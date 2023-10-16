<?php

namespace Drupal\fcbb_main\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

class DefaultParagraphsService {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function getReferencedParagraph(EntityInterface $entity): EntityInterface {
    // get the original paragraph entity from 'paragraphs_library_item' or 'from_library'
    if ($entity->hasField('field_reusable_paragraph') && $library_item = $entity->get('field_reusable_paragraph')->entity) {
      return $library_item->get('paragraphs')->entity;
    }
    if ($entity->hasField('paragraphs') && $paragraph = $entity->get('paragraphs')->entity) {
      return $paragraph;
    }
    return $entity;
  }
  public function attachDefaultParagraphs(EntityInterface &$entity, string $field_name, array $default_paragraph_blocks = null): void {
    // $default_paragraph_blocks is a key, value Array consisting of [ expected paragraph type => library id ];
    if (!$default_paragraph_blocks || !$entity->hasField($field_name)) {
      return;
    }
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $library_storage = $this->entityTypeManager->getStorage('paragraphs_library_item');
    $field = $entity->get($field_name);

    // get the current types already on the field
    $current_types = array_map(fn($item) => $this->getReferencedParagraph($item)->bundle(), $field->referencedEntities());

    foreach ($default_paragraph_blocks as $expected_type => $library_id) {
      if ($library_item = $library_storage->load($library_id)) {
        $type = $this->getReferencedParagraph($library_item)->bundle();
        // make sure the field doesnt already have a paragraph of this type
        if (!in_array($type, $current_types) && $type === $expected_type) {
          // create a 'from_library' from the library_item
          $paragraph = \Drupal\paragraphs\Entity\Paragraph::create([
            'type' => 'from_library',
            'field_reusable_paragraph' => $library_item,
          ]);
          $field->appendItem($paragraph);
        }
      }
    }
  }
}
