<?php

namespace Drupal\hb_payment;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\Services\HbPaymentUpdateInfo;

class HbPaymentManager {

  public function initForm() {
    $cart_id = \Drupal::routeMatch()->getParameter('cart_id');
    $cart = HbCart::load($cart_id);

    $form['amount'] = [
      '#type' => 'number',
      '#title' => t('Số tiền', [], ['langcode' => 'vi']),
      '#attributes' => [
        'min' => 1,
        'value' => \Drupal::service('hb_cart.calculate')->calculateTotalAmount($cart),
        'max' => 100000000,
        'readonly' => 'readonly'
      ],
    ];
    $option_1 = t('Cách 1: Chuyển hướng sang Cổng VNPAY chọn phương thức thanh toán',
      [], ['langcode' => 'vi'])->render();
    $option_2 = t('Cách 2: Tách phương thức tại site của đơn vị kết nối',
      [], ['langcode' => 'vi'])->render();
    $form['method'] = [
      '#type' => 'select',
      '#title' => t('Chọn phương thức thanh toán', [], ['langcode' => 'vi']),
      '#options' => [
        $option_1 => [
          'vn_pay' => t('Cổng thanh toán VNPAYQR', [], ['langcode' => 'vi']),
        ],
        $option_2 => [
          'vn_pay_qr' => t('Thanh toán bằng ứng dụng hỗ trợ VNPAYQR', [], ['langcode' => 'vi']),
          'vn_bank' => t('Thanh toán qua thẻ ATM/Tài khoản nội địa', [], ['langcode' => 'vi']),
          'int_card' => t('Thanh toán qua thẻ quốc tế', [], ['langcode' => 'vi']),
        ],
      ],
      '#options_attributes' => [
        $option_2 => [
          'vn_pay_qr' => ['disabled' => 'disabled'],
          'vn_bank' => ['disabled' => 'disabled'],
          'int_card' => ['disabled' => 'disabled'],
        ]
      ]
    ];

    $form['actions'] = [
      '#type' => 'container',
      'submit' => [
        '#value' => t('Pay'),
        '#type' => 'submit',
      ],
      'cancel' => [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('entity.hb_cart.collection'),
      ]
    ];

    return $form;
  }

  public function resultForm() {
    $cart_id = \Drupal::routeMatch()->getParameter('cart_id');
    $payment_info = \Drupal::request()->query->all();
    $exist_payment = \Drupal::entityTypeManager()->getStorage('hb_payment')->loadByProperties([
      'cart' => $cart_id,
      'status' => 1,
    ]);

    if ($exist_payment) {
      $serialize_info = \Drupal::database()->select('hb_payment_field_data', 'p')
        ->fields('p', ['info', 'cart'])
        ->condition('p.cart', $cart_id)
        ->execute()
        ->fetchField();

      $payment_info = unserialize($serialize_info);
    }

    $form['container'] = [
      '#type' => 'container',
      '#prefix' => t('Kết quả', [], ['langcode' => 'vi']),
      '#suffix' => DrupalDateTime::createFromTimestamp(strtotime($payment_info['vnp_PayDate'])),
      '#attributes' => [
        'style' => ['display: flex; flex-direction: column;']
      ],
      'txn_ref' => [
        '#markup' => '<div>' . t('Số tiền: :[txn_ref]', [
            ':[txn_ref]' => $payment_info['vnp_TxnRef']
          ], ['langcode' => 'vi']) . '</div>',
      ],
      'order_info' => [
        '#markup' => '<div>' . t('Nội dung thanh toán: :[vnp_OrderInfo]', [
            ':[vnp_OrderInfo]' => $payment_info['vnp_OrderInfo']
          ], ['langcode' => 'vi']) . '</div>',
      ],
      'transaction_no' => [
        '#markup' => '<div>' . t('Mã GD Tại VNPAY: :[vnp_TransactionNo]', [
            ':[vnp_TransactionNo]' => $payment_info['vnp_TransactionNo']
          ], ['langcode' => 'vi']) . '</div>',
      ],
      'bank_code' => [
        '#title' => '<div>' . t('Mã Ngân hàng: :[vnp_BankCode]', [
            ':[vnp_BankCode]' => $payment_info['vnp_BankCode']
          ], ['langcode' => 'vi']) . '</div>',
      ],
    ];

    if ($exist_payment) {
      return $form;
    }

    $response = [
      'message' => '<div>' . t('Chữ ký không hợp lệ', [], [
          'langcode' => 'vi'
        ]) . '</div>',
      'type' => 'warning',
      'repeat' => TRUE,
    ];

    $cart_id = \Drupal::routeMatch()->getParameter('cart_id');
    $response_salt = Link::createFromRoute(t('Quay về trang thanh toán', [], [
      'langcode' => 'vi'
    ]), 'hb_payment.payment', [
      'cart_id' => $cart_id
    ], ['absolute' => TRUE])->toString();

    /** @var HbPaymentUpdateInfo $service_update_info */
    $service_update_info = \Drupal::service('hb_payment.update_info');

    $valid_signature = \Drupal::service('hb_payment.validate')->validSignature();
    $cart = HbCart::load($cart_id);
    if ($valid_signature) {
      $cart->set('status', 0);
      $response = [
        'message' => '<div>' . t('GD Không thành công', [], [
            'langcode' => 'vi'
          ]) . '</div>',
        'type' => 'warning',
        'repeat' => TRUE,
      ];

      if ($payment_info['vnp_ResponseCode'] == '00') {
        $service_update_info->updatePaymentStatus($cart_id, TRUE);
        $cart->set('moderation_state', 'published');

        $response = [
          'message' => '<div>' . t('GD Thành công', [], [
              'langcode' => 'vi'
            ]) . '</div>',
          'type' => 'status',
          'repeat' => FALSE,
        ];
        $response_salt = '';
        $service_update_info->updateTotalProduct($cart_id);
      }
      $service_update_info->updatePaymentInfo($cart_id, $payment_info);
    }
    $cart->save();
    $response_message = Markup::create($response['message'] . $response_salt);

    \Drupal::messenger()->addMessage($response_message, $response['type'], $response['repeat']);

    return $form;
  }

}
