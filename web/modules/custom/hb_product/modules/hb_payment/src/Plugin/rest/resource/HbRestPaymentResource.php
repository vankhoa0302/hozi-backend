<?php

namespace Drupal\hb_payment\Plugin\rest\resource;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\HbPaymentManager;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents Payment records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_payment",
 *   label = @Translation("Payment"),
 *   uri_paths = {
 *     "create" = "/api/payment"
 *   }
 * )
 *
 */
class HbRestPaymentResource extends ResourceBase {



  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function post(Request $request, array $data) {
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

    if ($cart->get('uid')->target_id != $uid) {
      return new JsonResponse(['message' => 'Forbidden!'], 403);
    }

//    $payment_manager = new HbPaymentManager();
//    $payment_manager->pay($data);
    /** @todo complete payment api logic */
    return new JsonResponse([], 200);
  }

}
