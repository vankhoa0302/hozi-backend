<?php

namespace Drupal\hb_cms\Plugin\rest\resource;

use Drupal\locale\StringDatabaseStorage;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents ParagraphTypeChildren records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_translation",
 *   label = @Translation("Translation"),
 *   uri_paths = {
 *     "canonical" = "/api/translate",
 *   }
 * )
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestTranslationResource extends ResourceBase {
	/**
	 * Responds to POST requests and saves the new record.
	 *
	 * @param array $data
	 *   Data to write into the database.
	 *
	 * @return JsonResponse
	 *   The HTTP response object.
	 */
	public function patch(Request $request, array $data): JsonResponse {
		/** @var StringDatabaseStorage $string_manager */
		$string_manager = \Drupal::service('locale.storage');
		$result = [];
		foreach ($data as $string) {
			$existing_translation = $string_manager->findTranslation(['source' => $string]);
			$result[$string] = $existing_translation ? $existing_translation->getString() : $string;
		}
		return new JsonResponse(['results' => $result], 200);
	}

}