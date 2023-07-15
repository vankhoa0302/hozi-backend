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

  public function calculateTurnover($range = [], $current = FALSE,
    $current_week = FALSE, $month = null, $quarter = null) {
    $exist_payment = \Drupal::entityTypeManager()
      ->getStorage('hb_payment')
      ->loadByProperties([
        'cart.entity:hb_cart.status' => 'published',
      ]);
    if (empty($range) and !$current and !$current_week
      and is_null($month) and is_null($quarter)) {
      $valid_cart = [];
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published') {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }

      return $turnover;
    }
    elseif (!empty($range)) {
      $valid_cart = [];
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published' and
        $payment->get('created')->getString() >= $range['start'] and
        $payment->get('created')->getString() <= $range['end']) {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }

      return $turnover;
    } elseif ($current) {
      $valid_cart = [];
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published' and
          date('d', $payment->get('created')->getString()) ==
          date('d', \Drupal::time()->getCurrentTime())) {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }
      return $turnover;
    } elseif ($current_week) {
      $valid_cart = [];
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published' and
          date('Y-m', $payment->get('created')->getString()) ==
          date('Y-m', \Drupal::time()->getCurrentTime()) and
          date('W', $payment->get('created')->getString()) ==
          date('W', \Drupal::time()->getCurrentTime())) {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }
      return $turnover;
    } elseif (!is_null($month)) {
      $valid_cart = [];
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published' and
          date('Y', $payment->get('created')->getString()) ==
          date('Y', \Drupal::time()->getCurrentTime()) and
          (int) date('m', $payment->get('created')->getString()) ==
          $month) {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }
      return $turnover;
    } elseif (!is_null($quarter)) {
      $valid_cart = [];
      $start = 0;
      $end = 0;
      if ($quarter == 1) {
        $start = 1;
        $end = 3;
      } elseif ($quarter == 2) {
        $start = 3;
        $end = 6;
      } elseif ($quarter == 3) {
        $start = 6;
        $end = 9;
      } elseif ($quarter == 4) {
        $start = 9;
        $end = 12;
      }
      foreach ($exist_payment as $payment) {
        $cart = $payment->get('cart')->entity;
        if ($cart->get('moderation_state')->value == 'published' and
          date('n', $payment->get('created')->getString()) >
          $start and
          date('n', $payment->get('created')->getString()) <
          $end) {
          $valid_cart[] = $cart;
        }
      }
      $turnover = 0;
      foreach ($valid_cart as $cart) {
        $turnover += \Drupal::service('hb_cart.calculate')
          ->calculateTotalAmount($cart);
      }
      return $turnover;
    }
  }

  public function calculateInventory() {
    $exist_product = \Drupal::entityTypeManager()
      ->getStorage('hb_product')
      ->loadByProperties([
        'status' => 1,
      ]);
    $inventory = 0;
    foreach ($exist_product as $product) {
      $inventory += $product->get('field_p_f_quantity')->getString();
    }
    return $inventory;
  }

  public function calculateProfit($turnover) {
    $exist_product = \Drupal::entityTypeManager()
      ->getStorage('hb_product')
      ->loadByProperties([
        'status' => 1,
      ]);
    $inventory = 0;
    foreach ($exist_product as $product) {
      $quantity = $product->get('field_p_f_quantity')->getString();
      $price = (int) $product->get('field_p_f_original_price')->getString();
      $inventory += $quantity * $price;
    }

    $profit = $turnover - $inventory;
    return $profit;
  }

}
