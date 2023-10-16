<?php
/**
 * @file
 * Contains Drupal\fcbb_main\Form\AnnouncementSettingsForm.
 */
namespace Drupal\fcbb_main\Form;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnnouncementSettingsForm extends ConfigFormBase {

  /**
   * The uuid service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The uuid object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Php $uuid) {
    parent::__construct($config_factory);

    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fcbb_main.announcement_settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fcbb_main_announcement_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $announcement_config = $this->config('fcbb_main.announcement_settings');
    $show_top_message = $announcement_config->get('show_top_message')?:false;
    $top_message = $announcement_config->get('top_message')?:'';
    $top_message_es = $announcement_config->get('top_message_es')?:'';
    $show_bottom_message = $announcement_config->get('show_bottom_message')?:false;
    $bottom_message = $announcement_config->get('bottom_message')?:'';
    $bottom_message_es = $announcement_config->get('bottom_message_es')?:'';

    $form['miles_top_message'] = [
      '#type' => 'fieldset',
      '#title' => t('Top Message Text'),
      '#collapsible' => TRUE,
    ];
    $form['miles_top_message']['show_top_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show this message'),
      '#default_value' => $show_top_message,
    ];
    $form['miles_top_message']['top_message'] = [
      '#type' => 'text_format',
      '#format' => 'basic_html',
      '#title' => $this->t('Message'),
      '#default_value' => $top_message,
    ];

    $form['miles_bottom_message'] = [
      '#type' => 'fieldset',
      '#title' => t('Bottom Message Text'),
      '#collapsible' => TRUE,
    ];
    $form['miles_bottom_message']['show_bottom_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show this message'),
      '#default_value' => $show_bottom_message,
    ];
    $form['miles_bottom_message']['bottom_message'] = [
      '#type' => 'text_format',
      '#format' => 'basic_html',
      '#title' => $this->t('Message'),
      '#default_value' => $bottom_message,
    ];
    $form['miles_announcement_uuid'] = [
      '#type' => 'hidden',
      '#title' => $this->t('UUID'),
      '#value' => $this->uuid->generate(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('fcbb_main.announcement_settings')
      ->set('show_top_message', $form_state->getValue(['show_top_message']))
      ->set('top_message', $form_state->getValue(['top_message','value']))
      ->set('show_bottom_message', $form_state->getValue(['show_bottom_message']))
      ->set('bottom_message', $form_state->getValue(['bottom_message','value']))
      ->set('announcement_uuid', $form_state->getValues()['miles_announcement_uuid'])
      ->save();

    // Done to avoid announcement banner message caching.
    drupal_flush_all_caches();
  }
}
