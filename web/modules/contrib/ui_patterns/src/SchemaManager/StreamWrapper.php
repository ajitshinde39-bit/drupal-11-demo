<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\SchemaManager;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;

/**
 * Defines the read-only ui-patterns:// stream wrapper for prop types.
 */
class StreamWrapper extends LocalReadOnlyStream {

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    // phpcs:enable
    $plugin_id = str_replace('ui-patterns://', '', $uri);
    /** @var \Drupal\ui_patterns\PropTypeInterface $plugin */
    $plugin = \Drupal::service('plugin.manager.ui_patterns_prop_type')->createInstance($plugin_id);
    $stream = fopen('php://memory', 'r+');
    $schema = json_encode($plugin->getSchema());
    if ($stream && $schema) {
      fwrite($stream, $schema);
      rewind($stream);
      $this->handle = $stream;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() : string {
    return "";
  }

  /**
   * {@inheritdoc}
   */
  public function getName() : string {
    return 'ui_patterns';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return 'ui_patterns';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() : string {
    return "";
  }

}
