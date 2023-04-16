<?php

namespace Drupal\hb_devel\Plugin\DevelGenerate;

use Drupal\comment\CommentManagerInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\devel_generate\Plugin\DevelGenerate\ContentDevelGenerate;
use Drupal\field\Entity\FieldConfig;
use Drupal\hb_product\HbcartInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\PathAliasStorage;
use Drupal\user\UserStorageInterface;
use Drush\Utils\StringUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ContentDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "cart",
 *   label = @Translation("cart"),
 *   description = @Translation("Generate a given number of cart. Optionally delete current cart."),
 *   url = "cart",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "max_comments" = 0,
 *     "title_length" = 4,
 *     "add_type_label" = FALSE
 *   },
 * )
 */
class CartDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

    /**
     * The node storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $cartStorage;

    /**
     * The node type storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $cartTypeStorage;

    /**
     * The user storage.
     *
     * @var \Drupal\user\UserStorageInterface
     */
    protected $userStorage;

    /**
     * The module handler.
     *
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * The comment manager service.
     *
     * @var \Drupal\comment\CommentManagerInterface
     */
    protected $commentManager;

    /**
     * The language manager.
     *
     * @var \Drupal\Core\Language\LanguageManagerInterface
     */
    protected $languageManager;

    /**
     * The content translation manager.
     *
     * @var \Drupal\content_translation\ContentTranslationManagerInterface
     */
    protected $contentTranslationManager;

    /**
     * The url generator service.
     *
     * @var \Drupal\Core\Routing\UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * The alias storage.
     *
     * @var \Drupal\path_alias\PathAliasStorage
     */
    protected $aliasStorage;

    /**
     * The date formatter service.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * The Drush batch flag.
     *
     * @var bool
     */
    protected $drushBatch;

    /**
     * Provides system time.
     *
     * @var \Drupal\Core\Datetime\DrupalDateTime
     */
    protected $time;

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * The construct.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin ID for the plugin instance.
     * @param array $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Entity\EntityStorageInterface $cart_storage
     *   The node storage.
     * @param \Drupal\Core\Entity\EntityStorageInterface $cart_type_storage
     *   The node type storage.
     * @param \Drupal\user\UserStorageInterface $user_storage
     *   The user storage.
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   The module handler.
     * @param \Drupal\comment\CommentManagerInterface $comment_manager
     *   The comment manager service.
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     *   The language manager.
     * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
     *   The content translation manager service.
     * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
     *   The url generator service.
     * @param \Drupal\path_alias\PathAliasStorage $alias_storage
     *   The alias storage.
     * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
     *   The date formatter service.
     * @param \Drupal\Core\Datetime\DrupalDateTime $time
     *   Provides system time.
     * @param \Drupal\Core\Database\Connection $database
     *   Database connection.
     */
    public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $cart_storage, EntityStorageInterface $cart_type_storage, UserStorageInterface $user_storage, ModuleHandlerInterface $module_handler, CommentManagerInterface $comment_manager = NULL, LanguageManagerInterface $language_manager, ContentTranslationManagerInterface $content_translation_manager = NULL, UrlGeneratorInterface $url_generator, PathAliasStorage $alias_storage, DateFormatterInterface $date_formatter, Time $time, Connection $database) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        $this->moduleHandler = $module_handler;
        $this->cartStorage = $cart_storage;
        $this->cartTypeStorage = $cart_type_storage;
        $this->userStorage = $user_storage;
        $this->commentManager = $comment_manager;
        $this->languageManager = $language_manager;
        $this->contentTranslationManager = $content_translation_manager;
        $this->urlGenerator = $url_generator;
        $this->aliasStorage = $alias_storage;
        $this->dateFormatter = $date_formatter;
        $this->time = $time;
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        $entity_type_manager = $container->get('entity_type.manager');
        return new static(
            $configuration, $plugin_id, $plugin_definition,
            $entity_type_manager->getStorage('hb_cart'),
            $entity_type_manager->getStorage('hb_cart_type'),
            $entity_type_manager->getStorage('user'),
            $container->get('module_handler'),
            $container->has('comment.manager') ? $container->get('comment.manager') : NULL,
            $container->get('language_manager'),
            $container->has('content_translation.manager') ? $container->get('content_translation.manager') : NULL,
            $container->get('url_generator'),
            $entity_type_manager->getStorage('path_alias'),
            $container->get('date.formatter'),
            $container->get('datetime.time'),
            $container->get('database')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $types = $this->cartTypeStorage->loadMultiple();
        if (empty($types)) {
            $create_url = $this->urlGenerator->generateFromRoute('hb_cart.type_add');
            $this->setMessage($this->t('You do not have any cart types that can be generated. <a href=":create-type">Go create a new cart type</a>', [':create-type' => $create_url]), 'error', FALSE);
            return;
        }

        $options = [];

        foreach ($types as $type) {
            $options[$type->id()] = [
                'type' => ['#markup' => $type->label()],
            ];
            if ($this->commentManager) {
                $comment_fields = $this->commentManager->getFields('hb_cart');
                $map = [$this->t('Hidden'), $this->t('Closed'), $this->t('Open')];

                $fields = [];
                foreach ($comment_fields as $field_name => $info) {
                    // Find all comment fields for the bundle.
                    if (in_array($type->id(), $info['bundles'])) {
                        $instance = FieldConfig::loadByName('hb_cart', $type->id(), $field_name);
                        $default_value = $instance->getDefaultValueLiteral();
                        $default_mode = reset($default_value);
                        $fields[] = new FormattableMarkup('@field: @state', [
                            '@field' => $instance->label(),
                            '@state' => $map[$default_mode['status']],
                        ]);
                    }
                }
                // @todo Refactor display of comment fields.
                if (!empty($fields)) {
                    $options[$type->id()]['comments'] = [
                        'data' => [
                            '#theme' => 'item_list',
                            '#items' => $fields,
                        ],
                    ];
                }
                else {
                    $options[$type->id()]['comments'] = $this->t('No comment fields');
                }
            }
        }

        $header = [
            'type' => $this->t('cart type'),
        ];
        if ($this->commentManager) {
            $header['comments'] = [
                'data' => $this->t('Comments'),
                'class' => [RESPONSIVE_PRIORITY_MEDIUM],
            ];
        }

        $form['cart_types'] = [
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $options,
        ];

        $form['kill'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('<strong>Delete all content</strong> in these content types before generating new content.'),
            '#default_value' => $this->getSetting('kill'),
        ];
        $form['num'] = [
            '#type' => 'number',
            '#title' => $this->t('How many nodes would you like to generate?'),
            '#default_value' => $this->getSetting('num'),
            '#required' => TRUE,
            '#min' => 0,
        ];

        $options = [1 => $this->t('Now')];
        foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
            $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
        }
        $form['time_range'] = [
            '#type' => 'select',
            '#title' => $this->t('How far back in time should the nodes be dated?'),
            '#description' => $this->t('Node creation dates will be distributed randomly from the current time, back to the selected time.'),
            '#options' => $options,
            '#default_value' => 604800,
        ];

        $form['max_comments'] = [
            '#type' => $this->moduleHandler->moduleExists('comment') ? 'number' : 'value',
            '#title' => $this->t('Maximum number of comments per node.'),
            '#description' => $this->t('You must also enable comments for the content types you are generating. Note that some nodes will randomly receive zero comments. Some will receive the max.'),
            '#default_value' => $this->getSetting('max_comments'),
            '#min' => 0,
            '#access' => $this->moduleHandler->moduleExists('comment'),
        ];
        $form['title_length'] = [
            '#type' => 'number',
            '#title' => $this->t('Maximum number of words in titles'),
            '#default_value' => $this->getSetting('title_length'),
            '#required' => TRUE,
            '#min' => 1,
            '#max' => 255,
        ];
        $form['skip_fields'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Fields to leave empty'),
            '#description' => $this->t('Enter the field names as a comma-separated list. These will be skipped and have a default value in the generated content.'),
            '#default_value' => NULL,
        ];
        $form['base_fields'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Base fields to populate'),
            '#description' => $this->t('Enter the field names as a comma-separated list. These will be populated.'),
            '#default_value' => NULL,
        ];
        $form['add_type_label'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Prefix the title with the content type label.'),
            '#description' => $this->t('This will not count against the maximum number of title words specified above.'),
            '#default_value' => $this->getSetting('add_type_label'),
        ];
        $form['add_alias'] = [
            '#type' => 'checkbox',
            '#disabled' => !$this->moduleHandler->moduleExists('path'),
            '#description' => $this->t('Requires path.module'),
            '#title' => $this->t('Add an url alias for each node.'),
            '#default_value' => FALSE,
        ];
        $form['add_statistics'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Add statistics for each node (node_counter table).'),
            '#default_value' => TRUE,
            '#access' => $this->moduleHandler->moduleExists('statistics'),
        ];

        // Add the language and translation options.
        $form += $this->getLanguageForm('nodes');

        // Add the user selection checkboxes.
        $author_header = [
            'id' => $this->t('User ID'),
            'user' => $this->t('Name'),
            'role' => $this->t('Role(s)'),
        ];

        $author_rows = [];
        /** @var \Drupal\user\UserInterface $user */
        foreach ($this->userStorage->loadMultiple() as $user) {
            $author_rows[$user->id()] = [
                'id' => ['#markup' => $user->id()],
                'user' => ['#markup' => $user->getAccountName()],
                'role' => ['#markup' => implode(", ", $user->getRoles())],
            ];
        }

        $form['authors-wrap'] = [
            '#type' => 'details',
            '#title' => $this->t('Users'),
            '#open' => FALSE,
            '#description' => $this->t('Select users for randomly assigning as authors of the generated content. Leave all unchecked to use a random selection of up to 50 users.'),
        ];

        $form['authors-wrap']['authors'] = [
            '#type' => 'tableselect',
            '#header' => $author_header,
            '#options' => $author_rows,
        ];

        $form['#redirect'] = FALSE;

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsFormValidate(array $form, FormStateInterface $form_state) {
        if (!array_filter($form_state->getValue('cart_types'))) {
            $form_state->setErrorByName('cart_types', $this->t('Please select at least one content type'));
        }
        $skip_fields = is_null($form_state->getValue('skip_fields')) ? [] : StringUtils::csvToArray($form_state->getValue('skip_fields'));
        $base_fields = is_null($form_state->getValue('base_fields')) ? [] : StringUtils::csvToArray($form_state->getValue('base_fields'));
        $form_state->setValue('skip_fields', $skip_fields);
        $form_state->setValue('base_fields', $base_fields);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateElements(array $values) {
        if ($this->isBatch($values['num'], $values['max_comments'])) {
            $this->generateBatchContent($values);
        }
        else {
            $this->generateContent($values);
        }
    }

    /**
     * Generate content when not in batch mode.
     *
     * This method is used when the number of elements is under 50.
     */
    private function generateContent($values) {
        $values['cart_types'] = array_filter($values['cart_types']);
        if (!empty($values['kill']) && $values['cart_types']) {
            $this->contentKill($values);
        }

        if (!empty($values['cart_types'])) {
            // Generate nodes.
            $this->develGenerateContentPreNode($values);
            $start = time();
            $values['num_translations'] = 0;
            for ($i = 1; $i <= $values['num']; $i++) {
                $this->develGenerateContentAddFurniture($values);
                if (isset($values['feedback']) && $i % $values['feedback'] == 0) {
                    $now = time();
                    $options = [
                        '@feedback' => $values['feedback'],
                        '@rate' => ($values['feedback'] * 60) / ($now - $start),
                    ];
                    $this->messenger()->addStatus(dt('Completed @feedback carts (@rate cart/min)', $options));
                    $start = $now;
                }
            }
        }
        $this->setMessage($this->formatPlural($values['num'], 'Created 1 node', 'Created @count nodes'));
        if ($values['num_translations'] > 0) {
            $this->setMessage($this->formatPlural($values['num_translations'], 'Created 1 cart translation', 'Created @count cart translations'));
        }
    }

    /**
     * Generate content in batch mode.
     *
     * This method is used when the number of elements is 50 or more.
     */
    private function generateBatchContent($values) {
        // Remove unselected node types.
        $values['cart_types'] = array_filter($values['cart_types']);
        // If it is drushBatch then this operation is already run in the
        // self::validateDrushParams().
        if (!$this->drushBatch) {
            // Setup the batch operations and save the variables.
            $operations[] = ['devel_generate_operation',
                [$this, 'batchContentPreNode', $values],
            ];
        }
        // Add the kill operation.
        if ($values['kill']) {
            $operations[] = ['devel_generate_operation',
                [$this, 'batchContentKill', $values],
            ];
        }

        // Add the operations to create the nodes.
        for ($num = 0; $num < $values['num']; $num++) {
            $operations[] = ['devel_generate_operation',
                [$this, 'batchContentAddNode', $values],
            ];
        }

        // Set the batch.
        $batch = [
            'title' => $this->t('Generating Content'),
            'operations' => $operations,
            'finished' => 'devel_generate_batch_finished',
            'file' => \Drupal::service('extension.path.resolver')->getPath('module', 'devel_generate') . '/devel_generate.batch.inc',
        ];

        batch_set($batch);
        if ($this->drushBatch) {
            drush_backend_batch_process();
        }
    }

    /**
     * Batch wrapper for calling ContentPreNode.
     */
    public function batchContentPreNode($vars, &$context) {
        $context['results'] = $vars;
        $context['results']['num'] = 0;
        $context['results']['num_translations'] = 0;
        $this->develGenerateContentPreNode($context['results']);
    }

    /**
     * Batch wrapper for calling ContentAddNode.
     */
    public function batchContentAddNode($vars, &$context) {
        if ($this->drushBatch) {
            $this->develGenerateContentAddFurniture($vars);
        }
        else {
            $this->develGenerateContentAddFurniture($context['results']);
        }
        if (!isset($context['results']['num'])) {
            $context['results']['num'] = 0;
        }
        $context['results']['num']++;
        if (!empty($vars['num_translations'])) {
            $context['results']['num_translations'] += $vars['num_translations'];
        }
    }

    /**
     * Batch wrapper for calling ContentKill.
     */
    public function batchContentKill($vars, &$context) {
        if ($this->drushBatch) {
            $this->contentKill($vars);
        }
        else {
            $this->contentKill($context['results']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateDrushParams(array $args, array $options = []) {
        $add_language = StringUtils::csvToArray($options['languages']);
        // Intersect with the enabled languages to make sure the language args
        // passed are actually enabled.
        $valid_languages = array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL));
        $values['add_language'] = array_intersect($add_language, $valid_languages);

        $translate_language = StringUtils::csvToArray($options['translations']);
        $values['translate_language'] = array_intersect($translate_language, $valid_languages);

        $values['add_type_label'] = $options['add-type-label'];
        $values['kill'] = $options['kill'];
        $values['feedback'] = $options['feedback'];
        $values['skip_fields'] = is_null($options['skip-fields']) ? [] : StringUtils::csvToArray($options['skip-fields']);
        $values['base_fields'] = is_null($options['base-fields']) ? [] : StringUtils::csvToArray($options['base-fields']);
        $values['title_length'] = 6;
        $values['num'] = array_shift($args);
        $values['max_comments'] = array_shift($args);
        // Do not use csvToArray here because it removes '0' values.
        $values['authors'] = is_null($options['authors']) ? [] : explode(',', $options['authors']);

        $all_types = array_keys(node_type_get_names());
        $default_types = array_intersect(['hb_cart', 'hb_furniture'], $all_types);
        $selected_types = StringUtils::csvToArray($options['bundles'] ?: $default_types);

        if (empty($selected_types)) {
            throw new \Exception(dt('No cart types available'));
        }

        $values['cart_types'] = array_combine($selected_types, $selected_types);
        $cart_types = array_filter($values['cart_types']);

        if (!empty($values['kill']) && empty($cart_types)) {
            throw new \Exception(dt('To delete content, please provide the cart types (--bundles)'));
        }

        // Checks for any missing content types before generating nodes.
        if (array_diff($cart_types, $all_types)) {
            throw new \Exception(dt('One or more cart types have been entered that don\'t exist on this site'));
        }

        if ($this->isBatch($values['num'], $values['max_comments'])) {
            $this->drushBatch = TRUE;
            $this->develGenerateContentPreNode($values);
        }

        return $values;
    }

    /**
     * Determines if the content should be generated in batch mode.
     */
    protected function isBatch($content_count, $comment_count) {
        return $content_count >= 50 || $comment_count >= 10;
    }

    /**
     * Deletes all nodes of given node types.
     *
     * @param array $values
     *   The input values from the settings form.
     */
    protected function contentKill(array $values) {
        $nids = $this->cartStorage->getQuery()
            ->condition('bundle', $values['cart_types'], 'IN')
            ->accessCheck(FALSE)
            ->execute();

        if (!empty($nids)) {
            $nodes = $this->cartStorage->loadMultiple($nids);
            $this->cartStorage->delete($nodes);
            $this->setMessage($this->t('Deleted %count carts.', ['%count' => count($nids)]));
        }
    }

    /**
     * Preprocesses $results before adding content.
     *
     * @param array $results
     *   Results information.
     */
    protected function develGenerateContentPreNode(array &$results) {
        $authors = $results['authors'];
        // Remove non-selected users. !== 0 will leave the Anonymous user in if it
        // was selected on the form or entered in the drush parameters.
        $authors = array_filter($authors, function ($k) {
            return $k !== 0;
        });
        // If no users are specified then get a random set up to a maximum of 50.
        // There is no direct way randomise the selection using entity queries, so
        // we use a database query instead.
        if (empty($authors)) {
            $query = $this->database->select('users', 'u')
                ->fields('u', ['uid'])
                ->range(0, 50)
                ->orderRandom();
            $authors = $query->execute()->fetchCol();
        }
        $results['users'] = $authors;
    }

    /**
     * Create one node. Used by both batch and non-batch code branches.
     *
     * @param array $results
     *   Results information.
     */
    protected function develGenerateContentAddFurniture(array &$results) {
        if (!isset($results['time_range'])) {
            $results['time_range'] = 0;
        }
        $users = $results['users'];
        $cart_type = array_rand($results['cart_types']);
        $uid = $users[array_rand($users)];
        // Add the content type label if required.
        $title_prefix = $results['add_type_label'] ? $this->cartTypeStorage->load($cart_type)->label() . ' - ' : '';
		    $products = \Drupal::entityQuery('hb_product')
			    ->condition('bundle', $cart_type)
			    ->range(0, 10)
			    ->accessCheck(FALSE)
					->execute();
        $values = [
            'id' => NULL,
            'bundle' => $cart_type,
            'label' => $title_prefix . $this->getRandom()->sentences(mt_rand(1, $results['title_length']), TRUE),
            'uid' => $uid,
            'description' => $this->getRandom()->sentences(mt_rand(1, 500)),
            'field_c_f_furniture' => array_values($products),
            'field_c_f_quantity' => mt_rand(10, 100),
            'status' => TRUE,
            'created' => $this->time->getRequestTime() - mt_rand(0, $results['time_range']),
        ];

        if (isset($results['add_language'])) {
            $values['langcode'] = $this->getLangcode($results['add_language']);
        }
        $cart = $this->cartStorage->create($values);
        // See devel_generate_entity_insert() for actions that happen before and
        // after this save.
        $cart->save();

        // Add url alias if required.
        if (!empty($results['add_alias'])) {
            $path_alias = $this->aliasStorage->create([
                'path' => '/cart/' . $cart->id(),
                'alias' => '/cart-' . $cart->id() . '-' . $cart->bundle(),
                'langcode' => $values['langcode'] ?? LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ]);
            $path_alias->save();
        }

        // Add translations.
        if (isset($results['translate_language']) && !empty($results['translate_language'])) {
            $this->develGenerateContentAddNodeTranslation($results, $cart);
        }
    }

    /**
     * Create translation for the given node.
     *
     * @param array $results
     *   Results array.
     * @param \Drupal\hb_product\HbcartInterface $cart
     *   Node to add translations to.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    protected function develGenerateContentAddNodeTranslation(array &$results, HbcartInterface $cart) {
        if (is_null($this->contentTranslationManager)) {
            return;
        }
        if (!$this->contentTranslationManager->isEnabled('hb_cart', $cart->getEntityTypeId())) {
            return;
        }
        if ($cart->langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED || $cart->langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
            return;
        }

        if (!isset($results['num_translations'])) {
            $results['num_translations'] = 0;
        }
        // Translate node to each target language.
        $skip_languages = [
            LanguageInterface::LANGCODE_NOT_SPECIFIED,
            LanguageInterface::LANGCODE_NOT_APPLICABLE,
            $cart->langcode->value,
        ];
        foreach ($results['translate_language'] as $langcode) {
            if (in_array($langcode, $skip_languages)) {
                continue;
            }
            $translation_node = $cart->addTranslation($langcode);
            $translation_node->devel_generate = $results;
            $translation_node->setTitle($cart->bundle() . ' (' . $langcode . ')');
            $this->populateFields($translation_node);
            $translation_node->save();
            if ($translation_node->id() > 0 && !empty($results['add_alias'])) {
                $path_alias = $this->aliasStorage->create([
                    'path' => '/cart/' . $translation_node->id(),
                    'alias' => '/cart-' . $translation_node->id() . '-' . $translation_node->bundle() . '-' . $langcode,
                    'langcode' => $langcode,
                ]);
                $path_alias->save();
            }
            $results['num_translations']++;
        }
    }
}
