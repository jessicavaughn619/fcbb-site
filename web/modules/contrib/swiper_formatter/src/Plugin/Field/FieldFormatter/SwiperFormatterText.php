<?php

namespace Drupal\swiper_formatter\Plugin\Field\FieldFormatter;

use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Drupal\swiper_formatter\SwiperFormatterTrait;

/**
 * Plugin implementation of the 'swiper_formatter_text' formatter variant.
 *
 * @FieldFormatter(
 *   id = "swiper_formatter_text",
 *   label = @Translation("Swiper markup"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class SwiperFormatterText extends TextDefaultFormatter {
  use SwiperFormatterTrait;

}
