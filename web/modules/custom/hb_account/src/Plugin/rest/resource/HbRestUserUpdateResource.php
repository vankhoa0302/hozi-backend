<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents RestUserUpdate records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user_update",
 *   label = @Translation("User Update"),
 *   uri_paths = {
 *     "canonical" = "/api/user"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserUpdateResource extends ResourceBase {

  const FIELD_ADDRESS = 'field_user_address';
  const FIELD_PICTURE = 'user_picture';

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

    if (isset($data['address'])) {
      $user->set(self::FIELD_ADDRESS, $data['address']);
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

    if (!isset($data['address']) and
      !isset($data['picture']) and
      !isset($data['old_pass'])) {
      return new JsonResponse(['message' => 'Forbidden!'], 403);
    }

    try {
      $user->save();
    } catch (\Exception $e) {
      return new JsonResponse(['message' => $e->getMessage()], 500);
    }

    $view = Views::getView('get_information_user_via_bearer_token');
    $view->setDisplay('rest_export_user');
    $view->execute();
    $content = $view->buildRenderable('rest_export_user');
    foreach ($content['#view']->result as $row_index => $row) {
      $content['#view']->row_index = $row_index;
      $results = $content['#view']->rowPlugin->render($row);
    }

    unset($content['#view']->row_index);

    return new JsonResponse([
      'results' => $results ?? [],
      'message' => 'Success!'
    ], 200);
  }
}
