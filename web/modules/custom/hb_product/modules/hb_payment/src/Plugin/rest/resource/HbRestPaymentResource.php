<?php

namespace Drupal\hb_payment\Plugin\rest\resource;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\HbPaymentFactory;
use Drupal\hb_payment\Services\HbPaymentUpdateInfo;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents Payment records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_payment",
 *   label = @Translation("Payment"),
 *   uri_paths = {
 *     "create" = "/api/payment",
 *     "canonical" = "/api/payment"
 *   }
 * )
 *
 */
class HbRestPaymentResource extends ResourceBase
{


  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function post(Request $request, array $data)
  {
    $uid = \Drupal::currentUser()->id();

    if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
      'cart_id',
      'ip_address',
    ], $data)) {
      $userData = \Drupal::service('user.data');
      return new JsonResponse(['message' => $userData->get('hb_guard', $uid, 'guard_field') . ' is missing!'], 400);
    }

    $cart = HbCart::load($data['cart_id']);
    if (empty($cart)) {
      return new JsonResponse(['message' => 'Cart not found!'], 404);
    }

    $cart_are_in_stack = $cart->get('field_c_f_pay_after_receive')->value;
    $service_cart_validate = \Drupal::service('hb_cart.validate');
    if (!$cart_are_in_stack and !$service_cart_validate->isCurrentCart($cart)) {
      return new JsonResponse(['message' => 'Cart is paid or waiting for payment!'], 400);
    }

    if ($cart->get('uid')->target_id != $uid) {
      return new JsonResponse(['message' => 'Forbidden!'], 403);
    }

    if (!$cart_are_in_stack and $request->get('pay_after_receive')) {
      /** @var HbPaymentUpdateInfo $service_update_info */
      $service_update_info = \Drupal::service('hb_payment.update_info');
      $cart->set('status', 0);
      $cart->set('moderation_state', 'in_progressing');
      $cart->set('field_c_f_pay_after_receive', 1);
      $cart->save();
      $service_update_info->updatePaymentStatus($cart->id(), FALSE);
      $service_update_info->updateTotalProduct($cart->id());
      return new JsonResponse(['message' => 'Success!'], 200);
    }

    $payment_factory = new HbPaymentFactory($cart);
    $vnp_ReturnUrl = \Drupal::config('hb_payment.settings')->get('redirect_after_payment');
    $payment_factory->setReturnUrl($vnp_ReturnUrl);
    $vnp_Url = $payment_factory->initPaymentUrl();
    return new JsonResponse([
      'message' => t('Success!'),
      'result' => [
        'url' => $vnp_Url,
        'expired_in' => $payment_factory->getExpiredDate(),
      ],
    ], 200);
  }

  public function get(Request $request)
  {
    $user = User::load(\Drupal::currentUser()->id());
    $state = $request->get('state');
    $props = [
      'uid' => $user->id()
    ];
    if (!is_null($state)) {
      $props['status'] = (boolean)$state;
    }
    $results = [];
    $payments = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties($props);
    foreach ($payments as $payment) {
      $results['results'][] = [
        'id' => $payment->id(),
        'cart_id' => $payment->get('cart')->getString(),
        'name' => t('Đơn hàng số ' . $payment->get('cart')->getString(), [], ['langcode' => 'vi']),
        'amount' => $payment->get('info')->vnp_Amount,
        'created' => $payment->get('created')->getString(),
        'changed' => $payment->get('changed')->getString(),
        'pay_date' => $payment->get('info')->vnp_PayDate,
        'bank_code' => $payment->get('info')->vnp_BankCode,
        'card_type' => $payment->get('info')->vnp_CardType,
        'status' => (boolean)$payment->get('status')->getString(),
      ];
    }
    return new JsonResponse($results, 200);
  }

}
