<?php

namespace Drupal\hb_message;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a message entity type.
 */
interface HbMessageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
