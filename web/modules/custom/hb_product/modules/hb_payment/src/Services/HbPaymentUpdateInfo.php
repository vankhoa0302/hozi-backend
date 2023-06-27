<?php

namespace Drupal\hb_payment\Services;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\Entity\HbPayment;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\paragraphs\Entity\Paragraph;
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
        'uid' => \Drupal::currentUser()->id(),
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

  public function updateTotalProduct(int $cart_id) {
    $furniture = HbCart::load($cart_id)->get('field_c_f_furniture')->getValue();
    foreach ($furniture as $item) {
      $para = Paragraph::load($item['target_id']);
      $product = HbProduct::load($para->get('field_p_f_c_furniture')->target_id);
      $amount = $para->get('field_p_f_c_quantity')->getString();
      $total = $product->get('field_p_f_quantity')->getString();
      $product->set('field_p_f_quantity', $total - $amount)->save();
    }
  }

}
