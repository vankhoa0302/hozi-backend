<?php

namespace Drupal\hb_paragraphs\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents ParagraphTypeChildren records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_paragraph_type_children",
 *   label = @Translation("Paragraph Type Children"),
 *   uri_paths = {
 *     "canonical" = "/api/paragraph/{type}",
 *     "create" = "/api/hb-rest-paragraph-type-children"
 *   }
 * )
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestParagraphTypeChildrenResource extends ResourceBase {

	/**
	 * Responds to GET requests.
	 *
	 * @param int $id
	 *   The ID of the record.
	 *
	 * @return JsonResponse
	 *   The response containing the record.
	 */
	public function get($type) {
		$fieldDefinition = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('paragraph', $type);

		if (!isset($fieldDefinition['field_p_f_c_type'])) {
			throw new NotFoundHttpException($type . ' not found!');
		}

    $resources = $fieldDefinition['field_p_f_c_type']->getSetting('allowed_values');
    $results = [];
    $images = [];
    $medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
      'field_m_i_category' => 'hb_furniture_category'
    ]);
    foreach ($medias as $media) {
      $file = $media->get('field_media_image')->entity;
      $image_uri = $file->getFileUri();
      $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_uri);
      $images[strtok($media->label(), '.')] = $image_url;
    }
    foreach ($resources as $key => $resource) {
      $results[] = (object) [
        'id' => $key,
        'typeName' => $resource,
        'image' => $images[$key],
      ];
    }
		return new JsonResponse(['results' => $results], 200);
	}

}
