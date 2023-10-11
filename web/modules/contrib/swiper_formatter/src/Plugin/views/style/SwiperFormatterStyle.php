<?php

namespace Drupal\swiper_formatter\Plugin\views\style;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\Component\Utility\Html;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "swiper_formatter",
 *   title = @Translation("Swiper formatter"),
 *   help = @Translation("Display the results in a Swiper."),
 *   theme = "swiper_formatter",
 *   display_types = {"normal"}
 * )
 */
class SwiperFormatterStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Swiper Configuration Entity definittion.
   *
   * @var \Drupal\swiper_formatter\Entity\SwiperFormatter
   */
  protected $swiperFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->swiperFormatter = $this->entityTypeManager->getStorage('swiper_formatter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['template'] = ['default' => 'default'];
    $options['caption'] = ['default' => NULL];

    // Ensure unique id attribute for each instance
    // of Swiper on the same page.
    // User can change this on settings but we try to
    // make sure some unique id is auto assigned.
    $view_id = $this->view->id();
    $current_display = $this->view->current_display;
    $options['id'] = ['default' => Html::getUniqueId('swiper-' . $view_id . '-' . $current_display)];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['swiper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Swiper formatter settings'),
    ];

    if ($this->swiperStorage = $this->swiperFormatter->load($this->options['template'])) {
      $this->options += $this->swiperStorage->get('swiper_options');
    }

    $form['swiper']['swiper_el'] = [
      '#type' => 'swiper_formatter_settings',
      '#title' => $this->t('Swiper formatter settings'),
      '#default_value' => [
        'type' => 'views',
        'name' => 'views',
        'template' => $this->options['template'],
        'settings' => $this->options,
      ],
    ];

    // $this->view->initStyle();
    if ($this->usesFields()) {

      $fields = $this->view->style_plugin->displayHandler->handlers['field'];
      $entity_fields = [];

      foreach ($fields as $field_name => $field) {
        // Double check on field storage definition existence.
        /** @var \Drupal\views\Plugin\views\field\EntityField $field */
        $field_storage_definitions = isset($field->definition['entity_type']) && !empty($field->definition['entity_type']) ? $this->entityFieldManager->getFieldStorageDefinitions($field->definition['entity_type']) : [];
        if (isset($field->definition['field_name']) && isset($field_storage_definitions[$field->definition['field_name']])) {

          if ($field->options['type'] == 'image' && (count($fields) == 1 || count($fields) == 2)) {
            $form['swiper']['swiper_el']['#default_value']['type'] = 'image';
            $alt_subfield = $field->definition['field_name'] . '_alt';
            $title_subfield = $field->definition['field_name'] . '_title';
            if (in_array($alt_subfield, $field->definition['additional fields'])) {
              $form['swiper']['swiper_el']['#default_value']['settings']['alt_field'] = TRUE;
            }
            if (in_array($title_subfield, $field->definition['additional fields'])) {
              $form['swiper']['swiper_el']['#default_value']['settings']['title_field'] = TRUE;
            }
          }

          $entity_fields[$field_name] = [
            'title' => $field->definition['title'],
            'storage' => $field_storage_definitions[$field->definition['field_name']],
          ];
        }
      }

      $form['swiper']['swiper_el']['#default_value']['caption'] = [
        'value' => $this->options['caption'],
        'entity_fields' => $entity_fields,
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function renderFields(array $result) {
    parent::renderFields($result);
    // Remove field that was set to be a slide caption.
    if (!empty($this->options['caption']) && !empty($this->rendered_fields)) {
      foreach ($this->rendered_fields as &$rendered_field) {
        if (isset($rendered_field[$this->options['caption']])) {
          unset($rendered_field[$this->options['caption']]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $output = [];
    $sets = parent::render();

    if (!isset($this->options['template']) || empty($this->options['template']) || empty($sets)) {
      return $output;
    }

    if ($this->swiperStorage = $this->swiperFormatter->load($this->options['template'])) {

      $this->options += $this->swiperStorage->get('swiper_options');
      $this->options['field_type'] = 'views';

      $this->options['is_image'] = FALSE;

      if ($this->usesFields()) {
        $fields = $this->view->style_plugin->displayHandler->handlers['field'];
        if (count($fields) == 1 || count($fields) == 2) {

          foreach ($fields as $field) {
            /** @var \Drupal\views\Plugin\views\field\EntityField $field */
            if ($field->options['type'] == 'image') {

              if (isset($field->options['settings']['image_style']) && !empty($field->options['settings']['image_style'])) {
                $this->options['image_style'] = $field->options['settings']['image_style'];
              }
              $this->options['field_name'] = $field->definition['field_name'];
              $this->options['field_type'] = 'views_image';
              $this->options['is_image'] = $field->definition['field_name'];
            }
          }
        }
      }

      foreach ($sets as $index => &$set) {

        $id = isset($this->options['grouping']) && !empty($this->options['grouping']) && !empty($this->options['grouping'][0]) ? Html::getUniqueId('swiper-group-' . $this->options['grouping'][0]['field'] . '-' . $index) : NULL;

        if (!$id) {
          $id = $this->options['id'];
        }
        $captions = [];

        foreach ($set['#rows'] as $delta => &$row) {

          $i = 0;
          $entity = NULL;
          if (isset($row['#row'])) {
            $entity = $row['#row']->_entity;
          }
          else {
            $entity = isset($row['#' . $row['#theme']]) ? $row['#' . $row['#theme']] : NULL;
          }

          // Take care of caption.
          if (is_object($entity) && isset($this->options['caption']) && !empty($this->options['caption'])) {
            $image_subfields = ['alt', 'title'];
            if ($this->options['is_image'] && in_array($this->options['caption'], $image_subfields)) {
              foreach ($entity->get($this->options['is_image'])->getValue() as $img_delta => $img_value) {
                $captions[$img_delta] = isset($entity->get($this->options['is_image'])[$img_delta]) ? ['value' => $entity->get($this->options['is_image'])[$img_delta]->{$this->options['caption']}] : [];
              }
              $row['#caption'] = $this->parseLinear($i, $delta, 'caption', $captions);
            }
            else {
              if ($entity->hasField($this->options['caption']) && !empty($entity->get($this->options['caption'])->getValue())) {
                $row['#caption'] = $this->parseLinear($i, $delta, 'caption', $entity->get($this->options['caption'])->getValue());
              }
            }
          }
          if (is_object($entity) && isset($this->options['field_name']) && $entity->hasField($this->options['field_name']) && !empty($entity->get($this->options['field_name'])->getValue())) {
            $row['#background'] = $this->parseLinear($i, $delta, 'background', $entity->get($this->options['field_name'])->getValue());
          }
        }

        $output[$index] = [
          '#theme' => $this->themeFunctions(),
          '#id' => $id,
          '#object' => $this->view,
          '#content' => $set['#rows'],
          '#settings' => $this->options,
          '#attributes' => [
            'id' => $id,
            'class' => [
              'swiper-container',
            ],
          ],
        ];
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    // Move swiper options to the parent array so that
    // values are saved properly.
    $swiper_options = $form_state->getValue([
      'style_options',
      'swiper',
      'swiper_el',
    ]);

    foreach ($swiper_options as $key => $value) {
      $form_state->setValue(['style_options', $key], $value);
    }

    $form_state->setValue(['style_options', 'swiper'], NULL);
  }

  /**
   * Some Kung-fu magic here.
   *
   *  Try to reset deltas when
   *  "Multiple field settings > Display all values in the same row"
   *  in fields setting in a View is disabled.
   *
   * @param int $index
   *   Custom index.
   * @param int $delta
   *   Default index/delta returned from a view render.
   * @param string $type
   *   Property we are looking for and returning.
   * @param array $field_values
   *   An array of field values returned by its parent entity.
   *
   * @return string
   *   Either a raw caption string (to be rendered),
   *   or url of image field (for lazy loading feature).
   */
  protected function parseLinear(int &$index, int $delta, string $type, array $field_values = []) {

    $values = NULL;

    if (count($field_values) > 0) {
      $count_values = count($field_values) - 1;
      if ($delta >= $count_values) {
        $index = $delta > 1 ? $delta - $count_values : $delta;
      }
      else {
        $index = $delta;
      }

      if ($type == 'caption') {
        $values = isset($field_values[$index]['value']) ? $field_values[$index]['value'] : NULL;
      }
      elseif ($type == 'background') {
        // Lazy load support.
        if ($this->options['is_image'] && $this->options['lazy']['enabled']) {
          $values = $this->lazyLoad($index, $delta, $field_values);
        }
      }
      $index++;
    }
    return $values;
  }

  /**
   * Get image URL to use Lazy loading feature in template.
   *
   * @param int $index
   *   Custom index.
   * @param int $delta
   *   Default index/delta returned from a view render.
   * @param array $field_values
   *   An array of field values returned by its parent entity.
   *
   * @return string
   *   A path or url of the image to set as data attribute,
   *   for Lazy loading Swiper feature. It resepects selected image style.
   */
  protected function lazyLoad(int $index, int $delta, array $field_values) {
    $background = NULL;
    $image_target_id = isset($field_values[$index]['target_id']) ? $field_values[$index]['target_id'] : NULL;
    if ($image_target_id) {
      if ($file = $this->entityTypeManager->getStorage('file')->load($image_target_id)) {
        if (isset($this->options['image_style']) && !empty($this->options['image_style'])) {
          $image_style = $this->entityTypeManager->getStorage('image_style')->load($this->options['image_style']);
          $background = $image_style->buildUrl($file->getFileUri());
        }
        // No Image style set (bad :)
        else {
          $background = $file->createFileUrl();
        }

        // Important Swiper options for to combine with Lazy Loading module.
        $this->options['preloadImages'] = FALSE;
        $this->options['watchSlidesProgress'] = TRUE;
      }
    }
    return $background;
  }

}
