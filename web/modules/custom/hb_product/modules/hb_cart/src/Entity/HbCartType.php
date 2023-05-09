<?php

namespace Drupal\hb_cart\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the hb_cart type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hb_cart_type",
 *   label = @Translation("Cart types"),
 *   label_collection = @Translation("Carts types"),
 *   label_singular = @Translation("Carts type"),
 *   label_plural = @Translation("Carts types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count carts type",
 *     plural = "@count carts types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hb_cart\Form\HbCartTypeForm",
 *       "edit" = "Drupal\hb_cart\Form\HbCartTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hb_cart\HbCartTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer hb_cart types",
 *   bundle_of = "hb_cart",
 *   config_prefix = "hb_cart_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hb_cart_types/add",
 *     "edit-form" = "/admin/structure/hb_cart_types/manage/{hb_cart_type}",
 *     "delete-form" = "/admin/structure/hb_cart_types/manage/{hb_cart_type}/delete",
 *     "collection" = "/admin/structure/hb_cart_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HbCartType extends ConfigEntityBundleBase {

  /**
   * The machine name of this hb_cart type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the hb_cart type.
   *
   * @var string
   */
  protected $label;

}
