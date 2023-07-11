<?php

namespace Drupal\hb_payment;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\hb_payment\Entity\HbPayment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the payment entity type.
 */
class HbPaymentListBuilder extends EntityListBuilder {

  protected $limit = 10;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new HbPaymentListBuilder object.
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
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total payments: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['cart'] = $this->t('Cart');
    $header['status'] = $this->t('Status');
    $header['address'] = $this->t('Address');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /** @var \Drupal\hb_payment\HbPaymentInterface $entity */
    $row['id'] = $entity->toLink();
    $row['cart'] = $entity->get('cart')->entity->toLink();
    $status = [
      'draft' => 'Awaiting payment',
      'completed' => 'Awaiting payment',
      'waiting_for_approve' => 'Waiting for approve',
      'approved' => 'Approved',
      'in_progressing' => 'In-progressing',
      'cancel' => 'Cancel',
      'shipping' => 'Shipping',
    ];
    $row['status'] = $status[$entity->get('cart')->entity->get('moderation_state')->value] ?? '';
    $row['address'] = $entity->get('address')->getString();
    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value);
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime());
    return $row + parent::buildRow($entity);
  }

}
