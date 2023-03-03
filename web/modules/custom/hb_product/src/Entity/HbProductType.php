<?php

namespace Drupal\hb_product\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Product type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hb_product_type",
 *   label = @Translation("Product type"),
 *   label_collection = @Translation("Product types"),
 *   label_singular = @Translation("product type"),
 *   label_plural = @Translation("products types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count products type",
 *     plural = "@count products types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hb_product\Form\HbProductTypeForm",
 *       "edit" = "Drupal\hb_product\Form\HbProductTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hb_product\HbProductTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer product types",
 *   bundle_of = "hb_product",
 *   config_prefix = "hb_product_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hb_product_types/add",
 *     "edit-form" = "/admin/structure/hb_product_types/manage/{hb_product_type}",
 *     "delete-form" = "/admin/structure/hb_product_types/manage/{hb_product_type}/delete",
 *     "collection" = "/admin/structure/hb_product_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HbProductType extends ConfigEntityBundleBase {

  /**
   * The machine name of this product type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the product type.
   *
   * @var string
   */
  protected $label;

}
