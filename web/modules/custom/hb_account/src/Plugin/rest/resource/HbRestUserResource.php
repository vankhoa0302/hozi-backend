<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents RestUser records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user",
 *   label = @Translation("User info"),
 *   uri_paths = {
 *     "canonical" = "/api/user"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserResource extends ResourceBase {

  private const FIELD_USER_INFO = 'field_user_info';
  private const FIELD_PICTURE = 'user_picture';

  private const FIELD_USER_INFO_ADDRESS = 'field_p_u_c_i_address';
  private const FIELD_USER_INFO_DEFAULT = 'field_p_u_c_i_default';
  private const FIELD_USER_INFO_PHONE_NUMBER = 'field_p_u_c_i_phone_number';
  private const FIELD_USER_INFO_NAME = 'field_p_u_c_i_name';

  public function get() {
    $results = $this->getUserInfo();
    return new JsonResponse($results, 200);
  }

  /**
   * Responds to PATCH requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function patch(array $data): JsonResponse {
    $user = User::load(\Drupal::currentUser()->id());
    $user_info = $user->get(self::FIELD_USER_INFO)->getValue();

    if (isset($data['id']) and $data['id'] > 0) {
      $para = Paragraph::load($data['id']);
      if ($para && $para->getParentEntity()->id() != $user->id()) {
        return new JsonResponse(['message' => 'Forbidden!'], 403);
      }

      if (isset($data['address'])) {
        $para->set(self::FIELD_USER_INFO_ADDRESS, $data['address']);
      }

      if (isset($data['name'])) {
        $para->set(self::FIELD_USER_INFO_NAME, $data['name']);
      }

      if (isset($data['phone_number'])) {
        if (preg_match('/^[0-9]{10,11}$/', $data['phone_number'])) {
          return new JsonResponse(['message' => 'Invalid phone number!'], 400);
        }
        $para->set(self::FIELD_USER_INFO_PHONE_NUMBER, $data['phone_number']);
      }

      if (isset($data['default']) and $data['default']) {
        foreach ($user_info as $item) {
          if ($item->get(self::FIELD_USER_INFO_DEFAULT)->getString()) {
            if ($para->id() == $item['target_id']) {
              $para->set(self::FIELD_USER_INFO_DEFAULT, FALSE);
            } else {
              Paragraph::load($item['target_id'])->set(self::FIELD_USER_INFO_DEFAULT, FALSE)->save();
            }
          }
        }

        $para->set(self::FIELD_USER_INFO_DEFAULT, TRUE);
      }
      $para->save();
    }

    if (isset($data['picture'])) {
      $user->set(self::FIELD_PICTURE, $data['picture']);
    }

    if (isset($data['old_pass'])) {
      $password_hash_er = \Drupal::service('password');
      if (!$password_hash_er->check($data['old_pass'], $user->getPassword())) {
        return new JsonResponse([
          'message' => 'Wrong password!'
        ], 400);
      }

      if (isset($data['new_pass'])) {
        $user->setPassword($data['new_pass']);
      }
    }

    if (!isset($data['id']) and
      !isset($data['picture']) and
      !isset($data['old_pass'])) {
      return new JsonResponse(['message' => 'Forbidden!'], 403);
    }

    try {
      $user->save();
    } catch (\Exception $e) {
      return new JsonResponse(['message' => $e->getMessage()], 500);
    }

    $results = $this->getUserInfo();
    return new JsonResponse([
      'results' => $results,
      'message' => 'Success!'
    ], 200);
  }

  /**
   * @return array
   */
  public function getUserInfo(): array {
    $results = [];
    $user = User::load(\Drupal::currentUser()->id());
    /** @var File $user_picture */
    $user_picture = $user->get(self::FIELD_PICTURE)->entity;
    $results['results'] = [
      'user_id' => $user->id(),
      'user_name' => $user->label(),
      'user_info' => [],
    ];
    if (!empty($user_picture)) {
      $results['results']['user_picture'] = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($user_picture->getFileUri());
    }
    $user_info = $user->get(self::FIELD_USER_INFO)->getValue();
    foreach ($user_info as $item) {
      $para = Paragraph::load($item['target_id']);
      $results['results']['user_info'][$item['target_id']] = [
        'name' => $para->get(self::FIELD_USER_INFO_NAME)->getString(),
        'address' => $para->get(self::FIELD_USER_INFO_ADDRESS)->getString(),
        'phone_number' => $para->get(self::FIELD_USER_INFO_PHONE_NUMBER)?->local_number ?? '',
        'default' => (boolean)$para->get(self::FIELD_USER_INFO_DEFAULT)->getString(),
      ];
    }
    return $results;
  }
}
