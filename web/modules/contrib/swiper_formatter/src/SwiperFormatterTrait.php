<?php

namespace Drupal\swiper_formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\Html;

/**
 * Common methods for a few possible Swiper formatters.
 */
trait SwiperFormatterTrait {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Routing\RedirectDestination definition.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * Swiper Configuration Entity definittion.
   *
   * @var \Drupal\swiper_formatter\Entity\SwiperFormatter
   */
  protected $swiperFormatter;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
  $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    EntityStorageInterface $image_style_storage,
    FileUrlGeneratorInterface $file_url_generator = NULL,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    RedirectDestinationInterface $destination,
    MessengerInterface $messenger) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);

    // $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->destination = $destination;
    $this->swiperFormatter = $this->entityTypeManager->getStorage('swiper_formatter');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('redirect.destination'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'template' => 'default',
      'caption' => NULL,
      'custom_link' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::settingsForm($form, $form_state);

    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $this->fieldDefinition->getTargetBundle());
    $entity_fields = [];
    $swiper_entity = NULL;

    foreach ($fields as $field_name => $field) {
      $entity_fields[$field_name] = [
        'title' => $field->getLabel(),
        'storage' => $field->getFieldStorageDefinition(),
      ];
    }

    $settings = $this->fieldDefinition->getSettings();

    if (!empty($this->getSetting('template'))) {
      if ($swiper_entity = $this->swiperFormatter->load($this->getSetting('template'))) {
        $settings += $swiper_entity->get('swiper_options');
      }
    }

    $element += [
      '#type' => 'swiper_formatter_settings',
      '#title' => $this->t('Swiper formatter settings'),
      '#default_value' => [
        'type' => $this->fieldDefinition->getFieldStorageDefinition()->getType(),
        'name' => $this->fieldDefinition->getFieldStorageDefinition()->getName(),
        'template' => $this->getSetting('template'),
        'settings' => $settings,
        'custom_link' => $this->getSetting('custom_link'),
        'caption' => [
          'value' => $this->getSetting('caption'),
          'entity_fields' => $entity_fields,
        ],
      ],
      '#weight' => 0,
    ];

    if ($this->fieldDefinition->getTargetBundle()) {

      $route_name = 'entity.field_config.' . $entity_type . '_field_edit_form';
      $route_params = [
        'field_config' => $entity_type . '.' . $this->fieldDefinition->getTargetBundle() . '.' . $this->fieldDefinition->getFieldStorageDefinition()->getName(),
      ];

      $route_params[$entity_type . '_type'] = $this->fieldDefinition->getTargetBundle();

      $destination = substr($this->destination->get(), 0, strpos($this->destination->get(), '?'));
      $uri_options = [
        'fragment' => 'edit-settings-title-field',
        'query' => ['destination' => $destination],
      ];
      $element['#default_value']['caption']['field_edit_url'] = Url::fromRoute($route_name, $route_params, $uri_options);
      $element['#default_value']['destination'] = ['destination' => $destination];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $parent = parent::settingsSummary();
    $summary = [];

    // Build the options summary.
    if ($swiper_template = $this->getSetting('template')) {
      if ($swiper_entity = $this->swiperFormatter->load($swiper_template)) {
        $summary[] = $this->t('Swiper template: @swiper_template', ['@swiper_template' => $swiper_entity->label()]);
      }
    }

    if ($caption = $this->getSetting('caption')) {
      switch ($caption) {
        case 'title':
        case 'alt':
          $summary[] = $this->t('Caption: Image @caption field', ['@caption' => ucfirst($caption)]);
          break;

        default:
          $fields = $this->entityFieldManager->getFieldDefinitions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle());
          if (isset($fields[$caption])) {
            $summary[] = $this->t('Caption: @caption field', ['@caption' => $fields[$caption]->getLabel()]);
          }
          break;
      }
    }

    // Merge with parent settings summary.
    $summary = array_merge($summary, $parent);

    // Custom link for image.
    if ($this->getSetting('image_link') == 'custom') {
      $summary[] = $this->t('Custom link');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $output = parent::viewElements($items, $langcode);

    $type = $this->fieldDefinition->getFieldStorageDefinition()->getType();

    // Bail out if no elements to render.
    if (empty($output)) {
      return [];
    }

    if (!$this->getSetting('template')) {
      $message = $this->t("<em>Swiper formatter is set for <strong>@field</strong></em> field but no Swiper template is set on field's display settings. Check it out @edit. Falling back to default view.", [
        '@field' => $this->fieldDefinition->getLabel(),
        '@edit' => 'gg',
      ]);
      $this->messenger->addWarning($message);
      return $output;
    }

    $id = NULL;
    $formatter_settings = $this->getSettings();
    $formatter_settings['field_type'] = $type;
    $formatter_settings['field_name'] = $this->fieldDefinition->getFieldStorageDefinition()->getName();

    if ($swiper_entity = $this->swiperFormatter->load($this->getSetting('template'))) {

      $formatter_settings += $swiper_entity->get('swiper_options');

      $id = Html::getUniqueId('swiper-' . $swiper_entity->id() . '-' . $this->fieldDefinition->getTargetEntityTypeId() . '-' . $this->fieldDefinition->getTargetBundle() . '-' . $this->fieldDefinition->getFieldStorageDefinition()->getName());
      $formatter_settings += ['id' => $id];

      foreach ($output as $delta => &$item) {

        if ($type == 'image' && $formatter_settings['lazy']['enabled']) {
          if ($formatter_settings['image_style']) {
            $image_style = $this->entityTypeManager->getStorage('image_style')->load($formatter_settings['image_style']);
            $item['#background'] = $image_style->buildUrl($item['#item']->entity->getFileUri());
          }
          else {
            $item['#background'] = $item['#item']->entity->createFileUrl();
          }
        }

        if (isset($formatter_settings['caption'])) {
          switch ($formatter_settings['caption']) {
            case 'title':
              $item['#caption'] = isset($item['#item']) ? $item['#item']->title : NULL;
              break;

            case 'alt':
              $item['#caption'] = isset($item['#item']) ? $item['#item']->alt : NULL;
              break;

            default:
              $item['#caption'] = $items->getEntity()->hasField($formatter_settings['caption']) && isset($items->getEntity()->get($formatter_settings['caption'])->getValue()[$delta]) && isset($items->getEntity()->get($formatter_settings['caption'])->getValue()[$delta]['value']) ? $items->getEntity()->get($formatter_settings['caption'])->getValue()[$delta]['value'] : NULL;
              break;
          }
        }
        if (isset($formatter_settings['image_link'])) {
          if (isset($item['#url'])) {
            $item['#slide_url'] = is_object($item['#url']) ? $item['#url']->toString() : $item['#url'];
          }
          else {
            if ($formatter_settings['image_link'] == 'custom') {
              // @todo Implement some tokens for this custom field.
              $item['#slide_url'] = $formatter_settings['custom_link'];
            }
          }
        }
      }

      if ($formatter_settings['field_type'] == 'image' && $formatter_settings['lazy']['enabled']) {
        $formatter_settings['preloadImages'] = FALSE;
        $formatter_settings['watchSlidesProgress'] = TRUE;
      }

      // Assign our theme and its variables here.
      $elements = [
        '#theme' => 'swiper_formatter',
        '#id' => $id,
        '#object' => $items->getEntity(),
        '#content' => $output,
        '#settings' => $formatter_settings,
        '#attributes' => [
          'id' => $id,
          'class' => [
            'swiper-container',
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter only applies to multi-image fields.
    return parent::isApplicable($field_definition) && $field_definition->getFieldStorageDefinition()->isMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $option_id = $this->getSetting('template');
    // Add the options as dependency.
    if ($option_id) {
      $options = $this->swiperFormatter->load($option_id);
      $dependencies[$options->getConfigDependencyKey()][] = $options->getConfigDependencyName();
    }
    return parent::calculateDependencies() + $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    if ($this->optionsDependenciesDeleted($this, $dependencies)) {
      $changed = TRUE;
    }
    return $changed;
  }

  /**
   * If a dependency is going to be deleted, set the option set to default.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   * @param array $dependencies_deleted
   *   An array of dependencies that will be deleted.
   *
   * @return bool
   *   Whether or not option set dependencies changed.
   */
  protected function optionsDependenciesDeleted(FormatterBase $formatter, array $dependencies_deleted) {
    $option_id = $formatter->getSetting('template');
    if ($option_id && $options = $options = $this->swiperFormatter->load($option_id)) {
      if (!empty($dependencies_deleted[$options->getConfigDependencyKey()]) && in_array($options->getConfigDependencyName(), $dependencies_deleted[$options->getConfigDependencyKey()])) {
        $formatter->setSetting('template', NULL);
        return TRUE;
      }
    }
    return FALSE;
  }

}
