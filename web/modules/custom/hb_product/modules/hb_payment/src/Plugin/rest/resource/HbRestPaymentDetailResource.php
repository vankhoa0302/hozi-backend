<?php

namespace Drupal\hb_payment\Plugin\rest\resource;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\HbPaymentFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents Payment records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_payment_detail",
 *   label = @Translation("Payment detail"),
 *   uri_paths = {
 *     "canonical" = "/api/payment/{pid}"
 *   }
 * )
 *
 */
class HbRestPaymentDetailResource extends ResourceBase {

  public function get(Request $request, int $pid) {
    $user = User::load(\Drupal::currentUser()->id());
    $props = [
      'id' => $pid,
      'uid' => $user->id()
    ];
    $results = [];
    $payments = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties($props);

    if (empty($payments)) {
      return new JsonResponse(['message' => 'Payment not found!'], 404);
    }
//    $status = [
//      'draft' => 'Awaiting payment',
//      'completed' => 'Awaiting payment',
//      'waiting_for_approve' => 'Waiting for approve',
//      'approved' => 'Approved',
//      'in_progressing' => 'In-progressing',
//      'cancel' => 'Cancel',
//      'shipping' => 'Shipping',
//    ];

    foreach ($payments as $payment) {
      $results['results'] = [
        'id' => $payment->id(),
        'cart_id' => $payment->get('cart')->getString(),
        'name' => t('Đơn hàng số ' . $payment->get('cart')->getString(), [], ['langcode' => 'vi']),
        'transfer_content' => $payment->get('info')->vnp_OrderInfo,
//        'amount' => $payment->get('info')->vnp_Amount,
        'amount' => \Drupal::service('hb_cart.calculate')->calculateTotalAmount($payment->get('cart')->entity),
        'created' => $payment->get('created')->getString(),
        'changed' => $payment->get('changed')->getString(),
        'pay_date' => $payment->get('info')->vnp_PayDate,
        'bank_code' => $payment->get('info')->vnp_BankCode,
        'card_type' => $payment->get('info')->vnp_CardType,
        'status' => $payment->get('cart')->entity->get('moderation_state')->value,
        'address' => $payment->get('address')->getString(),
      ];
    }
    return new JsonResponse($results, 200);
  }

  public function patch(int $pid) {
    $user = User::load(\Drupal::currentUser()->id());
    $props = [
      'id' => $pid,
      'uid' => $user->id()
    ];
    $results = [];
    $payments = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties($props);

    if (empty($payments)) {
      return new JsonResponse(['message' => 'Payment not found!'], 404);
    }
    $payment = reset($payments);
    $cart = $payment->get('cart')->entity;
    $cart->set('moderation_state', 'cancel');
    $cart->set('status', 0);
    $cart->save();

    $results['results'] = [
      'id' => $payment->id(),
      'cart_id' => $cart->id(),
      'name' => t('Đơn hàng số ' . $cart->id(), [], ['langcode' => 'vi']),
      'transfer_content' => $payment->get('info')->vnp_OrderInfo,
//        'amount' => $payment->get('info')->vnp_Amount,
      'amount' => \Drupal::service('hb_cart.calculate')->calculateTotalAmount($cart),
      'created' => $payment->get('created')->getString(),
      'changed' => $payment->get('changed')->getString(),
      'pay_date' => $payment->get('info')->vnp_PayDate,
      'bank_code' => $payment->get('info')->vnp_BankCode,
      'card_type' => $payment->get('info')->vnp_CardType,
      'status' => $cart->get('moderation_state')->value,
      'address' => $payment->get('address')->getString(),
    ];

    return new JsonResponse($results, 200);
  }

}
