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
			if (!isset($required_data[$item])) {
				$userData = \Drupal::service('user.data');
				$userData->set('hb_guard', \Drupal::currentUser()->id(), 'guard_field', $item);
				return FALSE;
			}
		}
		return TRUE;
	}
}