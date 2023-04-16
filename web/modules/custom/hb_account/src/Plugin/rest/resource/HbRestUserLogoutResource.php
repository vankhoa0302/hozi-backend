<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents User Logout records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user_logout",
 *   label = @Translation("User Logout"),
 *   uri_paths = {
 *     "create" = "/api/user/logout"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserLogoutResource extends ResourceBase {

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */

  public function post(): JsonResponse {
    $user = User::load(\Drupal::currentUser()->id());
    $collector = \Drupal::service('simple_oauth.expired_collector');

    $access_tokens = $collector->collectForAccount($user, TRUE);
    $refresh_tokens = $this->collectRefreshTokens($user);
    $collector->deleteMultipleTokens($access_tokens);
    $collector->deleteMultipleTokens($refresh_tokens);
    \Drupal::service('session_manager')->delete($user->id());

    $this->logger->notice('User @user has logged out.', ['@user' => $user->label()]);
    return new JsonResponse(['message' => 'Logout success!'], 200);
  }

  /**
   * @param User $user
   *
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function collectRefreshTokens(User $user) {
    $tokenStorage = \Drupal::entityTypeManager()->getStorage('oauth2_token');
    $query = $tokenStorage->getQuery();
    $query->condition('auth_user_id', $user->id());
    $query->condition('bundle', 'refresh_token');
    $query->accessCheck();
    $entity_ids = $query->execute();

    $output = $entity_ids
      ? array_values($tokenStorage->loadMultiple(array_values($entity_ids)))
      : [];

    return array_values($output);
  }


}
