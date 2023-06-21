<?php

namespace Drupal\hb_cart\Plugin\rest\resource;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents Cart records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_cart",
 *   label = @Translation("Cart"),
 *   uri_paths = {
 *     "canonical" = "/api/cart"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestCartResource extends ResourceBase {

	/**
	 * Responds to PATCH requests.
	 *
	 * @param int $id
	 *   The ID of the record.
	 * @param array $data
	 *   Data to write into the storage.
	 *
	 * @return JsonResponse
	 *   The HTTP response object.
	 */
	public function patch(array $data) {
		$carts = \Drupal::entityTypeManager()->getStorage('hb_cart')->loadByProperties([
			'bundle' => 'furniture',
			'uid'    => \Drupal::currentUser()->id(),
		]);
		if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
			'product_id',
			'product_quantity',
		], $data)) {
			$userData = \Drupal::service('user.data');
			return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
		}
		if (empty($carts)) {
			$cart = HbCart::create([
				'bundle' => 'furniture',
			]);
			$cart->save();
		} else {
			$cart = reset($carts);
		}
		$furniture = !empty($cart->get('field_c_f_furniture')) ? $cart->get('field_c_f_furniture')->getValue() : [];
		$result = [];
		foreach ($furniture as $item) {
			$furniture_cart = Paragraph::load($item['target_id']);
			$field_p_f_c_quantity = $furniture_cart->get('field_p_f_c_quantity')?->value;
			$furniture_cart->set('field_p_f_c_quantity', $field_p_f_c_quantity + $data['product_quantity']);
			$furniture_cart->save();
		}
		$view = Views::getView('cart');
		$view->setDisplay('rest_export_cart');
		$view->execute();
		$content = $view->buildRenderable('rest_export_cart');
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