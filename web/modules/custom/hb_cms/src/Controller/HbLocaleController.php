<?php

namespace Drupal\hb_cms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\locale\Controller\LocaleController;

/**
 * Return response for manual check translations.
 */
class HbLocaleController extends LocaleController {
	/**
	 * Shows the string search screen.
	 *
	 * @return array
	 *   The render array for the string search screen.
	 */
	public function translatePage() {
		return [
			'filter' => $this->formBuilder()->getForm('Drupal\hb_cms\Form\HbTranslateFilterForm'),
			'form'   => $this->formBuilder()->getForm('Drupal\hb_cms\Form\HbTranslateEditForm'),
		];
	}
}