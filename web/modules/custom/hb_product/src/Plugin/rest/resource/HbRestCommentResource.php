<?php

namespace Drupal\hb_product\Plugin\rest\resource;

use Drupal\comment\Entity\Comment;
use Drupal\hb_guard\Services\HbDataGuardService;
use Drupal\hb_product\Entity\HbProduct;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents Comment records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_comment",
 *   label = @Translation("Comment"),
 *   uri_paths = {
 *     "canonical" = "/api/product/{pid}/comment/{cid}",
 *     "create" = "/api/product/{pid}/comment"
 *   }
 * )
 *
 */
class HbRestCommentResource extends ResourceBase {

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param array $data
   *   Data to write into the database.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function post($pid, array $data) {
    /** @var HbDataGuardService $data_guard_service */
    $data_guard_service = \Drupal::service('hb_guard.data_guard');
    if (!$data_guard_service->existEntity(HbProduct::class, $pid)) {
      return new JsonResponse(['message' => t('Product with id :[pid] not found!', [
        ':[pid]' => $pid,
      ])], 400);
    }

    if ($data_guard_service->guardRequiredData([
      'comment',
    ], $data)) {
      $userData = \Drupal::service('user.data');
      return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
    }
    $uid = \Drupal::currentUser()->id();
    $values = [
      'entity_type' => 'hb_product',
      'entity_id' => $pid,
      'field_name' => 'field_p_f_comments',
      'uid' => $uid,
      'comment_type' => 'comments_of_product',
      'subject' => "User {$uid}:",
      'comment_body' => $data['comment'],
      'status' => 1,
    ];
    $comment = Comment::create($values);
    $comment->save();
    return new JsonResponse([
      'message' => t('Success!'),
      'result' => $comment->id(),
    ], 200);
  }


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
  public function patch($pid, $cid, array $data) {
    /** @var HbDataGuardService $data_guard_service */
    $data_guard_service = \Drupal::service('hb_guard.data_guard');
    if (!$data_guard_service->existEntity(HbProduct::class, $pid)) {
      return new JsonResponse(['message' => t('Product with id :[pid] not found!', [
        ':[pid]' => $pid,
      ])], 400);
    }

    if (!$data_guard_service->existEntity(Comment::class, $cid)) {
      return new JsonResponse(['message' => t('Comment with id :[cid] not found!', [
        ':[cid]' => $cid,
      ])], 400);
    }

    $comment = Comment::load($cid);

    if (!\Drupal::service('hb_product.comment')->isOwner($comment)) {
      return new JsonResponse(['message' => t('Forbidden!')], 403);
    }

    if ($data_guard_service->guardRequiredData([
      'comment',
    ], $data)) {
      $userData = \Drupal::service('user.data');
      return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
    }
    $comment->set('comment_body', $data['comment'])->save();

    return new JsonResponse([
      'message' => t('Success!'),
      'result' => $comment->id(),
    ], 200);
  }

  /**
   * Responds to DELETE requests.
   *
   * @param int $id
   *   The ID of the record.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function delete($pid, $cid) {
    /** @var HbDataGuardService $data_guard_service */
    $data_guard_service = \Drupal::service('hb_guard.data_guard');
    if (!$data_guard_service->existEntity(HbProduct::class, $pid)) {
      return new JsonResponse(['message' => t('Product with id :[pid] not found!', [
        ':[pid]' => $pid,
      ])], 400);
    }

    if (!$data_guard_service->existEntity(Comment::class, $cid)) {
      return new JsonResponse(['message' => t('Comment with id :[cid] not found!', [
        ':[cid]' => $cid,
      ])], 400);
    }

    $comment = Comment::load($cid);

    if (!\Drupal::service('hb_product.comment')->isOwner($comment)) {
      return new JsonResponse(['message' => t('Forbidden!')], 403);
    }

    $comment->delete();

    return new JsonResponse([
      'message' => t('Success!'),
    ], 200);
  }

}
