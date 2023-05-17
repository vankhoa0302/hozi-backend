<?php

namespace Drupal\hb_product\Services;

use Drupal\comment\Entity\Comment;

/**
 * Class HbCommentService
 * @package Drupal\hb_cms\Services
 */
class HbCommentService {

  public function isOwner(Comment $comment): float|int {
    if ($comment->getOwner()->id() == \Drupal::currentUser()->id()) {
      return TRUE;
    }
    return FALSE;
  }

}
