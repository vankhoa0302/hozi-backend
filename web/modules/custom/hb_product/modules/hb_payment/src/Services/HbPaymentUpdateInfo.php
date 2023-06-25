<?php

namespace Drupal\hb_payment\Services;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\hb_payment\Entity\HbPayment;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;

class HbPaymentUpdateInfo {
  // Payment info only update changed time when paid success
  public function updatePaymentStatus(int $cart_id, bool $status): void {

    try {
      $exist_payment = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties([
        'cart' => $cart_id
      ]);
      if ($exist_payment) {
        /** @var HbPayment $payment */
        $payment = reset($exist_payment);
        $payment->setChangedTime(\Drupal::time()->getRequestTime());
        $payment->set('status', $status);
      } else {
        $payment = HbPayment::create([
          'cart' => $cart_id,
          'status' => $status,
        ]);
      }

      $payment->save();
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  public function updatePaymentInfo(int $cart_id, array $info): void {
    $exist_payment = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties([
      'cart' => $cart_id
    ]);

    $encode_info = serialize($info);
    if (!$exist_payment) {
      HbPayment::create([
        'cart' => $cart_id,
      ])->save();
    }

    \Drupal::database()->update('hb_payment_field_data')
      ->fields([
        'info' => $encode_info
      ])
      ->condition('cart', $cart_id)
      ->execute();
  }

}
