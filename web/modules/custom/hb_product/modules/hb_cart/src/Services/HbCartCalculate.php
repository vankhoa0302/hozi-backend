<?php

namespace Drupal\hb_cart\Services;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class HbCartCalculate
 * @package Drupal\hb_cart\Services
 */
class HbCartCalculate {

  public function calculateTotalAmount(HbCart $cart): float|int {
    $furniture_id = array_map(function ($furniture) {
      return $furniture['target_id'];
    }, $cart->get('field_c_f_furniture')->getValue());
    $furniture = Paragraph::loadMultiple($furniture_id);
    $amount = 0;
    foreach ($furniture as $item) {
      $quantity = $item->get('field_p_f_c_quantity')->getString();
      $price = $item->get('field_p_f_c_furniture')->entity->get('field_p_f_price')->getString();
      $amount += $quantity * $price;
    }
    return $amount;
  }

}
