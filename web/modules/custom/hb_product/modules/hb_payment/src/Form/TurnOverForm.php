<?php

namespace Drupal\hb_payment\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
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
          '#states' => [
            'enabled' => [
              ':input[name="in"]' => ['value' => '_none']
            ],
          ],
        ],
        'end' => [
          '#type' => 'date',
          '#title' => 'Đến ngày',
          '#states' => [
            'enabled' => [
              ':input[name="in"]' => ['value' => '_none']
            ],
          ],
        ],

      ],
      'in' => [
        '#type' => 'select',
        '#title' => 'Filters',
        '#options' => [
          '_none' => '-- None --',
          'current' => 'Trong ngày hôm nay',
          'week' => 'Trong tuần này',
          'month' => 'Trong tháng',
          'quarter' => 'Trong quý',
        ],
      ],
      'month' => [
        '#type' => 'select',
        '#title' => 'Tháng',
        '#options' => [
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
          6 => 6,
          7 => 7,
          8 => 8,
          9 => 9,
          10 => 10,
          11 => 11,
          12 => 12,
        ],
        '#states' => [
          'visible' => [
            ':input[name="in"]' => ['value' => 'month']
          ],
          'enabled' => [
            ':input[name="in"]' => ['value' => 'month']
          ],
        ],
      ],
      'quarter' => [
        '#type' => 'select',
        '#title' => 'Quý',
        '#options' => [
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
        ],
        '#states' => [
          'visible' => [
            ':input[name="in"]' => ['value' => 'quarter']
          ],
          'enabled' => [
            ':input[name="in"]' => ['value' => 'quarter']
          ],
        ],
      ],
      'apply' => [
        '#type' => 'submit',
        '#value' => 'Apply filter',
        '#ajax' => [
          'callback' => [$this, 'ajaxSubmitForm'],
          'wrapper' => 'feeds-ajax-form-wrapper',
          'progress' => 'none',
        ],
      ],
      '#attributes' => [
        'style' => [
          'display: flex;',
          'justify-content: space-evenly;'
        ]
      ]
    ];

    $turnover = \Drupal::service('hb_payment.calculate')
      ->calculateTurnover();
    $form['data'] = [
      '#type' => 'container',
      'turnover' => [
        '#type' => 'number',
        '#title' => 'Doanh thu',
        '#attributes' => [
          'min' => 1,
          'value' => $turnover,
          'readonly' => 'readonly',
        ],
      ],
      'profit' => [
        '#type' => 'number',
        '#title' => 'Lợi nhuận',
        '#attributes' => [
          'min' => 1,
          'value' => \Drupal::service('hb_payment.calculate')
            ->calculateProfit($turnover),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $turnover = 0;
    if (!empty($form_state->getValue('start'))) {
      $start = $form_state->getValue('start');
      $end = $form_state->getValue('end');
      $turnover = \Drupal::service('hb_payment.calculate')
        ->calculateTurnover([
          'start' => strtotime($start),
          'end' => strtotime($end)
        ]);
    } elseif (!empty($form_state->getValue('in'))) {
      $context = $form_state->getValue('in');
      if ($context == 'current') {
        $turnover = \Drupal::service('hb_payment.calculate')
          ->calculateTurnover([], TRUE);
      } elseif ($context == 'week') {
        $turnover = \Drupal::service('hb_payment.calculate')
          ->calculateTurnover([], FALSE, TRUE);
      } elseif ($context == 'month') {
        $turnover = \Drupal::service('hb_payment.calculate')
          ->calculateTurnover([], FALSE, FALSE, $form_state->getValue('month'));
      } elseif ($context == 'quarter') {
        $quarter = $form_state->getValue('quarter');
        $turnover = \Drupal::service('hb_payment.calculate')
          ->calculateTurnover([], FALSE, FALSE, null, $quarter);
      }

    }
    $profit = \Drupal::service('hb_payment.calculate')
      ->calculateProfit($turnover);
    $response = new AjaxResponse();
    $response->addCommand(
      new InvokeCommand('input[data-drupal-selector="edit-turnover"]',
      'val',
      [$turnover]
      )
    );
    $response->addCommand(
      new InvokeCommand('input[data-drupal-selector="edit-profit"]',
      'val',
      [$profit]
      )
    );
    return $response;
  }

}
