<?php

namespace Drupal\hb_payment\Services;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\Entity\HbPayment;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;

class HbPaymentCalculate {

  public function calculateTurnover() {
    $exist_payment = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties([
      'cart.entity:hb_cart.status' => 'published',
    ]);
    $valid_cart = [];
    foreach ($exist_payment as $payment) {
      $cart = $payment->get('cart')->entity;
      if ($cart->get('moderation_state')->value == 'published') {
        $valid_cart[] = $cart;
      }
    }
    $turnover = 0;
    foreach ($valid_cart as $cart) {
      $turnover += \Drupal::service('hb_cart.calculate')->calculateTotalAmount($cart);
    }

    return $turnover;
  }

  public function calculateInventory() {
    $exist_product = \Drupal::entityTypeManager()->getStorage('hb_product')->loadByProperties([
      'status' => 1,
    ]);
    $inventory = 0;
    foreach ($exist_product as $product) {
      $inventory +=  $product->get('field_p_f_quantity')->getString();
    }
    return $inventory;
  }

  public function calculateProfit() {
    $exist_product = \Drupal::entityTypeManager()->getStorage('hb_product')->loadByProperties([
      'status' => 1,
    ]);
    $inventory = 0;
    foreach ($exist_product as $product) {
      $quantity = $product->get('field_p_f_quantity')->getString();
      $price = $product->get('field_p_f_price')->getString();
      $discount = $product->get('field_p_f_discount')->getString();
      $inventory += $quantity * $price - $discount;
    }

    $profit = $this->calculateTurnover() - $inventory;
    return $profit;
  }
}
