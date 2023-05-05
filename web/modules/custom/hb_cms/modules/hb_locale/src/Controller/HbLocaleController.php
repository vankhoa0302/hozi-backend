<?php

namespace Drupal\hb_locale\Controller;

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
			'filter' => $this->formBuilder()->getForm('Drupal\hb_locale\Form\HbTranslateFilterForm'),
			'form'   => $this->formBuilder()->getForm('Drupal\hb_locale\Form\HbTranslateEditForm'),
		];
	}
}
