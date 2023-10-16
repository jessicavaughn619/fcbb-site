<?php

namespace Drupal\fcbb_main\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * A test Twig extension that adds a custom function and a custom filter.
 */
class MpbTwigFilter extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters()
  {
    return [
      'currentnode' => new TwigFilter('currentnode', [$this, 'getCurrentNode']),
    ];
  }
  /**
     * Gets a unique identifier for this Twig extension.
     *
     * @return string
     *   A unique identifier for this Twig extension.
     */
    public function getName()
    {
      return 'fcbb_main.twig_filter';
    }

  public function getCurrentNode($input) {
    // Get the current node
    $node = \Drupal::routeMatch()->getParameter('node');
    // If the node doesn't exist, check if we are editing existing content with a revision
    if (!$node) {
      $revision = \Drupal::routeMatch()->getParameter('node_revision');
      if ($revision) {
        // Load the specified revision of content
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadRevision($revision);
      }
    }
    if($node) {
      $title = $node->getTitle();
      $allowed_values = [
        'title' => $title,
        'label' => $title,
      ];
      if($node->access('view')){
        $allowed_values['id'] = $node->id();
        $allowed_values['type'] = $node->getType();
        $allowed_values['url'] = $node->toUrl()->toString();
      }
      if($node->hasField('field_author')){
        $allowed_values['author'] = null;
        if($node->get('field_author')->getValue()){
          $author = $node->field_author->entity;
          $author_render_array = \Drupal::entityTypeManager()
            ->getViewBuilder('node')
            ->view($author, 'author_block');
          $allowed_values['author'] = $author_render_array;
        }
      }
      if($input && isset($allowed_values[$input])) {
        return $allowed_values[$input];
      }
    }
  }
}
