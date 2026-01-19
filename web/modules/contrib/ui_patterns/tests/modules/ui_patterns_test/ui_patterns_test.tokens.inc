<?php

/**
 * @file
 * Builds placeholder replacement tokens for entity-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function ui_patterns_test_token_info(): array {
  $type = [
    'name' => t('Node Tests'),
    'description' => t('Tokens related to individual content items, or "nodes".'),
    'needs-data' => 'entity_test',
  ];

  // Core tokens for nodes.
  $entity['id'] = [
    'name' => t("Entity ID"),
    'description' => t('The unique ID of the content item, or "entity_test".'),
  ];

  return [
    'types' => ['node' => $type],
    'tokens' => ['node' => $entity],
  ];
}

/**
 * Implements hook_tokens().
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
function ui_patterns_test_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    $entity = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'id':
          $replacements[$original] = $entity->id();
          break;

        case 'name':
          $replacements[$original] = $entity->name();
          break;

      }
    }
  }
  return $replacements;
}
