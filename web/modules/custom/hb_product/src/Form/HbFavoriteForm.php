<?php

namespace Drupal\hb_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the favorite entity edit forms.
 */
class HbFavoriteForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New favorite %label has been created.', $message_arguments));
        $this->logger('hb_product')->notice('Created new favorite %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The favorite %label has been updated.', $message_arguments));
        $this->logger('hb_product')->notice('Updated favorite %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.hb_favorite.canonical', ['hb_favorite' => $entity->id()]);

    return $result;
  }

}