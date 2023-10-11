<?php

namespace Drupal\swiper_formatter\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Swiper entity form.
 *
 * @property \Drupal\swiper_formatter\SwiperFormatterInterface $entity
 */
class SwiperFormatterForm extends EntityForm {

  const SWIPER_MODULES = [
    'navigation',
    'pagination',
    'autoplay',
    'lazy',
  ];

  /**
   * EntityManager class.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an Swiper configuration entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $default_setting = $this->config('swiper_formatter.settings')->getRawData();
    // $swiper_options = $this->entity->swiper_options;
    $default_values = array_merge($default_setting, $this->entity->swiper_options);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the swiper.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Description for this Swiper template.'),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $default_values['enabled'],
      '#description' => $this->t("Whether Swiper initially enabled. When Swiper is disabled, it will hide all navigation elements and won't respond to any events and interactions."),
    ];

    $form['source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Swiper library source'),
      '#description' => $this->t('Under some conditions, usage of some themes and/or other libraries, some issues may occur. Set to local library to try to resolve, but please first make sure to have mandatory for <em>Local</em> <a target="_blank" href="https://unpkg.com/swiper@8/swiper-bundle.min.js">swiper-bundle.js</a> and <a target="_blank" href="https://unpkg.com/swiper@8/swiper-bundle.css">swiper-bundle.min.css</a> and/or mandatory for <em>Local minified</em> <a target="_blank" href="https://unpkg.com/swiper@8/swiper-bundle.min.js">swiper-bundle.min.js</a> and <a target="_blank" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">swiper-bundle.min.css</a> <strong>downloaded and placed in <strong>/libraries/swiper/</strong> folder.</strong>'),
      '#options' => [
        'remote' => $this->t('Remote (cdn)'),
        'local' => $this->t('Local'),
        'local_minified' => $this->t('Local minified'),
      ],
      '#default_value' => $default_values['source'],
    ];

    $form['swiper_options'] = [
      '#type' => 'container',
      '#title' => $this->t('Swiper options'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Wrapper options'),
      '#description' => $this->t('For the extensive list of options for this class see <a href="https://swiperjs.com/swiper-api#parameters">Swiper.js API</a>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['swiper_options']['wrapper']['autoHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Auto height'),
      '#default_value' => $default_values['autoHeight'],
      '#description' => $this->t('This is <strong>recommended</strong> for Vertical direction as well as for lazy loading (see below).'),
    ];

    $form['swiper_options']['wrapper']['width'] = [
      '#title' => $this->t('Fixed width'),
      '#type' => 'number',
      '#description' => $this->t('Integer value in pixels - recommended for vertical swipers.'),
      '#default_value' => $default_values['width'],
    ];

    $form['swiper_options']['wrapper']['height'] = [
      '#title' => $this->t('Fixed height'),
      '#type' => 'number',
      '#description' => $this->t('Integer value in pixels - recommended for vertical swipers.'),
      '#default_value' => $default_values['height'],
    ];

    $form['swiper_options']['wrapper']['observer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Observer'),
      '#default_value' => $default_values['observer'],
      '#description' => $this->t('Enable Mutation Observer on Swiper and its elements. In this case Swiper will be updated (reinitialised) each time if you change its style (like hide/show) or modify its child elements (like adding/removing slides). May be handy for programmatic slides transition i.e. form other trigger element on the page for instance.'),
    ];

    $form['swiper_options']['slides'] = [
      '#type' => 'details',
      '#title' => $this->t('Slides options'),
      '#description' => $this->t('For the extensive list of options for this class see <a href="https://swiperjs.com/swiper-api#parameters">Swiper.js API</a>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['swiper_options']['slides']['direction'] = [
      '#type' => 'radios',
      '#title' => $this->t('Direction'),
      '#description' => $this->t('Select sliding direction.'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
      ],
      '#default_value' => $default_values['direction'],
    ];

    $form['swiper_options']['slides']['effect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Swipe effect'),
      '#default_value' => $default_values['effect'],
      '#description' => $this->t('Choose one of a few Swiper effects. See <a target="_blank" href="https://swiperjs.com/swiper-api#param-effect">here</a>.<br /><em>Creative</em> effect seems unstable at the moment, therefore disabled.'),
      '#process' => [
        ['\Drupal\Core\Render\Element\Radios', 'processRadios'],
        [get_class($this), 'processEffect'],
      ],

      '#options' => [
        'slide' => $this->t('Slide'),
        'fade' => $this->t('Fade'),
        'cube' => $this->t('Cube'),
        'coverflow' => $this->t('Coverflow'),
        'flip' => $this->t('Flip'),
        'creative' => $this->t('Creative'),
        'cards' => $this->t('Cards'),
      ],
    ];

    $form['swiper_options']['slides']['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $default_values['loop'],
      '#description' => $this->t('Enable continuous loop mode. See some restrictions <a href="https://swiperjs.com/swiper-api#param-loop" target="_blank">here</a>.'),
    ];

    $form['swiper_options']['slides']['rewind'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $default_values['rewind'],
      '#description' => $this->t('Enable rewind, click on next nav button on last slide loads first slide, click on prev button on first slide rewinds to the last one. <strong>Should not be used together with loop mode.</strong>'),
    ];

    $form['swiper_options']['slides']['centeredSlides'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Centered slides'),
      '#default_value' => $default_values['centeredSlides'],
      '#description' => $this->t('Active slide will be centered, not always on the left side.'),
    ];

    $form['swiper_options']['slides']['speed'] = [
      '#title' => $this->t('Transition speed'),
      '#type' => 'number',
      '#description' => $this->t('Duration of transition between slides (in ms).'),
      '#default_value' => $default_values['speed'],
    ];

    $form['swiper_options']['slides']['slidesPerView'] = [
      '#title' => $this->t('Number of slides per view'),
      '#type' => 'number',
      '#description' => $this->t("Integer value - slides visible at the same time on slider's container."),
      '#default_value' => $default_values['slidesPerView'],
    ];

    $form['swiper_options']['slides']['loopedSlides'] = [
      '#title' => $this->t('Number of looped slides'),
      '#type' => 'number',
      '#description' => $this->t('Integer value - Number of slides looped at once. Probably must be set for the above config to work.'),
      '#default_value' => $default_values['loopedSlides'],
    ];

    $form['swiper_options']['slides']['noSwipingSelector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No swiping selectors'),
      '#maxlength' => 255,
      '#default_value' => $default_values['noSwipingSelector'],
      '#description' => $this->t('A comma separated list of css selectors for which swiping behaviour is disabled, when those are in focus; i.e. <em>.no-swipe, button, input</em>'),
    ];

    $form['swiper_options']['slides']['grabCursor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grab cursor type'),
      '#default_value' => $default_values['grabCursor'],
      '#description' => $this->t('This is basically CSS <em>cursor: grab</em>, may be useful on desktops. Does not work with CSS mode.'),
    ];

    $form['swiper_options']['slides']['cssMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('CSS mode'),
      '#default_value' => $default_values['cssMode'],
      '#description' => $this->t('When enabled it will use modern CSS Scroll Snap API. It doesn\'t support all of Swiper\'s features, but potentially should bring a much better performance in simple configurations. Please make sure to check <a href="https://swiperjs.com/swiper-api#param-cssMode" target="_blank">here</a>'),
    ];

    $form['swiper_options']['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation settings'),
      '#open' => TRUE,
      '#description' => $this->t('Swiper Navigation module, see <a target"_blank" href="https://swiperjs.com/swiper-api#navigation">Swiper.js | Navigation</a>.'),
      '#open' => TRUE,
    ];

    $form['swiper_options']['navigation']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable navigation'),
    // $this->entity->get('navigation_enabled'),
      '#default_value' => $default_values['navigation']['enabled'],
      '#description' => $this->t("Show Swiper's prev/next buttons."),
    ];

    $form['swiper_options']['navigation']['hideOnClick'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide on click'),
      '#default_value' => $default_values['navigation']['hideOnClick'],
      '#description' => $this->t("Toggle navigation buttons visibility after click on Swiper's container."),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[navigation][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay settings'),
      '#description' => $this->t('Swiper Autoplay module, see <a target="_blank" href="https://swiperjs.com/swiper-api#autoplay">Swiper.js | Autoplay</a>.'),
      '#open' => TRUE,
    ];

    $form['swiper_options']['autoplay']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autoplay'),
      '#default_value' => $default_values['autoplay']['enabled'],
    ];

    $form['swiper_options']['autoplay']['delay'] = [
    // $form['swiper_options']['autoplay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay in ms'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['delay'],
      '#description' => $this->t('Set amount of milliseconds after which Swiper will automatically swipe to the next slide.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay']['disableOnInteraction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable on interaction'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['disableOnInteraction'],
      '#description' => $this->t('Enable/disable autoplay on user interaction.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay']['pauseOnMouseEnter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on mouse enter'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['pauseOnMouseEnter'],
      '#description' => $this->t('Pause autoplay on mouse enter.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay']['reverseDirection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reverse direction'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['reverseDirection'],
      '#description' => $this->t('Enables autoplay in reverse direction.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay']['stopOnLastSlide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stop on last slide'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['stopOnLastSlide'],
      '#description' => $this->t('Stop autoplay when last slide is reached.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['autoplay']['waitForTransition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for transition'),
    // $this->entity->get('delay'),
      '#default_value' => $default_values['autoplay']['waitForTransition'],
      '#description' => $this->t('Waits for transition to continue autoplay.'),
    // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[autoplay][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['pagination'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination'),
      '#open' => TRUE,
      '#description' => $this->t('Swiper Navigation module, see <a target="_blank" href="https://swiperjs.com/swiper-api#pagination">Swiper.js | Pagination</a>.'),
    ];

    $form['swiper_options']['pagination']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pagination'),
    // $this->entity->get('pagination_enabled'),
      '#default_value' => $default_values['pagination']['enabled'],
      '#description' => $this->t('Enable this for more options.'),
    ];

    $form['swiper_options']['pagination']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pagination type'),
      // '#maxlength' => 255,
      '#options' => [
        'bullets' => $this->t('Bullets'),
        'progressbar' => $this->t('Progressbar'),
        'custom<' => $this->t('Custom'),
      ],
      '#default_value' => $default_values['pagination']['type'],
      '#description' => $this->t('Setting to "Custom" obviously requires implementation of <em>renderCustom()</em> callback somewhere in your code. See more about it <a target="_blank" href="https://swiperjs.com/swiper-api#pagination">here</a>.'),
      // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[pagination][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['pagination']['dynamicBullets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dynamic bullets'),
      '#default_value' => $default_values['pagination']['dynamicBullets'],
      '#description' => $this->t('May be handy and "fancy" with a bigger number of bullets/slides'),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[pagination][enabled]"]' => ['checked' => TRUE],
          ':input[name="swiper_options[pagination][type]"]' => ['value' => 'bullets'],
        ],
      ],
    ];

    $form['swiper_options']['pagination']['clickable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bullets clickable'),
      '#default_value' => $default_values['pagination']['clickable'],
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[pagination][enabled]"]' => ['checked' => TRUE],
          ':input[name="swiper_options[pagination][type]"]' => ['value' => 'bullets'],
        ],
      ],
    ];

    $form['swiper_options']['lazy'] = [
      '#type' => 'details',
      '#title' => $this->t('Lazy loading settings'),
      '#open' => TRUE,
      '#description' => $this->t('Swiper Lazy Loading module, see <a target="_blank" href="https://swiperjs.com/swiper-api#lazy-loading">Swiper.js | Lazy Loading</a>.'),
    ];

    $form['swiper_options']['lazy']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable lazy loading'),
      '#default_value' => $default_values['lazy']['enabled'],
      '#description' => $this->t('For images only. Includes Swiper pre-loader animation.'),
    ];

    $form['swiper_options']['lazy']['checkInView'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check in view'),
      '#default_value' => $default_values['lazy']['checkInView'],
      '#description' => $this->t('Enables to check is the Swiper in view before lazy loading images on initial slides.'),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['lazy']['loadOnTransitionStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load on transition start'),
      '#default_value' => $default_values['lazy']['loadOnTransitionStart'],
      '#description' => $this->t('By default, Swiper will load lazy images after transition to this slide, so you may enable this parameter if you need it to start loading of new image in the beginning of transition.'),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['lazy']['loadPrevNext'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load prev/next'),
      '#default_value' => $default_values['lazy']['loadPrevNext'],
      '#description' => $this->t('Enable lazy loading for the closest slides images (for previous and next slide images).'),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['lazy']['loadPrevNextAmount'] = [
      '#type' => 'number',
      '#title' => $this->t('Load prev/next amount'),
      '#default_value' => $default_values['lazy']['loadPrevNextAmount'],
      '#description' => $this->t("Amount of next/prev slides to preload lazy images in. Can't be less than Slides per view."),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['swiper_options']['lazy']['scrollingElement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scrolling element'),
      '#default_value' => $default_values['lazy']['scrollingElement'],
      '#description' => $this->t('Element to check scrolling on for checkInView. Defaults to window.'),
      '#states' => [
        'visible' => [
          ':input[name="swiper_options[lazy][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $swiper_options = [];
    foreach ($form_state->getValues() as $key => $values) {
      if (is_array($values)) {

        foreach ($values as $option => $value) {
          if (in_array($option, static::SWIPER_MODULES)) {

            // Process some special form elements, in order to
            // have the values fit to Swipers values types.
            $this->elementsHandler($form[$key][$option], $value);

            $swiper_options[$option] = $value;
          }
          else {
            foreach ($value as $sub_option => $sub_value) {

              // Process some special form elements, in order to
              // have the values fit to Swipers values types.
              $this->elementsHandler($form[$key][$option][$sub_option], $sub_value);

              $swiper_options[$sub_option] = $sub_value;
            }
          }
        }
      }
      else {
        $this->elementsHandler($form[$key], $values);
        $swiper_options[$key] = $values;

        if ($key == 'enabled') {
          $this->entity->set('status', $values);
        }
      }

      $this->entity->setSwiper($swiper_options);
    }

    // Now save entity.
    if ($this->entity->save()) {
      $this->messenger()->addStatus($this->t('Swiper %label saved.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Error: Swiper %label was not saved.', [
        '%label' => $this->entity->label(),
      ]));
    }

    // Go back to a page with collection of Swiper entities.
    $form_state->setRedirect('entity.swiper_formatter.collection');
  }

  /**
   * Process "effect" radios.
   *
   * @param array $element
   *   Form "effect" radios element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Form "effect" radios element.
   */
  public static function processEffect(array &$element, FormStateInterface $form_state) {
    $element['creative']['#disabled'] = TRUE;
    return $element;
  }

  /**
   * Check whether an swiper configuration entity exists.
   *
   * @param string $id
   *   Swiper config entity ID property.
   */
  public function exist($id) {
    return (bool) $this->entityTypeManager->getStorage('swiper_formatter')->load($id);
  }

  /**
   * Process some special form elements.
   *
   * Basically place some values up to fit to Swiper settings object structure.
   *
   * @param array $element
   *   Form element.
   * @param mixed $value
   *   Form element's value.
   */
  protected function elementsHandler(array $element, &$value) {
    if ($element['#type'] == 'checkbox') {
      if ($value == 0) {
        $value = FALSE;
      }
      if ($value == 1) {
        $value = TRUE;
      }
    }
    elseif ($element['#type'] == 'number') {
      $value = empty($value) ? NULL : (int) $value;
    }
    else {
      $value = empty($value) ? NULL : $value;
    }
  }

}
