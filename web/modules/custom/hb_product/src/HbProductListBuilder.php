<?php

namespace Drupal\hb_product;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the product entity type.
 */
class HbProductListBuilder extends EntityListBuilder {

  protected $limit = 10;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new HbProductListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $current_page = \Drupal::request()->get('page', 0);
    $link_to_import = Url::fromRoute('hb_product.import_products')->toString();
    $export_current_page_url = Url::fromRoute('view.product.excel_export_2');
    $export_current_page_url->setRouteParameters([
      'items_per_page' => 10,
      'page' => $current_page,
    ]);

    $build['header']['#markup'] = $this->t('<a href="' . $export_current_page_url->toString() .'"
 class="button button--action button--primary">Export current page</a>
 <a href="' . $link_to_import .'"
 class="button button--action button--primary">Import products</a>
');
    $this->sort();
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $export_all = Url::fromRoute('view.product.excel_export_1');
    $build['summary']['#markup'] = $this->t('<p>Total products: @total</p>
<a href="' . $export_all->toString() . '" class="button button--action button--primary">Export all</a>
', ['@total' => $total]);
    return $build;
  }

  public function sort() {
    $query = $this->getStorage()->getQuery();
    $query->sort('id', 'ASC');
    $query->accessCheck(FALSE);
    $this->getStorage()->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['type'] = $this->t('Type');
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\hb_product\HbProductInterface $entity */
    $row['id'] = $entity->id();
    $row['type'] = $entity->bundle();
    $row['label'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value);
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime());
    return $row + parent::buildRow($entity);
  }

}
