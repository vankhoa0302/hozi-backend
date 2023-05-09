<?php

namespace Drupal\hb_payment\Services;

use Drupal\hb_payment\Entity\HbPayment;
use Drupal\hb_payment\HbPaymentResourceInterface;

class HbPaymentValidate implements HbPaymentResourceInterface {

  public function validSignature(): bool {
    $request = \Drupal::request();

    $vnp_SecureHash = $request->query->get('vnp_SecureHash');
    $request_params = $request->query->all();
    $inputData = [];

    foreach ($request_params as $key => $value) {
      if (str_starts_with($key, "vnp_")) {
        $inputData[$key] = $value;
      }
    }

    unset($inputData['vnp_SecureHash']);
    ksort($inputData);
    $i = 0;
    $hashData = "";
    foreach ($inputData as $key => $value) {
      if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
      } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
      }
    }

    $secureHash = hash_hmac('sha512', $hashData, self::HASH_SECRET);

    return $secureHash == $vnp_SecureHash;
  }

}
