<?php

namespace Drupal\swiper_formatter\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\swiper_formatter\Entity\SwiperFormatter;

/**
 * Provides a form element with Swiper formatter options.
 *
 * Usage example:
 * @code
 * $form['swiper_formatter'] = [
 *   '#type' => 'swiper_formatter_settings',
 *   '#title' => t('Swiper formatter settings'),
 *   '#default_value' => (array) $values,
 * ];
 * @endcode
 *
 * @FormElement("swiper_formatter_settings")
 */
class SwiperFormatterSettings extends FormElement {

  // Field types that can be a source for slide csption.
  // It does combine and include for field formatter settings form
  // as well as in Views' settings form (naming).
  const CAPTION_TYPES = [
    'basic_string',
    'string',
    'string_long',
    'text',
    'text_default',
    'text_long',
    'text_with_summary',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [__CLASS__, 'process'],
        [__CLASS__, 'processCaption'],
      ],
    ];
  }

  /**
   * Process this element.
   *
   * @param array $element
   *   This element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $complete_form
   *   Possibly a complete form, including parent one.
   *
   * @return array
   *   This element, processed and with basic elements included.
   */
  public static function process(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $default_values = isset($element['#default_value']) && !empty($element['#default_value']) ? $element['#default_value'] : [];
    // $has_template,
    $template = isset($default_values['template']) ? $default_values['template'] : NULL;
    $options = SwiperFormatter::getSwiperTemplates();

    if (empty($options)) {
      $warning_line = "There's no Swiper templates created yet, ";
      if (\Drupal::service('current_user')->hasPermission('administer swiper')) {
        $warning = t('@warning_line create one first <a target="_blank" href=":url">here</a>.', [
          '@warning_line' => $warning_line,
          ':url' => '/admin/config/content/swiper_formatter/add',
        ]);
      }
      else {
        $warning = t('@warning_line create one first, needs "administer swiper" permission.', [
          '@warning_line' => $warning_line,
        ]);
      }
      \Drupal::service('messenger')->addWarning($warning);
    }

    $element['template'] = [
      '#title' => t('Swiper template'),
      '#type' => 'select',
      '#default_value' => $template,
      '#required' => TRUE,
      '#options' => $options,
      '#description' => t('Choose one of the available Swiper templates'),
      '#weight' => 0,

    ];

    $params = [];
    if ($default_values && isset($default_values['destination']) && !empty($default_values['destination'])) {
      $params['query'] = $default_values['destination'];
    }

    $manage_url = $template ? Url::fromRoute('entity.swiper_formatter.edit_form', ['swiper_formatter' => $template], $params) : Url::fromRoute('entity.swiper_formatter.collection', [], $params);
    $element['links'] = [
      '#theme' => 'links',
      '#links' => [
        [
          'title' => t('Create new option set'),
          'url' => Url::fromRoute('entity.swiper_formatter.add_form', [], $params),
        ],
        [
          'title' => t('Manage options'),
          'url' => $manage_url,
        ],
      ],
      '#access' => \Drupal::service('current_user')->hasPermission('administer swiper'),
    ];

    return $element;

  }

  /**
   * Render caption element.
   *
   * @param array $element
   *   This element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $complete_form
   *   Possibly a complete form, including parent one.
   *
   * @return array
   *   This element, processed and with caption element included.
   */
  public static function processCaption(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $default_values = isset($element['#default_value']) && !empty($element['#default_value']) ? $element['#default_value'] : NULL;

    // Add the caption setting.
    if ($default_values) {

      // Field's name and type.
      $type = isset($default_values['type']) && !empty($default_values['type']) ? $default_values['type'] : NULL;
      $name = isset($default_values['name']) && !empty($default_values['name']) ? $default_values['name'] : NULL;

      if (!$type || !$name) {
        return $element;
      }

      $caption_options = [];
      $action_fields = [];

      $caption_description = 'This will show up as a slide caption. Note that selected field must be multiple and follow deltas of slide field as well.';
      switch ($type) {
        case 'image':

          static::imageCaption($default_values, $caption_options, $action_fields);

          if ($name && isset($default_values['caption']) && isset($default_values['caption']['entity_fields']) && !empty($default_values['caption']['entity_fields'])) {
            static::defaultCaption($default_values['caption']['entity_fields'], $name, $caption_options, $action_fields);
          }
          $caption_description .= ' In case of a field other than image\'s alt and title caption will show even if chosen field itself is disabled for this Display. This way some field can serve exclusively as a caption.';
          break;

        default:
          if ($name && isset($default_values['caption']) && isset($default_values['caption']['entity_fields']) && !empty($default_values['caption']['entity_fields'])) {
            static::defaultCaption($default_values['caption']['entity_fields'], $name, $caption_options, $action_fields);
          }
          $caption_description .= $type == 'views' ? " This field won't show in the render result (within slide) but only as a caption." : ' Caption will show even if the field itself is disabled for this Display. This way some field can serve exclusively as a caption.';
          break;
      }

      // Define caption element.
      $element['caption'] = [
        '#title' => t('Caption source field'),
        '#type' => 'select',
        '#options' => $caption_options,
        '#empty_option' => t('@none', ['@none' => '- None -']),
        '#default_value' => $default_values && isset($default_values['caption']) && isset($default_values['caption']['value']) ? $default_values['caption']['value'] : NULL,
        '#description' => t('@caption_description', ['@caption_description' => $caption_description]),
        '#weight' => 1,
      ];

      // If the image field doesn't have all of the suitable caption sources,
      // inform the user.
      if (!empty($action_fields)) {
        $action_text = t('Enable the @action_field field', ['@action_field' => implode(' and/or ', $action_fields)]);

        if (isset($default_values['caption']['field_edit_url']) && $default_values['caption']['field_edit_url'] instanceof Url) {
          $action = Link::fromTextAndUrl($action_text, $default_values['caption']['field_edit_url'])->toRenderable();
        }
        else {
          // Just use plain text if we can't build the field edit link.
          $action = ['#markup' => $action_text];
        }
        // $action = ['#markup' => $action_text];
        $element['caption']['#description'] = t('You need to @action for this field to be able to use it as a caption.', [
          '@action' => \Drupal::service('renderer')->render($action),
        ]);

        // If there are no suitable caption sources,
        // than disable the caption element.
        if (count($action_fields) >= 2) {
          $element['caption']['#disabled'] = TRUE;
        }
      }

      // Images specific, add custom link on slide.
      if (isset($element['image_link'])) {
        // $element['image_link']['#access'] = FALSE;
        $element['image_link']['#options']['custom'] = t('Custom');
        $element['image_link']['#weight'] = 3;
        $element['custom_link'] = [
          '#type' => 'url',
          '#title' => t('Custom link'),
          '#default_value' => $default_values['custom_link'],
          '#weight' => 4,
          '#description' => t('Enter any custom link here. It will be the same for all slides, unless you override in the twig template, for instance placing part of the string here as some kind of tokens. Possible feature is usage of Token module.'),
          '#states' => [
            'visible' => [
              ':input[name="fields[field_image][settings_edit_form][settings][image_link]"]' => ['value' => 'custom'],
            ],
          ],
        ];
      }

      if (isset($element['image_style'])) {
        $element['image_style']['#weight'] = 2;
      }
    }

    return $element;
  }

  /**
   * Define #options for caption form element.
   *
   * @param array $entity_fields
   *   Asociative array of fields, storage and definition.
   * @param string $name
   *   Machine name of the captions field source.
   * @param array $caption_options
   *   Associative array ready for #options of a caption field source element.
   * @param array $action_fields
   *   An array of "actions", providing some extr alinks to user.
   */
  protected static function defaultCaption(array $entity_fields, string $name, array &$caption_options, array &$action_fields = []) {
    foreach ($entity_fields as $field) {
      if (in_array($field['storage']->getType(), static::CAPTION_TYPES) && $field['storage']->getName() != $name) {
        /** @var \Drupal\field\Entity\FieldStorageConfig $field['storage'] */
        if ($field['storage']->getCardinality() != 1) {
          $caption_options[$field['storage']->getName()] = t('@label', ['@label' => $field['title']]);
        }
      }
    }
  }

  /**
   * Define image specific #options for caption form element.
   *
   * @param array $default_values
   *   Asociative array of default values, fed from parent form (caller).
   * @param array $caption_options
   *   Associative array ready for #options of a caption field source element.
   * @param array $action_fields
   *   An array of "actions", providing some extr alinks to user.
   */
  protected static function imageCaption(array $default_values, array &$caption_options, array &$action_fields) {

    if (isset($default_values['settings']) && isset($default_values['settings']['title_field'])) {
      if ($default_values['settings']['title_field'] == FALSE) {
        // User action required on the image title.
        $action_fields[] = 'title';
      }
      else {
        $caption_options['title'] = t('Image Title field');
      }
    }

    if (isset($default_values['settings']) && isset($default_values['settings']['alt_field'])) {
      if ($default_values['settings']['alt_field'] == FALSE) {
        // User action required on the image title.
        $action_fields[] = 'alt';
      }
      else {
        $caption_options['alt'] = t('Image Alt field');
      }
    }
  }

}
