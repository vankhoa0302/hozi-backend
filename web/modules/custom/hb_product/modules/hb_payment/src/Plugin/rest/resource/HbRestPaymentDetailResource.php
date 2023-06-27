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

    foreach ($payments as $payment) {
      $results['results'] = [
        'id' => $payment->id(),
        'name' => t('Đơn hàng số ' . $payment->get('cart')->getString(), [], ['langcode' => 'vi']),
        'transfer_content' => $payment->get('info')->vnp_OrderInfo,
        'amount' => $payment->get('info')->vnp_Amount,
        'created' => $payment->get('created')->getString(),
        'changed' => $payment->get('changed')->getString(),
        'pay_date' => $payment->get('info')->vnp_PayDate,
        'bank_code' => $payment->get('info')->vnp_BankCode,
        'card_type' => $payment->get('info')->vnp_CardType,
        'status' => (boolean) $payment->get('status')->getString(),
      ];
    }
    return new JsonResponse($results, 200);
  }

}
