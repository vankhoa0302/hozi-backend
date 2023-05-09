<?php

namespace Drupal\hb_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hb_payment\HbPaymentFactory;
use Drupal\hb_payment\HbPaymentManager;
use Drupal\hb_payment\Services\HbPaymentValidate;

/**
 * Form handler for payments.
 */
class HbPaymentProgressForm extends FormBase{

  public function getFormId() {
    return 'hb_product_payment';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $payment_manager = new HbPaymentManager();

    /** @var HbPaymentValidate $service_validate */
    $service_validate = \Drupal::service('hb_payment.validate');
    $valid_signature = $service_validate->validSignature();

    if (!$valid_signature) {
      return $payment_manager->initForm();
    }

    return $payment_manager->resultForm($this->getRequest());
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $payment_factory= new HbPaymentFactory();
    $method = $form_state->getValue('method');
    $amount = $form_state->getValue('amount');
    $form_state->set('method', $method);
    $form_state->set('amount', $amount);
    $payment_factory->pay($method, [
      'cart_id' => \Drupal::routeMatch()->getParameter('cart_id'),
      'amount' => $amount
    ]);
  }

}
