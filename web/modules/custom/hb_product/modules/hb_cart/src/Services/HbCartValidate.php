<?php

namespace Drupal\hb_cart\Services;

use Drupal\hb_cart\Entity\HbCart;

/**
 * Class HbCartValidate
 * @package Drupal\hb_cart\Services
 */
class HbCartValidate {

  public function isCurrentCart(HbCart $cart): bool {
    if ($cart->get('moderation_state')->getString() == 'draft') {
      return TRUE;
    }
    return FALSE;
  }

}
