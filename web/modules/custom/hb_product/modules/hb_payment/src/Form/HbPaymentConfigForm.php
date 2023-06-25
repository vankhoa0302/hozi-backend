<?php

namespace Drupal\hb_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class HbPaymentConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'hb_payment.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['redirect_after_payment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect after payment', [], ['langcode' => 'en']),
      '#default_value' => $config->get('redirect_after_payment'),
    ];

    $form['expired_time'] = [
      '#type' => 'number',
      '#suffix' => t('Minutes'),
      '#min' => 0,
      '#title' => $this->t('Expired time', [], ['langcode' => 'en']),
      '#default_value' => $config->get('expired_time'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('redirect_after_payment', $form_state->getValue('redirect_after_payment'))
      ->set('expired_time', $form_state->getValue('expired_time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
