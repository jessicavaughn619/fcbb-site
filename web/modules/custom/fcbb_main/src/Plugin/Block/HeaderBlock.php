<?php

namespace Drupal\fcbb_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 *
 * @Block(
 *   id = "header_block",
 *   admin_label = @Translation("Global Header Block"),
 * )
 */
class HeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'header_block',
      '#description' => 'Header Block',
    ];

    return $build;
  }

  public function getCacheTags() {
    if ($node = get_current_node_or_revision()) {
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
