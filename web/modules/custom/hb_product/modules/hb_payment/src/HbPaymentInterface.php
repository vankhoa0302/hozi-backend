<?php

namespace Drupal\hb_payment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a payment entity type.
 */
interface HbPaymentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
