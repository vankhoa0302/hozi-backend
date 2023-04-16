<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents RestUserRegister records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user_register",
 *   label = @Translation("User Register"),
 *   uri_paths = {
 *     "create" = "/api/user/register"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserRegisterResource extends ResourceBase {

	/**
	 * Responds to POST requests and saves the new record.
	 *
	 * @param array $data
	 *   Data to write into the database.
	 *
	 * @return JsonResponse
	 *   The HTTP response object.
	 */
	public function post(array $data): JsonResponse {
		if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
			'mail',
			'name',
			'pass',
		], $data)) {
			$userData = \Drupal::service('user.data');
			return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
		}
		try {
			$exist_user = \Drupal::entityTypeManager()
				->getStorage('user')
				->loadByProperties([
					'mail' => $data['mail'],
				]);
			if ($exist_user) {
				return new JsonResponse(['message' => 'User\'s exist!'], 406);
			}
			User::create([
				'mail'   => $data['mail'],
				'name'   => $data['name'],
				'pass'   => $data['password'],
				'status' => 1,
			])->save();
		} catch (\Exception $e) {
			$this->logger->error($e);
			return new JsonResponse([], 500);
		}
		$this->logger->notice('Create new user @mail success.', ['@mail' => $data['mail']]);
		return new JsonResponse(['message' => 'Success!'], 200);
	}

}
