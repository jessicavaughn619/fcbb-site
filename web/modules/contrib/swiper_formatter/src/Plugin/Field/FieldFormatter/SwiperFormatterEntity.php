<?php

namespace Drupal\swiper_formatter\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Messenger\MessengerInterface;

use Drupal\swiper_formatter\SwiperFormatterTrait;

/**
 * Plugin implementation of the 'swiper_formatter_text' formatter variant.
 *
 * @FieldFormatter(
 *   id = "swiper_formatter_entity",
 *   label = @Translation("Swiper entity"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class SwiperFormatterEntity extends EntityReferenceEntityFormatter {
  use SwiperFormatterTrait;

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
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager,
    RedirectDestinationInterface $destination,
    MessengerInterface $messenger) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_type_manager, $entity_display_repository);

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
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('redirect.destination'),
      $container->get('messenger')
    );
  }

}
