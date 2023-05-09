<?php

namespace Drupal\hb_favorite\Plugin\rest\resource;

use Drupal\hb_favorite\Entity\HbFavorite;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents Favorite records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_favorite",
 *   label = @Translation("Favorite"),
 *   uri_paths = {
 *     "create" = "/api/favorite"
 *   }
 * )
 *
 */
class HbRestFavoriteResource extends ResourceBase {

	/**
	 * Responds to POST requests and saves the new record.
	 *
	 * @param array $data
	 *   Data to write into the database.
	 *
	 * @return JsonResponse
	 *   The HTTP response object.
	 */
	public function post(array $data) {
		$favorite_products = \Drupal::entityTypeManager()->getStorage('hb_favorite')->loadByProperties([
			'bundle' => 'furniture',
			'uid'    => \Drupal::currentUser()->id(),
		]);
		if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
			'product_id',
		], $data)) {
			$userData = \Drupal::service('user.data');
			return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
		}
		if (empty($favorite_products)) {
			$favorite_product = HbFavorite::create([
				'bundle' => 'furniture',
			]);
			$favorite_product->save();
		} else {
			$favorite_product = reset($favorite_products);
		}
		$furniture = !empty($favorite_product->get('field_f_f_furniture')) ? $favorite_product->get('field_f_f_furniture')->getValue() : [];
		$furniture = array_map(function ($f) {
			return $f['target_id'];
		}, $furniture);
		if (in_array($data['product_id'], $furniture)) {
			$furniture = array_diff($furniture, array_intersect($furniture, [$data['product_id']]));
		} else {
			$furniture[] = $data['product_id'];
		}

		$favorite_product->set('field_f_f_furniture', $furniture)->save();
		return new JsonResponse([
      'message' => 'Success!',
      'results' => TRUE,
    ], 200);
	}

}
