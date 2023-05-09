<?php

namespace Drupal\hb_cms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'blob' formatter.
 *
 * @FieldFormatter(
 *   id = "blob",
 *   label = @Translation("Blob tracking data"),
 *   field_types = {
 *     "blob",
 *   }
 * )
 */
class BlobFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => 'print_r(unserialize($values))',
        ];
      }
    }

    return $elements;
  }

}
