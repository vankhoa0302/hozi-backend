<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;

/**
 * Represents RestUserUpdate records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user_update",
 *   label = @Translation("User Update"),
 *   uri_paths = {
 *     "create" = "/api/user/update"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserUpdateResource extends ResourceBase {

}
