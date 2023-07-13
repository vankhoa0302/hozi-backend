<?php

namespace Drupal\hb_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\paragraphs\Entity\Paragraph;
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
    $fields = $rows[0];
    unset($rows[0]);
    try {
      $base_values = [
        'bundle' => 'furniture',
        'status' => TRUE,
      ];
      foreach ($rows as $values) {
        foreach ($values as $index => $value) {
          if ($fields[$index] === 'description__value') {
            $fields[$index] = 'description';
          }
          if ($fields[$index] === 'field_p_f_c_type') {
            $furniture_category = Paragraph::create([
              'type' => 'furniture_category',
              'field_p_f_c_type' => $value,
            ]);
            $furniture_category->save();
            $fields[$index] = 'field_p_f_attributes';
            $value = [ 0 => [
              'target_id' => $furniture_category->id(),
              'target_revision_id' => $furniture_category->getRevisionId(),
            ]];
          }
          if ($fields[$index] === 'field_p_f_media') {
            $file = File::create([
              'filename' => 'Ghế',
              'uri' => $value,
              'status' => 1,
              'uid' => 1,
            ]);
            $file->setPermanent(TRUE);
            $file->save();
            $value = [$file->id()];
          }

          $base_values[$fields[$index]] = $value;
        }
        if (is_string($base_values['field_p_f_attributes'])) {
          $furniture_category = Paragraph::create([
            'type' => 'furniture_category',
            'field_p_f_c_type' => $base_values['field_p_f_attributes'],
          ]);
          $furniture_category->save();
          $base_values['field_p_f_attributes'] = [ 0 => [
            'target_id' => $furniture_category->id(),
            'target_revision_id' => $furniture_category->getRevisionId(),
          ]];
        }
        $product = HbProduct::create($base_values);
        $product->save();
      }
    } catch (\Exception $exception) {
      \Drupal::logger('hb_product')->error($exception->getMessage());
      \Drupal::messenger()->addError($exception->getMessage());
    }
    $form_state->setRedirect('entity.hb_product.collection');
  }

}
