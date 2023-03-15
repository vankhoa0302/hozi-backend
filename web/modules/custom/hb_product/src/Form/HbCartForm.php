<?php

namespace Drupal\hb_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the hb_cart entity edit forms.
 */
class HbCartForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New hb_cart %label has been created.', $message_arguments));
        $this->logger('hb_product')->notice('Created new hb_cart %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The hb_cart %label has been updated.', $message_arguments));
        $this->logger('hb_product')->notice('Updated hb_cart %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.hb_cart.canonical', ['hb_cart' => $entity->id()]);

    return $result;
  }

}
