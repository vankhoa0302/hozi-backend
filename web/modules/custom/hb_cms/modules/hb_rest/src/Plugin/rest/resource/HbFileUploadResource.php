<?php

namespace Drupal\hb_rest\Plugin\rest\resource;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\rest\resource\FileUploadResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents Upload File records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_file_upload",
 *   label = @Translation("Upload File"),
 *   serialization_class = "Drupal\file\Entity\File",
 *   uri_paths = {
 *     "create" = "/api/upload-file"
 *   }
 * )
 *
 */
class HbFileUploadResource extends FileUploadResource {

  public function post(Request $request, $entity_type_id = NULL, $bundle = NULL, $field_name = NULL) {
    $fileUrlGenerator = \Drupal::service('file_url_generator');

    try {
      $filename = $this->validateAndParseContentDispositionHeader($request);
    } catch (\Error $error) {
      $this->logger->error($error->getMessage() . '</br>' . $error->getTraceAsString());
      return new JsonResponse([
        'message' => 'Can not upload multiple file.',
      ], 500);
    }
    $destination = 'public://webform-files';

    // Check the destination file path is writable.
    if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
      return new JsonResponse([
        'message' => 'Destination file path is not writable',
      ], 500);
    }

    $validators['file_validate_extensions'][0] = 'jpg jpeg png gif svg pdf txt';
    $prepared_filename = $this->prepareFilename($filename, $validators);

    // Create the file.
    $file_uri = "{$destination}/{$prepared_filename}";

    $temp_file_path = $this->streamUploadData($request);

    $file_uri = $this->fileSystem->getDestinationFilename($file_uri, FileSystemInterface::EXISTS_RENAME);

    // Lock based on the prepared file URI.
    $lock_id = $this->generateLockIdFromFileUri($file_uri);

    if (!$this->lock->acquire($lock_id)) {
      return new JsonResponse([
        'message' => sprintf('File "%s" is already locked for writing', $file_uri),
      ], 503);
    }
    // Begin building file entity.
    $file = File::create([]);
    $file->setPermanent();
    $file->setOwnerId($this->currentUser->id());
    $file->setFilename($prepared_filename);
    $file->setMimeType($this->mimeTypeGuesser->guessMimeType($prepared_filename));
    $file->setFileUri($file_uri);
    // Set the size. This is done in File::preSave() but we validate the file
    // before it is saved.
    $file->setSize(@filesize($temp_file_path));

    // Validate the file entity against entity-level validation and field-level
    // Move the file to the correct location after validation. Use
    // FileSystemInterface::EXISTS_ERROR as the file location has already been
    // determined above in FileSystem::getDestinationFilename().
    try {
      $this->fileSystem->move($temp_file_path, $file_uri, FileSystemInterface::EXISTS_REPLACE);
      $file->save();
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage() . '</br>' . $e->getTraceAsString());
      return new JsonResponse([
        'message' => 'Temporary file could not be moved to file location',
      ], 500);
    }


    $this->lock->release($lock_id);

    // 201 Created responses return the newly created entity in the response
    // body. These responses are not cacheable, so we add no cache-ability
    // metadata here.
    $data = [
      'id' => $file->id(),
      'url' => $fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
      'file_name' => $file->getFilename(),
    ];

    return new JsonResponse([
      'results' => $data,
      'message' => 'Created',
    ], 201);
  }

  public function permissions() {
    return ['restful post hb_rest' => ['title' => 'Access POST on Upload File']];
  }


}
