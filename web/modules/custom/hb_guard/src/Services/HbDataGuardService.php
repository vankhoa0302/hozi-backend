<?php

namespace Drupal\hb_guard\Services;

/**
 * Class HbDataGuardService
 * @package Drupal\hb_guard\Services
 */
class HbDataGuardService {

	public function guardRequiredData(array $required_data, array $data) {
		foreach ($required_data as $item) {
			if (!isset($data[$item])) {
				$userData = \Drupal::service('user.data');
				$userData->set('hb_guard', \Drupal::currentUser()->id(), 'guard_field', $item);
				return TRUE;
			}
		}
		return FALSE;
	}

	public function generateHash() {
		$data = rand(100000, 999999) . bin2hex(random_bytes(22)) . time();
		return md5($data);
	}
}