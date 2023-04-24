<?php

namespace Drupal\hb_account;

interface HbOTPInterface {
  public function isExpired(int $created,int $expired_in);
}
