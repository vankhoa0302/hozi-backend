<?php

namespace Drupal\hb_product\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\hb_product\Entity\HbFavorite;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\views\Views;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents Favorite records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_favorite",
 *   label = @Translation("Favorite"),
 *   uri_paths = {
 *     "canonical" = "/api/favorite/{id}",
 *     "create" = "/api/favorite"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
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
		$result = [];
		$furniture = array_map(function ($f) {
			return $f['target_id'];
		}, $furniture);
		if (in_array($data['product_id'], $furniture)) {
			$furniture = array_diff($furniture, array_intersect($furniture, [$data['product_id']]));
		} else {
			$furniture[] = $data['product_id'];
		}

		$favorite_product->set('field_f_f_furniture', $furniture)->save();
		$view = Views::getView('favorite');
		$view->setDisplay('rest_export_favorite_products');
		$view->execute();
		$content = $view->buildRenderable('rest_export_favorite_products');
		foreach ($content['#view']->result as $row_index => $row) {
			$content['#view']->row_index = $row_index;
			$rows[] = $content['#view']->rowPlugin->render($row);
		}
		unset($content['#view']->row_index);
		$result['results'] = \Drupal::service('serializer')->normalize($rows, 'json') ?? [];
		$pager = $content['#view']->pager;
		$total = (int) $pager->getTotalItems();
		$result['pager'] = [
			'count'          => $total,
			'pages'          => $pager->getCurrentPage(),
			'items_per_page' => $pager->getItemsPerPage(),
			'current_page'   => $pager->getCurrentPage(),
			'next_page'      => $total > $pager->getItemsPerPage() * ($pager->getCurrentPage() + 1) ? $pager->getCurrentPage() + 1 : $pager->getCurrentPage(),
		];
		return new JsonResponse($result, 200);
	}

}
