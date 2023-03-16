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
		foreach ($required_data as $item) {
			if (!isset($data[$item])) {
				$userData = \Drupal::service('user.data');
				$userData->set('hb_guard', \Drupal::currentUser()->id(), 'guard_field', $item);
				return TRUE;
			}
		}
		return FALSE;
	}
}