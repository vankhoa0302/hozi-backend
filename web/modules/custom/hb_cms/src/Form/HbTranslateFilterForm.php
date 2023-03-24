<?php

namespace Drupal\hb_cms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\locale\Form\TranslateFilterForm;
use Drupal\locale\SourceString;
use Drupal\locale\StringStorageException;

/**
 * Provides a filtered translation edit form.
 *
 * @internal
 */
class HbTranslateFilterForm extends TranslateFilterForm {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$filters = $this->translateFilters();
		$filter_values = $this->translateFilterValues();
		$form['#attached']['library'][] = 'locale/drupal.locale.admin';

		$form['filters'] = [
			'#type'       => 'details',
			'#title'      => $this->t('Filter translatable strings'),
			'#open'       => TRUE,
			'#attributes' => ['class' => ['clearfix']],
		];
		foreach ($filters as $key => $filter) {
			// Special case for 'string' filter.
			if ($key == 'string') {
				$form['filters']['status']['string'] = [
					'#type'          => 'search',
					'#title'         => $filter['title'],
					'#description'   => $filter['description'],
					'#default_value' => $filter_values[$key],
				];
			} else {
				$empty_option = $filter['options'][$filter['default']] ?? '- None -';
				$form['filters']['status'][$key] = [
					'#title'         => $filter['title'],
					'#type'          => 'select',
					'#empty_value'   => $filter['default'],
					'#empty_option'  => $empty_option,
					'#size'          => 0,
					'#options'       => $filter['options'],
					'#default_value' => $filter_values[$key],
				];
				if (isset($filter['states'])) {
					$form['filters']['status'][$key]['#states'] = $filter['states'];
				}
			}
		}
		$form['filters']['actions'] = [
			'#type'       => 'actions',
			'#attributes' => ['class' => ['container-inline']],
		];
		$form['filters']['actions']['submit'] = [
			'#type'  => 'submit',
			'#value' => $this->t('Search'),
		];
		$form['filters']['actions']['add'] = [
			'#type'   => 'submit',
			'#value'  => $this->t('Add'),
			'#submit' => [[$this, 'addMoreHandler']],
		];
		if ($this->getRequest()->getSession()->has('locale_translate_filter')) {
			$form['filters']['actions']['reset'] = [
				'#type'   => 'submit',
				'#value'  => $this->t('Reset'),
				'#submit' => ['::resetForm'],
			];
		}

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$filters = $this->translateFilters();

		$session_filters = $this->getRequest()->getSession()->get('locale_translate_filter', []);
		foreach ($filters as $name => $filter) {
			if ($form_state->hasValue($name)) {
				$session_filters[$name] = trim($form_state->getValue($name));
			}
		}
		$this->getRequest()->getSession()->set('locale_translate_filter', $session_filters);
		$form_state->setRedirect('locale.translate_page');
	}

	/**
	 * {@inheritdoc}
	 */
	public function addMoreHandler(&$form, FormStateInterface $form_state) {
		$filter_values = $this->translateFilterValues();
		if (empty($this->localeStorage->findString(['source' => $filter_values['string']]))) {
			$new_string = new SourceString();
			$new_string->setString($filter_values['string']);
			$new_string->setStorage($this->localeStorage);
			$new_string->save();
			try {
				$this->localeStorage->createTranslation([
					'lid'    => $new_string->lid,
					'source' => $new_string->source,
				])->save();
			} catch (\Exception $e) {
				\Drupal::logger('hb_cms')->error($e->getMessage());
			}
		}
		$this->submitForm($form, $form_state);
	}
}