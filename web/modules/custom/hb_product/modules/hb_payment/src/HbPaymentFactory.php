<?php

namespace Drupal\hb_payment;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\hb_payment\Entity\HbPayment;
use Drupal\hb_payment\Services\HbPaymentUpdateInfo;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HbPaymentFactory implements HbPaymentResourceInterface {

  private array $vnp_data;

  public function __construct() {
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    $requested_date = new DrupalDateTime("now", new DateTimeZone('Asia/Ho_Chi_Minh'));
    $cart_id = \Drupal::routeMatch()->getParameter('cart_id');
    $return_url = Url::fromRoute('hb_product.payment', [
      'cart_id' => $cart_id
    ], ['absolute' => TRUE])->toString();
    $create_date = date('YmdHis', $requested_date->getTimestamp());
    $txn_ref = rand(1, 999999);
    $expire_date = date('YmdHis', strtotime(
      date(
        'Y-m-d H:i:s',
        $requested_date->getTimestamp(),
      ) . '+' . self::REQUEST_EXPIRED_TIME . ' minutes',
    ));

    $this->setVnpData([
      'vnp_Version' => self::VERSION,
      'vnp_TmnCode' => self::TMN_CODE,
      'vnp_CreateDate' => $create_date,
      'vnp_CurrCode' => self::CURRENCY_UNIT,
      'vnp_ReturnUrl' => $return_url,
      'vnp_TxnRef' => $txn_ref,
      'vnp_ExpireDate' => $expire_date,
      'vnp_Locale' => self::LOCALE,
      'vnp_OrderType' => self::ORDER_CODE
    ]);
  }

  /**
   * @param array $vnp_data
   */
  private function setVnpData(array $vnp_data): void {
    $this->vnp_data = $vnp_data;
  }

  private function vnPay(array $data) {
    ksort($data);
    $query = '';
    $i = 0;
    $hash_data = '';

    foreach ($data as $key => $value) {
      if ($i == 1) {
        $hash_data .= '&' . urlencode($key) . "=" . urlencode($value);
      } else {
        $hash_data .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
      }
      $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }
    $vnpSecureHash = hash_hmac('sha512', $hash_data, self::HASH_SECRET);
    $vnp_Url = self::PAYMENT_URL_EXT . "?" . $query;
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

    /** @var HbPaymentUpdateInfo $service_update_info */
    $service_update_info = \Drupal::service('hb_payment.update_info');
    $service_update_info->updatePaymentStatus(FALSE);

    $redirect = new TrustedRedirectResponse($vnp_Url);
    $redirect->send();
    exit();
  }

  public function pay($method, array $data) {
    if ($method == 'vn_pay') {
      $input_data = [
        'vnp_Amount' => $data['amount'],
        'vnp_Command' => 'pay',
        'vnp_IpAddr' => \Drupal::request()->getClientIp(),
        'vnp_OrderInfo' => vn_to_str('Thanh toán đơn hàng số ' . $data['cart_id'] . '.'),
      ];
      $this->vnPay(array_merge($this->vnp_data, $input_data));
    }

//    $inputData = [
//      'vnp_Version' => self::VERSION,
//      'vnp_TmnCode' => self::TMN_CODE,
//      'vnp_Amount' => self::AMOUNT,
//      'vnp_Command' => self::METHOD,
//      'vnp_CreateDate' => date('YmdHis', $requested_date->getTimestamp()),
//      'vnp_CurrCode' => self::CURRENCY_UNIT,
//      'vnp_IpAddr' => $data['ip_address'],
//      'vnp_Locale' => self::LOCALE,
//      'vnp_OrderInfo' =>
//      'vnp_OrderType' => self::ORDER_CODE,
//      'vnp_ReturnUrl' => Url::fromRoute('entity.hb_cart.collection', [], ['absolute' => TRUE])->toString(),
//      'vnp_TxnRef' => rand(1, 10000),
//      'vnp_ExpireDate' => date('YmdHis', strtotime(
//        date(
//          'Y-m-d H:i:s',
//          $requested_date->getTimestamp(),
//        ) . '+' . self::REQUEST_EXPIRED_TIME . ' minutes',
//      )),
//      'vnp_Bill_Mobile' => '84911587896',
//      'vnp_Bill_Email' => 'cp19112000@gmail.com',
//      'vnp_Bill_FirstName' => 'NGUYEN',
//      'vnp_Bill_LastName' => 'VAN AN',
//      'vnp_Bill_Address' => 'P315, 22 Lang Ha',
//      'vnp_Bill_City' => 'HANOI',
//      'vnp_Bill_Country' => 'VN',
//      'vnp_Bill_State' => 'CA',
//      'vnp_Inv_Phone' => '84911587896',
//      'vnp_Inv_Email' => 'cp19112000@gmail.com',
//      'vnp_Inv_Customer' => self::OWNER_NAME,
//      'vnp_Inv_Address' => '22 Láng Hạ, Đống Đa, Hà Nội',
//      'vnp_Inv_Company' => 'Công ty TTV',
//      'vnp_Inv_Taxcode' => '20180924080900',
//      'vnp_Inv_Type' => 'I',
//    ];
  }



}
