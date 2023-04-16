<?php

namespace Drupal\hb_message\Services;

use Drupal\hb_message\Entity\HbMessage;

/**
 * Class HbSendEmailService
 * @package Drupal\hb_message\Services
 */
class HbSendEmailService {

  const MESSAGE_INIT_ARR = [
    'subject' => 'Something\'s wrong!',
    'body' => 'Please contact administrator!'
  ];

  public static function send(string $mail, string $template, array $values) {

    $base_fields = array_keys(\Drupal::service('entity_field.manager')->getFieldStorageDefinitions('hb_message'));

    foreach ($base_fields as $field_machine_name) {
      if (str_ends_with($field_machine_name, '_body')) {
        $body = $field_machine_name;
      }
      if (str_ends_with($field_machine_name, '_subject')) {
        $subject = $field_machine_name;
      }
    }

    $message_values = array_merge([
      'label' => $template . ' ' . $mail . ' ' . date('D-m-Y H:i:s', \Drupal::time()->getRequestTime()),
      'bundle' => $template,
      'status' => TRUE,
      'langcode' => $values['langcode'],
      'uid' => user_load_by_mail($mail)->id(),
    ], $values['params']);

    HbMessage::create($message_values)->save();
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $message = $mail_manager->mail('phpmailer_smtp', $template, $mail, $values['langcode'], $values['params'], NULL, FALSE);

    $message = array_merge($message, self::MESSAGE_INIT_ARR);

    if (!empty($subject) and !empty($body)) {
      $message['subject'] = $values['params'][$subject];
      $message['body'] = $values['params'][$body];
    }

    $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
    $phpMailerSmtp = $mail_manager->createInstance('phpmailer_smtp');
    $phpMailerSmtp->mail($message);
  }
}
