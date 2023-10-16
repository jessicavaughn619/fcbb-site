<?php

use Drupal\Component\Utility\Xss;

/**
 * Implements hook_theme().
 */
function fcbb_main_theme($existing, $type, $theme, $path) {
  $theme_path = Drupal::theme()->getActiveTheme()->getPath();
  return [
    'header_block' => [
      // 'template' => 'global-header',
      'variables' => [],
      'path' => $theme_path . '/templates'
    ],
    'footer_block' => [
      // 'template' => 'global-footer',
      'variables' => [],
      'path' => $theme_path . '/templates'
    ],
    'style_tile' => [
      'template' => 'style-tile',
      'variables' => [],
      'path' => $theme_path . '/templates'
    ],
    'announcement_banner' => [
      'template' => 'announcement-banner',
      'variables' => [],
      'path' => $theme_path . '/templates'
    ]
  ];
}

function fcbb_main_theme_suggestions_alter(&$suggestions, &$vars, $hook){
  if(in_array($hook, ['header_block', 'footer_block'])){
    $current_node = get_current_node_or_revision();
    $bundle = $current_node ? $current_node->bundle() : null;
    if($bundle){
      $suggestions[] = $hook . '__' . $bundle;
    }
  }
}

function fcbb_main_preprocess_header_block(&$variables) {
  $theme_path = Drupal::theme()->getActiveTheme()->getPath();
  $variables['theme_path'] = '/' . $theme_path;
  $variables['logo_svg'] = file_get_contents($theme_path . '/images/logo.png');
  $variables['logo_url'] = '/' . $theme_path . '/images/logo.png';
  $variables['main_menu'] = _render_menu_links('main', 10);
  $variables['social_links'] = _render_menu_links('social-links', 1);
  $variables['home_url'] = '/';
}

function fcbb_main_preprocess_footer_block(&$variables) {
  $theme_path = Drupal::theme()->getActiveTheme()->getPath();
  $variables['theme_path'] = '/' . $theme_path;
  $variables['logo_svg'] = file_get_contents($theme_path . '/images/logo.png');
  $variables['logo_url'] = '/' . $theme_path . '/images/logo.png';
  // $variables['busa_logo'] = file_create_url($theme_path . '/images/busa-logo-white.svg');
  $variables['footer_menu'] = _render_menu_links('footer', 1);
  $variables['social_links'] = _render_menu_links('social-links', 1);
  $variables['subsidiary_companies'] = _render_menu_links('subsidiary-companies', 1);
  $variables['policies_legal'] = _render_menu_links('policies-legal', 1);
  $variables['home_url'] = '/';
}

function _render_menu_links($menu = 'main', $depth = 1) {
  $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
  $menu_parameters->setMaxDepth($depth);
  $menu_tree_service = \Drupal::service('menu.link_tree');
  $tree = $menu_tree_service->load($menu, $menu_parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  // SERVICE for removing untranslated links from menus
  // if(in_array($menu, ['footer', 'main', 'b2b-navigation'])){
  // $i18n_filter_service = \Drupal::service('mmg_i18n.i18n_filter_service');
  // $i18n_filter_service->filter($tree);
  // }
  $tree = $menu_tree_service->transform($tree, $manipulators);
  return $menu_tree_service->build($tree);
}

// Preprocess the announcement_banner block template.
function fcbb_main_preprocess_announcement_banner(&$variables) {
  $a_config = \Drupal::config('fcbb_main.announcement_settings');
  $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

  $variables['show_top_message'] = $a_config->get('show_top_message');
  $variables['show_bottom_message'] = $a_config->get('show_bottom_message');

  $variables['top_bgcolor'] = $a_config->get('top_bgcolor');
  $variables['bottom_bgcolor'] = $a_config->get('bottom_bgcolor');
  if($langcode != 'es'){
    $variables['top_message'] = $a_config->get('top_message');
    $variables['bottom_message'] = $a_config->get('bottom_message');
  }else{
    $variables['top_message'] = $a_config->get('top_message_es');
    $variables['bottom_message'] = $a_config->get('bottom_message_es');
  }

}
