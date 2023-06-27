<?php

namespace Drupal\hb_rest\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\rest\Plugin\views\style\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The style plugin for serialized output formats with pager.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "hb_serializer_with_pager",
 *   title = @Translation("Serializer with full pager"),
 *   help = @Translation("Serializes views row data using the Serializer component with pager."),
 *   display_types = {"data"}
 * )
 */
class HbSerializerWithPager extends Serializer {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = $pager_info = [];

    // If the data entity row plugin is used, this will be an array of entities
    // which will pass through serializer to one of the registered normalizers,
    // which will transform it to arrays/scalars. If the data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    // Create pager info if pagination is enabled in view.
    if ($pager = $this->view->pager) {
      $plugin_id = $pager->getPluginId();
      if ($plugin_id == 'mini' || $plugin_id == 'full') {
        $items_per_page = (int)$pager->options['items_per_page'];
        $count = (int)$pager->getTotalItems();
        if ($items_per_page === 0) {
          $items_per_page = $count;
        }
        $pages = ceil($count / $items_per_page);
        $current_page = $pager->getCurrentPage() ?? 0;
        $next_page = $current_page + 1;
        if ($next_page == $pages || $pages == 0) {
          $next_page = 0;
        }

        $pager_info['pager'] = [
          'count' => $count,
          'pages' => (int)$pages,
          'items_per_page' => $items_per_page,
          'current_page' => (int)$current_page,
          'next_page' => $next_page,
        ];
      } elseif ($plugin_id == 'some' && $pager->options['items_per_page'] == 1){
        $rows = reset($rows);
      }
    }

    unset($this->view->row_index);
    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    } else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $results = ['results' => $rows] + $pager_info;

    return $this->serializer->serialize($results, $content_type, ['views_style_plugin' => $this]);
  }

}
