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


}
