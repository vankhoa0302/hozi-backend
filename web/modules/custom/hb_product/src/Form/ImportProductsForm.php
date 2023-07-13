<?php

namespace Drupal\hb_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Provides a hb_product form.
 */
class ImportProductsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hb_product_import_products';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File (.xlsx)'),
      '#upload_location' => 'private://products',
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Imprt'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('file') == NULL) {
      $form_state->setErrorByName('file', $this->t('This file can not empty'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue('file');
    $file = File::load(reset($file));
    $inputFileName = \Drupal::service('file_system')
      ->realpath('private://products/' . $file->get('filename')->getString());


    $spreadsheet = IOFactory::load($inputFileName);

    $sheetData = $spreadsheet->getActiveSheet();

    $rows = [];
    foreach ($sheetData->getRowIterator() as $row) {
      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(FALSE);
      $cells = [];
      foreach ($cellIterator as $cell) {
        $cells[] = $cell->getValue();


      }
      $rows[] = $cells;

    }
    $form_state->setRedirect('entity.hb_product.collection');
  }

}
