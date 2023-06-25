<?php

namespace Drupal\hb_payment;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_payment\Services\HbPaymentUpdateInfo;

class HbPaymentFactory implements HbPaymentResourceInterface {

  private array $vnp_data;

  private int $cart_id;

  private int $amount;
  private string $expired_date;
  private string $return_url;

  private string $created_date;

  private string $vnp_IpAddr;

  private string $vnp_OrderInfo;

  private DrupalDateTime $requested_date;

  private int $txn_ref;


  private HbCart $cart;


  public function __construct(HbCart $cart) {
    $this->setDefaultTimeZone();
    $this->cart = $cart;
    $this->cart_id = $cart->id();
    $this->setDefaultReturnUrl();
    $this->setDefaultAmount();
    $this->setDefaultRequestedDate();
    $this->setDefaultCreatedDate();
    $this->txn_ref = rand(1, 999999);
    $this->setDefaultExpiredDate();
    $this->setDefaultVnpIpAddr();
    $this->setDefaultVnpOrderInfo();
    $this->setDefaultVnpData();
  }
  private function setDefaultTimeZone() {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
  }

  private function setDefaultReturnUrl() {
    $this->return_url = Url::fromRoute('hb_payment.payment', [
      'cart_id' => $this->cart_id,
    ], ['absolute' => TRUE])->toString();
  }

  private function setDefaultAmount() {
    $this->amount = \Drupal::service('hb_cart.calculate')->calculateTotalAmount($this->cart);
  }

  private function setDefaultRequestedDate() {
    $this->requested_date = new DrupalDateTime("now", new DateTimeZone('Asia/Ho_Chi_Minh'));
  }

  private function setDefaultCreatedDate() {
    $this->created_date = date('YmdHis', $this->requested_date->getTimestamp());
  }

  private function setDefaultExpiredDate() {
    $this->expired_date = date('YmdHis', strtotime(
      date(
        'Y-m-d H:i:s',
        $this->requested_date->getTimestamp(),
      ) . '+' . \Drupal::config('hb_payment.settings')->get('expired_time') . ' minutes',
    ));
  }

  private function setDefaultVnpIpAddr(): void {
    $this->vnp_IpAddr = \Drupal::request()->getClientIp();
  }

  private function setDefaultVnpOrderInfo(): void {
    $this->vnp_OrderInfo = vn_to_str('Thanh toán đơn hàng số ' . $this->cart_id . '.');
  }

  private function setDefaultVnpData() {
    $this->vnp_data = [
      'vnp_Version' => self::VERSION,
      'vnp_TmnCode' => self::TMN_CODE,
      'vnp_CreateDate' => $this->created_date,
      'vnp_CurrCode' => self::CURRENCY_UNIT,
      'vnp_ReturnUrl' => $this->return_url,
      'vnp_TxnRef' => $this->txn_ref,
      'vnp_ExpireDate' => $this->expired_date,
      'vnp_Locale' => self::LOCALE,
      'vnp_OrderType' => self::ORDER_CODE,
      'vnp_Command' => self::METHOD,
      'vnp_Amount' => $this->amount,
      'vnp_IpAddr' => $this->vnp_IpAddr,
    ];
  }


  /**
   * @param string $return_url
   */
  public function setReturnUrl(string $return_url): void {
    $this->return_url = $return_url;
  }

  /**
   * @return string
   */
  public function getExpiredDate(): string {
    return $this->expired_date;
  }

  /**
   * @return string
   */
  public function getDefaultTimeZone(): string {
    return date_default_timezone_get();
  }

  /**
   * @param string $vnp_OrderInfo
   */
  public function setVnpOrderInfo(string $vnp_OrderInfo): void {
    $this->vnp_OrderInfo = $vnp_OrderInfo;
  }


  public function initPaymentUrl() {
    $data = $this->vnp_data;
    $data['vnp_ReturnUrl'] = $this->return_url;
    $data['vnp_OrderInfo'] = $this->vnp_OrderInfo;
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
    $service_update_info->updatePaymentStatus($this->cart_id, FALSE);
    $service_update_info->updatePaymentInfo($this->cart_id, $data);
    return $vnp_Url;
  }

}
