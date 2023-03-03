<?php

namespace Drupal\hb_guard;

/**
 * Class HbDataGuardService
 * @package Drupal\hb_guard\Services
 */
class HbDataGuardService {

    public function __construct() {
    }

    public function guardRequiredData(array $required_data, array $data) {
        foreach ($data as $item) {
            if (empty($required_data[$item])) {
                return FALSE;
            }
        }
        return TRUE;
    }
}