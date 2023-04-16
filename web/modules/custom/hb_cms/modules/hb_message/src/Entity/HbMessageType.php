<?php

namespace Drupal\hb_message\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Message type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "hb_message_type",
 *   label = @Translation("Message type"),
 *   label_collection = @Translation("Message types"),
 *   label_singular = @Translation("message type"),
 *   label_plural = @Translation("messages types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count messages type",
 *     plural = "@count messages types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\hb_message\Form\HbMessageTypeForm",
 *       "edit" = "Drupal\hb_message\Form\HbMessageTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\hb_message\HbMessageTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer message types",
 *   bundle_of = "hb_message",
 *   config_prefix = "hb_message_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hb_message_types/add",
 *     "edit-form" = "/admin/structure/hb_message_types/manage/{hb_message_type}",
 *     "delete-form" = "/admin/structure/hb_message_types/manage/{hb_message_type}/delete",
 *     "collection" = "/admin/structure/hb_message_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HbMessageType extends ConfigEntityBundleBase {

  /**
   * The machine name of this message type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the message type.
   *
   * @var string
   */
  protected $label;

}
