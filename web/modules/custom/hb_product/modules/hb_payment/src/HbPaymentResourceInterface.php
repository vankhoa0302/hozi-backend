<?php

namespace Drupal\hb_payment;

interface HbPaymentResourceInterface {
  public const VERSION = '2.1.0';

  public const PAYMENT_URL = 'http://sandbox.vnpayment.vn/merchant_webapi/merchant.html';

  public const PAYMENT_URL_EXT = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

  public const BANK_CODE = 'NCB';

  public const TMN_CODE = 'PLPAZE7V';

  public const HASH_SECRET = 'KEPHKCOGQBKNFWTODFMFSHOSUHQCFAPC';

  public const BANK_NAME = 'Ngân hàng NCB';

  public const CARD_NUMBER = '9704198526191432198';

  public const OWNER_NAME = 'NGUYEN VAN A';

  public const RELEASE_DATE = '07/15';

  public const OTP = '123456';

  public const AMOUNT = 1000000;

  public const METHOD = 'pay';

  public const CURRENCY_UNIT = 'VND';

  public const ORDER_CODE = 130000;
//  private const PRODUCT_CODE = 'other';

  public const REQUEST_EXPIRED_TIME = 30;

  public const LOCALE = 'vn';

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
