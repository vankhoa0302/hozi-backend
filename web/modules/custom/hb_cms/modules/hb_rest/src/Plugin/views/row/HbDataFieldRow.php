<?php

namespace Drupal\hb_rest\Plugin\views\row;

use Drupal\comment\Entity\Comment;
use Drupal\rest\Plugin\views\row\DataFieldRow;
use Drupal\views\ResultRow;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "hb_data_field",
 *   title = @Translation("Fields (Optionals)"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"data"}
 * )
 */
class HbDataFieldRow extends DataFieldRow {

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    /** @var ResultRow $row */
    $output = [];

    foreach ($this->view->field as $id => $field) {
      // If the raw output option has been set, just get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = array_map(function ($item) {
          if (isset($item['rendered']['#markup'])) {
            return $item['rendered']['#markup'];
          }
          return NULL;
        }, $field->getItems($row));

//       Original is $field->getValue($row)
      }
      // Otherwise, get rendered field.
      else {
        // Advanced render for token replacement.
        $markup = $field->advancedRender($row);
        // Post render to support un-cacheable fields.
        $field->postRender($row, $markup);
        $value = $field->last_render;
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($id)] = $value;
      }
    }
    return $output;
  }

}
