<?php

namespace Drupal\hb_favorite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Favorite type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hb_favorite_type",
 *   label = @Translation("Favorite type"),
 *   label_collection = @Translation("Favorite types"),
 *   label_singular = @Translation("favorite type"),
 *   label_plural = @Translation("favorites types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count favorites type",
 *     plural = "@count favorites types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hb_favorite\Form\HbFavoriteTypeForm",
 *       "edit" = "Drupal\hb_favorite\Form\HbFavoriteTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hb_favorite\HbFavoriteTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer favorite types",
 *   bundle_of = "hb_favorite",
 *   config_prefix = "hb_favorite_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hb_favorite_types/add",
 *     "edit-form" = "/admin/structure/hb_favorite_types/manage/{hb_favorite_type}",
 *     "delete-form" = "/admin/structure/hb_favorite_types/manage/{hb_favorite_type}/delete",
 *     "collection" = "/admin/structure/hb_favorite_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HbFavoriteType extends ConfigEntityBundleBase {

  /**
   * The machine name of this favorite type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the favorite type.
   *
   * @var string
   */
  protected $label;

}
