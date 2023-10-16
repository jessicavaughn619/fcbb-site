<?php
/**
 * @file
 * Contains Drupal\fcbb_main\Form\DefaultParagraphSettingsForm.
 */
namespace Drupal\fcbb_main\Form;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class DefaultParagraphSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The uuid service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;
  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The uuid object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Php $uuid, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fcbb_main.default_paragraph_settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fcbb_main_default_paragraph_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_paragraph_config = $this->config('fcbb_main.default_paragraph_settings');
    // make a fieldset for each content type
    foreach($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $content_type) {
      $content_type_id = $content_type->id();
      $content_type_label = $content_type->label();
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type_id);
      $field_options = [];
      foreach($fields as $field_name => $field) {
        if($field->getType() == 'entity_reference_revisions') {
          $field_options[$field_name] = $field->getLabel();
        }
      }
      if(empty($field_options)) {
        // no paragraphs fields on this content type
        // skip it
        continue;
      }
      $form[$content_type_id] = [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#title' => $content_type_label,
      ];
      $form[$content_type_id][$content_type_id . '_paragraphs_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Field to add default paragraphs to'),
        '#options' => $field_options,
        '#default_value' => $default_paragraph_config->get($content_type_id . '_paragraphs_field')?:'',
      ];
      // default paragraphs to add
      // make an multiselect autocomplete field for picking a paragraph library_item
      // this will be a comma separated list of library_item ids
      $ids = $default_paragraph_config->get($content_type_id . '_default_paragraphs') ? array_column($default_paragraph_config->get($content_type_id . '_default_paragraphs'), 'target_id') : [];
      $default_value = !empty($ids)?$this->entityTypeManager->getStorage('paragraphs_library_item')->loadMultiple($ids):'';
      $form[$content_type_id][$content_type_id . '_default_paragraphs'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Default Paragraphs'),
        '#target_type' => 'paragraphs_library_item',
        '#tags' => TRUE,
        '#default_value' => $default_value,
        '#selection_settings' => [

        ],
      ];
    }

    $form['uuid'] = [
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
    $default_paragraph_config = $this->config('fcbb_main.default_paragraph_settings');
    $values = $form_state->getValues();
    foreach($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $content_type) {
      $content_type_id = $content_type->id();
      if(!isset($values[$content_type_id . '_paragraphs_field'])) {
        continue;
      }
      $default_paragraph_config->set($content_type_id . '_paragraphs_field', $values[$content_type_id . '_paragraphs_field']);
      $default_paragraph_config->set($content_type_id . '_default_paragraphs', $values[$content_type_id . '_default_paragraphs']);
    }
    $default_paragraph_config->set('uuid', $values['uuid']);
    $default_paragraph_config->save();
  }
}
