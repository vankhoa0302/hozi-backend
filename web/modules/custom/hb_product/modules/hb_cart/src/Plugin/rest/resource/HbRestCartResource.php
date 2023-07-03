<?php

namespace Drupal\hb_cart\Plugin\rest\resource;

use Drupal\hb_cart\Entity\HbCart;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
    $service_guard_data = \Drupal::service('hb_guard.data_guard');
    if ($service_guard_data->guardRequiredData([
      'product_id',
      'product_quantity',
    ], $data)) {
      $userData = \Drupal::service('user.data');
      return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
    }

    if (!$service_guard_data->existEntity(HbProduct::class, $data['product_id'])) {
      return new JsonResponse(['message' => 'Product not found!'], 404);
    }

    $product = HbProduct::load($data['product_id']);
    $carts = \Drupal::entityTypeManager()->getStorage('hb_cart')->loadByProperties([
      'bundle' => 'furniture',
      'uid' => \Drupal::currentUser()->id(),
    ]);
    if (empty($carts)) {
      $furniture_cart = Paragraph::create([
        'type' => 'furniture_cart',
        'field_p_f_c_furniture' => $product->id(),
        'field_p_f_c_quantity' => 0,
      ]);
      $furniture_cart->save();
      $cart = HbCart::create([
        'bundle' => 'furniture',
        'label' => 'Cart ' . $furniture_cart->id(),
        'field_c_f_furniture' => [
          0 => [
            'target_id' => $furniture_cart->id(),
            'target_revision_id' => $furniture_cart->getRevisionId()
          ]
        ],
      ]);
      $cart->save();
    } else {
      $cart = reset($carts);
    }

    $furniture = !empty($cart->get('field_c_f_furniture')) ? $cart->get('field_c_f_furniture')->getValue() : [];
    $result = [];
    $updated = FALSE;
    foreach ($furniture as $item) {
      $furniture_cart = Paragraph::load($item['target_id']);
      if ($furniture_cart->get('field_p_f_c_furniture')->target_id == $data['product_id']) {
        $field_p_f_c_quantity = $furniture_cart->get('field_p_f_c_quantity')->value;
        if ($field_p_f_c_quantity + $data['product_quantity'] <= 0 or
          $field_p_f_c_quantity + $data['product_quantity'] > $product->get('field_p_f_quantity')->getString()) {
          return new JsonResponse(['message' => 'invalid product_quantity!'], 400);
        }
        $furniture_cart->set('field_p_f_c_quantity', $field_p_f_c_quantity + $data['product_quantity']);
        $furniture_cart->save();

        $updated = TRUE;
      }
    }

    if (!$updated) {
      $furniture_cart = Paragraph::create([
        'type' => 'furniture_cart',
        'field_p_f_c_furniture' => $product->id(),
        'field_p_f_c_quantity' => $data['product_quantity'],
      ]);
      $furniture_cart->save();
      $furniture[] = [
        'target_id' => $furniture_cart->id(),
        'target_revision_id' => $furniture_cart->getRevisionId()
      ];
      $cart->set('field_c_f_furniture', $furniture)->save();
    }

    $result['results'] = [
      'success' => true,
      'cart_count_item' => count($furniture),
    ];
    return new JsonResponse($result, 200);
  }

  public function delete(Request $request) {
    $carts = \Drupal::entityTypeManager()->getStorage('hb_cart')->loadByProperties([
      'bundle' => 'furniture',
      'uid' => \Drupal::currentUser()->id(),
    ]);
    if (!empty($carts)) {
      $cart = reset($carts);
      if (\Drupal::currentUser()->id() != $cart->getOwner()->id()) {
        return new JsonResponse(['message' => 'Forbidden!'], 403);
      }
      if (!empty($request->get('product_id'))) {
        $service_guard_data = \Drupal::service('hb_guard.data_guard');
        if (!$service_guard_data->existEntity(HbProduct::class, $request->get('product_id'))) {
          return new JsonResponse(['message' => 'Product not found!'], 404);
        }
        $furniture = !empty($cart->get('field_c_f_furniture')) ? $cart->get('field_c_f_furniture')->getValue() : [];
        foreach ($furniture as $item) {
          $furniture_cart = Paragraph::load($item['target_id']);
          if ($furniture_cart->get('field_p_f_c_furniture')->target_id == $request->get('product_id')) {
            $furniture_cart->delete();
            $furniture = !empty($cart->get('field_c_f_furniture')) ? $cart->get('field_c_f_furniture')->getValue() : [];
            $cart->set('field_c_f_furniture', $furniture)->save();
          }
        }

      } else {
        $cart->delete();
      }
      return new JsonResponse(['message' => 'Success!'], 200);
    }
    return new JsonResponse(['message' => 'Cart is empty!'], 400);
  }
}
