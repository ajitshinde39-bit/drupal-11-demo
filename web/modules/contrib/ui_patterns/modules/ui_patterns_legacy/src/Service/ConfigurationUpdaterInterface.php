<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy\Service;

/**
 * Configuration updater interface methods.
 */
interface ConfigurationUpdaterInterface {

  /**
   * Migrate configuration from UI Patterns 1.x to UI Patterns 2.x.
   *
   * @param string $filter
   *   Filter configuration objects to migrate.
   */
  public function migrateConfiguration(string $filter = '*'): void;

}
