<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\SchemaManager;

/**
 * Normalize schema before checking its compatibility.
 */
class Canonicalizer {

  /**
   * Canonicalize schema.
   */
  public function canonicalize(array $schema): array {
    $schema = $this->resolveQuirks($schema);
    $schema = $this->keepOnlyUsefulProperties($schema);
    if (array_key_exists("type", $schema)) {
      $schema = $this->canonicalizeType($schema);
    }
    if (array_key_exists("anyOf", $schema)) {
      foreach ($schema["anyOf"] as $index => $sub_schema) {
        $schema["anyOf"][$index] = $this->canonicalize($sub_schema);
      }
    }
    ksort($schema);
    return $schema;
  }

  /**
   * Canonicalize schema using type property .
   */
  protected function canonicalizeType(array $schema): array {
    if (!isset($schema["type"])) {
      return $schema;
    }
    if (is_array($schema["type"])) {
      $schema = $this->resolveMultipleTypes($schema);
      return $this->canonicalize($schema);
    }
    if ($schema["type"] === "object" && isset($schema["properties"])) {
      foreach ($schema["properties"] as $property_id => $property) {
        $schema["properties"][$property_id] = $this->canonicalize($property);
      }
    }
    if ($schema["type"] === "array" && isset($schema["items"])) {
      $schema["items"] = $this->canonicalize($schema["items"]);
    }
    if ($schema["type"] === "int") {
      // Some SDC themes are using this wrong JSON schema type.
      // Example: https://www.drupal.org/project/kinetic
      $schema["type"] = "integer";
    }
    return $schema;
  }

  /**
   * Convert multiple type property values as anyOf property.
   */
  protected function resolveMultipleTypes(array $schema): array {
    if (!is_array($schema["type"])) {
      return $schema;
    }
    $schemas = [
      "anyOf" => [],
    ];
    foreach ($schema["type"] as $index => $type) {
      $sub_schema = $schema;
      $sub_schema["type"] = $type;
      $schemas["anyOf"][$index] = $sub_schema;
    }
    return $schemas;
  }

  /**
   * Resolve common JSON schema mistakes.
   */
  protected function resolveQuirks(array $schema): array {
    if (isset($schema["type"]) && $schema["type"] === "array" && isset($schema["properties"])) {
      // Some SDC themes are using "properties" with arrays instead of "items".
      // Example: https://www.drupal.org/project/kinetic
      $schema["items"] = $schema["properties"];
      unset($schema["properties"]);
    }
    return $schema;
  }

  /**
   * Keep only useful properties.
   */
  protected function keepOnlyUsefulProperties(array $schema): array {
    $keys = [
      "anyOf", "allOf", "oneOf", "not", "enum", "type", '$ref', "const",
    ];
    $keys_by_type = [
      "string" => ["minLength", "maxLength", "pattern", "format"],
      "number" => ["minimum", "maximum", "exclusiveMinimum", "exclusiveMaximum",
        "multipleOf",
      ],
      "integer" => ["minimum", "maximum", "exclusiveMinimum", "exclusiveMaximum",
        "multipleOf",
      ],
      "boolean" => [],
      "null" => [],
      "array" => ["minItems", "maxItems", "items", "additionalItems",
        "uniqueItems",
      ],
      "object" => ["properties", "additionalProperties", "required",
        "minProperties", "maxProperties", "dependencies", "patternProperties",
      ],
    ];
    if (isset($schema["type"]) && is_string($schema["type"])) {
      $type = $schema["type"];
      if (array_key_exists($type, $keys_by_type)) {
        $keys = array_merge($keys, $keys_by_type[$type]);
      }
    }
    return array_intersect_key($schema, array_flip($keys));
  }

}
