<?php

namespace Drupal\hb_cart;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a hb_cart entity type.
 */
interface HbCartInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
