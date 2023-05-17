<?php

namespace Drupal\hb_cms\Plugin\views\field;

use Drupal\comment\Entity\Comment;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ViewsField("comment_views_field")
 */
class CommentViewsField extends EntityField {

  protected function getFieldStorageDefinition() {
    $entity_type_id = $this->getEntityType();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    $field_name_arr = preg_grep('/(_comments)$/i', array_keys($field_storage_definitions));
    $this->definition['field_name'] = reset($field_name_arr);
    return $field_storage_definitions[$this->definition['field_name']];
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    if (empty($items)) {
      return [];
    }
    $entity = $values->_entity;
    $storage = \Drupal::entityTypeManager()->getStorage('comment');
    $commentField = $entity->get($this->definition['field_name']);
    $comments = $storage->loadThread($entity, $commentField->getFieldDefinition()->getName(), \Drupal\comment\CommentManagerInterface::COMMENT_MODE_FLAT);

    $items = array_map(function ($item) {
      /** @var Comment $item */
      return ['rendered' => ['#markup' => [
        'author' => $item->getOwner()->label(),
        'value' => $item->get('comment_body')->getString()
      ]]];
    }, $comments);

    return $items;
  }
}
