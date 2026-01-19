<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\Context\ContextInterface;

/**
 * Trait for sources handling enum values.
 */
trait ContextMatcherPluginManagerTrait {

  /**
   * The static cache.
   *
   * @var array<string, mixed>
   */
  protected array $staticCache = [];

  /**
   * Advanced method to get source definitions for contexts.
   *
   *  In addition to getDefinitionsForContexts(), this method
   *  checks context_definitions of plugins according to their keys.
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
   * @param array|null $input_definitions
   *   The input definitions to filter or NULL to use the default definitions.
   *
   * @return array<string, array<string, mixed> >
   *   Plugin definitions
   */
  public function getDefinitionsMatchingContextsAndTags(array $contexts = [], ?array $tag_filter = NULL, ?array $input_definitions = NULL) : array {
    // Only use the cache when input_definitions is NULL,
    // so we work on default definitions.
    $cacheKey = ($input_definitions === NULL) ? $this->getHashKey(__FUNCTION__, [$contexts, $tag_filter]) : NULL;
    if ($cacheKey && isset($this->staticCache[$cacheKey])) {
      return $this->staticCache[$cacheKey];
    }
    $definitions = $this->getDefinitionsMatchingContexts($contexts, $input_definitions);
    if (is_array($tag_filter)) {
      $definitions = static::filterDefinitionsByTags($definitions, $tag_filter);
    }
    if ($cacheKey) {
      $this->staticCache[$cacheKey] = $definitions;
    }
    return $definitions;
  }

  /**
   * Return plugin definitions for a field.
   *
   * This method will try to narrow down the definitions, when some contexts
   * are provided, such as field_name and entity, and removed those that
   * do not match the field_name and entity.
   *
   * @param array|null $contexts
   *   Contexts.
   * @param array $tag_filter
   *   Tag filters to check if field is set.
   *
   * @return array
   *   The plugin definitions, possibly narrowed down by contexts, or NULL.
   */
  protected function getDefinitionsForField(?array $contexts, array $tag_filter): ?array {
    // We only want to narrow down the definitions,
    // when field_name and entity contexts are provided,
    // and when the tag_filter field is set.
    if (!is_array($contexts) || !isset($contexts["field_name"]) || !isset($contexts["entity"]) || (!($tag_filter["field"] ?? FALSE))) {
      return NULL;
    }
    $field_name_context = $contexts["field_name"];
    $entity_context = $contexts["entity"];
    $field_name = $field_name_context->getContextValue() ?? "";
    $entity_type_id = $entity_context->getContextValue()->getEntityTypeId() ?? "";
    if (empty($field_name) || empty($entity_type_id)) {
      return NULL;
    }
    $input_definitions = $this->getDefinitions();
    $cache_key = sprintf("%s--per-field--%s--%s", $this->cacheKey, $entity_type_id, $field_name);
    if (($cache = $this->cacheGet($cache_key)) && isset($cache->data)) {
      return $cache->data;
    }
    $filtered_definitions = $this->removeDefinitionNotMatchingField($input_definitions, $field_name_context, $entity_context);
    $this->cacheSet($cache_key, $filtered_definitions, Cache::PERMANENT, $this->cacheTags);
    return $filtered_definitions;
  }

  /**
   * Remove definitions that do not match the field_name and entity contexts.
   *
   * @param array $input_definitions
   *   The input definitions.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $field_name_context
   *   The field name context.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $entity_context
   *   The entity context.
   *
   * @return array
   *   The filtered definitions.
   */
  protected function removeDefinitionNotMatchingField(
    array $input_definitions,
    ContextInterface $field_name_context,
    ContextInterface $entity_context,
  ): array {
    return array_filter($input_definitions, function ($plugin_definition) use ($field_name_context, $entity_context) {
      $tags = $plugin_definition['tags'] ?? [];
      if (!in_array('field', $tags)) {
        return FALSE;
      }
       $context_definitions = $plugin_definition['context_definitions'] ?? [];
      if (isset($context_definitions['field_name']) && !$context_definitions['field_name']->isSatisfiedBy($field_name_context)) {
        return FALSE;
      }
      if (isset($context_definitions['entity']) && !$context_definitions['entity']->isSatisfiedBy($entity_context)) {
        return FALSE;
      }
       return TRUE;
    });
  }

  /**
   * Implementation of getDefinitionsMatchingContexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Contexts.
   * @param array|null $input_definitions
   *   The input definitions to filter or NULL to use the default definitions.
   *
   * @return array<string, array<string, mixed> >
   *   Plugin definitions
   */
  private function getDefinitionsMatchingContexts(array $contexts = [], ?array $input_definitions = NULL) : array {
    if ($input_definitions === NULL) {
      $input_definitions = $this->getDefinitions();
    }
    $definitions = $this->contextHandler()->filterPluginDefinitionsByContexts($contexts, $input_definitions);
    $checked_context_by_keys = [];
    foreach (array_keys($contexts) as $key) {
      $checked_context_by_keys[$key] = [];
    }
    $definitions = array_filter($definitions, function ($definition) use ($contexts, &$checked_context_by_keys) {
      $context_definitions = isset($definition['context_definitions']) ? $definition['context_definitions'] ?? [] : [];
      foreach ($context_definitions as $key => $context_definition) {
        if (!$context_definition->isRequired()) {
          continue;
        }
        if (!array_key_exists($key, $contexts)) {
          return FALSE;
        }
        $context_definition_key = hash('sha256', serialize($context_definition));
        if (!isset($checked_context_by_keys[$key][$context_definition_key])) {
          $checked_context_by_keys[$key][$context_definition_key] = $context_definition->isSatisfiedBy($contexts[$key]);
        }
        if (!$checked_context_by_keys[$key][$context_definition_key]) {
          return FALSE;
        }
      }
      return TRUE;
    });
    return $definitions;
  }

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
  public static function filterDefinitionsByTags(array $definitions, array $tag_filter): array {
    return array_filter($definitions, static function ($definition) use ($tag_filter) {
      $tags = array_key_exists("tags", $definition) ? $definition['tags'] : [];
      if (count($tag_filter) > 0) {
        foreach ($tag_filter as $tag => $tag_required) {
          $found = in_array($tag, $tags);
          if (($tag_required && !$found) || (!$tag_required && $found)) {
            return FALSE;
          }
        }
      }
      return TRUE;
    });
  }

  /**
   * Get a hash key for caching.
   *
   * @param string $key
   *   A key.
   * @param array $contexts
   *   An array of contexts.
   *
   * @return string
   *   The hash key.
   */
  private function getHashKey(string $key, array $contexts = []) : string {
    return hash("sha256", serialize([$key, $contexts]));
  }

}
