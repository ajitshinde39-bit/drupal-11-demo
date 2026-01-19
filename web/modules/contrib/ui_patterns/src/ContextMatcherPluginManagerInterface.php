<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Trait for handling context matcher plugin managers.
 */
interface ContextMatcherPluginManagerInterface {

  /**
   * Get definitions matching the contexts and tags.
   *
   *  In addition to getDefinitionsForContexts(), this method
   *  should check context_definitions of plugins according to their keys.
   *  When required in def, a context must be present with same key,
   *  and it must satisfy the context definition.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Contexts.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *     The array keys are the tags, and the values are boolean.
   *     If the value is TRUE, the tag is required.
   *     If the value is FALSE, the tag is forbidden.
   *
   * @return array<string, array<string, mixed> >
   *   Plugin definitions
   */
  public function getDefinitionsMatchingContextsAndTags(array $contexts = [], ?array $tag_filter = NULL) : array;

  /**
   * Filters definitions by tags.
   *
   * @param array $definitions
   *   The definitions.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *    The array keys are the tags, and the values are boolean.
   *    If the value is TRUE, the tag is required.
   *    If the value is FALSE, the tag is forbidden.
   *
   * @return array
   *   The filtered definitions.
   */
  public static function filterDefinitionsByTags(array $definitions, array $tag_filter): array;

}
