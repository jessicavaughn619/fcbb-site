<?php

namespace Drupal\swiper_formatter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

use Drupal\swiper_formatter\SwiperFormatterInterface;

/**
 * Defines the Nk tools swiper entity type.
 *
 * @ConfigEntityType(
 *   id = "swiper_formatter",
 *   label = @Translation("Swiper"),
 *   label_collection = @Translation("Swipers"),
 *   label_singular = @Translation("swiper"),
 *   label_plural = @Translation("swipers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count swiper",
 *     plural = "@count swipers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\swiper_formatter\SwiperFormatterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\swiper_formatter\Form\SwiperFormatterForm",
 *       "edit" = "Drupal\swiper_formatter\Form\SwiperFormatterForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "swiper_formatter",
 *   admin_permission = "administer swiper_formatter",
 *   links = {
 *     "collection" = "/admin/structure/swiper-formatter",
 *     "add-form" = "/admin/structure/swiper-formatter/add",
 *     "edit-form" = "/admin/structure/swiper-formatter/{swiper_formatter}",
 *     "delete-form" = "/admin/structure/swiper-formatter/{swiper_formatter}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "status",
 *     "swiper_options"
 *   }
 * )
 */
class SwiperFormatter extends ConfigEntityBase implements SwiperFormatterInterface {

  /**
   * The swiper ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Swiper entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * Swiper entity description.
   *
   * @var string
   */
  protected $description;

  /**
   * Swiper entity status.
   *
   * @var bool
   */
  protected $status;

  /**
   * A collection of all of the Swiper's properties into a single array.
   *
   * @var array
   */
  public $swiper_options = [];

  /**
   * Swipers getter.
   */
  public static function getSwipers() {
    $swiper_options = [];
    $swipers = static::loadMultiple();
    if (!empty($swipers)) {
      foreach ($swipers as $swiper_entity) {
        $swiper_entity_storage = $swiper_entity->load($swiper_entity->id());
        $swiper_options[$swiper_entity->id()] = [
          'id' => $swiper_entity->id(),
          'label' => $swiper_entity_storage->label(),
          'properties' => $swiper_entity_storage->toArray(),
        ];
      }
    }
    return $swiper_options;
  }

  /**
   * Swipers setter.
   *
   * @param array $swiper_options
   *   An array of options to assing to property.
   */
  public function setSwiper(array $swiper_options = []) {
    $this->swiper_options = $swiper_options;
    return $this;
  }

  /**
   * Prepare #options for swiper template options form field.
   */
  public static function getSwiperTemplates() {
    $swipers = static::getSwipers();
    if (!empty($swipers)) {
      $templates = [];
      foreach ($swipers as $id => $swiper) {
        $templates[$id] = $swiper['label'];
      }
    }
    return $templates;
  }

}
