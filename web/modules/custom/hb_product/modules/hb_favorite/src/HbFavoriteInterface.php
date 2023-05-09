<?php

namespace Drupal\hb_favorite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a favorite entity type.
 */
interface HbFavoriteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
