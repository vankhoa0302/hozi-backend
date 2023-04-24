<?php

namespace Drupal\hb_account;

use Drupal\hb_account\HbOTPInterface;

class HbOTP implements HbOTPInterface {

  /**
   * @param $created
   * timestamp
   * @param $expired_in
   * minutes
   * @return bool
   */
  public function isExpired(int $created,int $expired_in): bool {
    if (strtotime(
        date(
          'Y-m-d H:i:s',
          strtotime(
            date(
              'Y-m-d H:i:s',
              $created,
            ) . '+' . $expired_in . ' minutes',
          ),
        ),
      ) < \Drupal::time()->getRequestTime()
    ) {
      return TRUE;
    }
    return FALSE;
  }
}
