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
 *   label = @Translation("RestUserRegister"),
 *   uri_paths = {
 *     "canonical" = "/api/user-register/{id}",
 *     "create" = "/api/user-register"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserRegisterResource extends ResourceBase
{

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array           $configuration,
                        $plugin_id,
                        $plugin_definition,
        array           $serializer_formats,
        LoggerInterface $logger,
    )
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('rest'),
        );
    }

    /**
     * Responds to POST requests and saves the new record.
     *
     * @param array $data
     *   Data to write into the database.
     *
     * @return JsonResponse
     *   The HTTP response object.
     */
    public function post(array $data): JsonResponse
    {
        if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
            'mail',
            'name',
            'pass'
        ], $data)) {
            return new JsonResponse(['message' => 'Missing data!'], 400);
        }
        try {
            $exist_user = \Drupal::entityTypeManager()
                ->getStorage('user')
                ->loadByProperties([
                    'mail' => $data['mail'],
                ]);
            if ($exist_user) {
                return new JsonResponse(['message' => 'User exist!'], 406);
            }
            User::create([
                'mail' => $data['mail'],
                'name' => $data['name'],
                'pass' => $data['password'],
                'status' => 1,
            ])->save();
        } catch (\Exception $e) {
            $this->logger->error($e);
            return new JsonResponse([], 500);
        }
        $this->logger->notice('Create new user @mail success.', ['@mail' => $data['mail']]);
        return new JsonResponse(['message' => 'Success!'], 200);
    }

    /**
     * Responds to GET requests.
     *
     * @param int $id
     *   The ID of the record.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The response containing the record.
     */
    public function get($id)
    {
        if (!$this->storage->has($id)) {
            throw new NotFoundHttpException();
        }
        $resource = $this->storage->get($id);
        return new ResourceResponse($resource);
    }

    /**
     * Responds to PATCH requests.
     *
     * @param int $id
     *   The ID of the record.
     * @param array $data
     *   Data to write into the storage.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     */
    public function patch($id, array $data)
    {
        if (!$this->storage->has($id)) {
            throw new NotFoundHttpException();
        }
        $stored_data = $this->storage->get($id);
        $data += $stored_data;
        $this->storage->set($id, $data);
        $this->logger->notice('The restuserregister record @id has been updated.');
        return new ModifiedResourceResponse($data, 200);
    }

    /**
     * Responds to DELETE requests.
     *
     * @param int $id
     *   The ID of the record.
     *
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     */
    public function delete($id)
    {
        if (!$this->storage->has($id)) {
            throw new NotFoundHttpException();
        }
        $this->storage->delete($id);
        $this->logger->notice('The restuserregister record @id has been deleted.', ['@id' => $id]);
        // Deleted responses have an empty body.
        return new ModifiedResourceResponse(NULL, 204);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBaseRoute($canonical_path, $method)
    {
        $route = parent::getBaseRoute($canonical_path, $method);
        // Set ID validation pattern.
        if ($method != 'POST') {
            $route->setRequirement('id', '\d+');
        }
        return $route;
    }

    /**
     * Returns next available ID.
     */
    private function getNextId()
    {
        $ids = \array_keys($this->storage->getAll());
        return count($ids) > 0 ? max($ids) + 1 : 1;
    }

}
