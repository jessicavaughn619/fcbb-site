<?php

namespace Drupal\swiper_formatter\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\swiper_formatter\SwiperFormatterTrait;

/**
 * Plugin implementation of the 'Swiper' formatter.
 *
 * @FieldFormatter(
 *   id = "swiper_formatter_image",
 *   label = @Translation("Swiper images"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SwiperFormatterImage extends ImageFormatter {
  use SwiperFormatterTrait;

}
