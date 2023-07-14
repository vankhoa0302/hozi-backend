<?php

namespace Drupal\hb_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a hb_payment form.
 */
class TurnOverForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hb_payment_turn_over';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filter'] = [
      '#type' => 'container',
      'range' => [
        'start' => [
          '#type' => 'date',
          '#title' => 'Tù ngày',
        ],
        'end' => [
          '#type' => 'date',
          '#title' => 'Đến ngày',
        ],
      ],
      'in' => [
        '#type' => 'select',
        '#title' => 'Filters',
        '#options' => [
          '_none' => '-- None --',
          'current' => 'Trong ngày',
          'week' => 'Trong tuần',
          'month' => 'Trong tháng',
          'quarter' => 'Trong quý',
        ],
      ],
      '#attributes' => [
        'style' => [
          'display: flex;',
          'justify-content: space-evenly;'
        ]
      ]
    ];

    $form['data'] = [
      '#type' => 'container',
      'turnover' => [
        '#type' => 'number',
        '#title' => 'Doanh thu',
        '#attributes' => [
          'min' => 1,
          'value' => \Drupal::service('hb_payment.calculate')
            ->calculateTurnover(),
          'readonly' => 'readonly',
        ],
      ],
      'profit' => [
        '#type' => 'number',
        '#title' => 'Lợi nhuận',
        '#attributes' => [
          'min' => 1,
          'value' => \Drupal::service('hb_payment.calculate')
            ->calculateProfit(),
          'readonly' => 'readonly',
        ],
      ],
      'inventory' => [
        '#type' => 'number',
        '#title' => 'Hàng tồn',
        '#attributes' => [
          'min' => 1,
          'value' => \Drupal::service('hb_payment.calculate')
            ->calculateInventory(),
          'readonly' => 'readonly',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
