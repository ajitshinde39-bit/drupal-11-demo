<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy\Service;

use Drupal\layout_builder\Section;

/**
 * Layout Builder updater interface methods.
 */
interface LayoutBuilderUpdaterInterface {

  public const PATTERN_PREFIX = 'pattern_';

  public const COMPONENT_PREFIX = 'ui_patterns:';

  public const COMPONENT_NAMESPACE_PARTS = 2;

  /**
   * Update layout overrides.
   *
   * @param \Drupal\layout_builder\Section $section
   *   The Layout Builder section.
   *
   * @return \Drupal\layout_builder\Section|false
   *   A section if the section had been updated.
   */
  public function updateLayout(Section $section): FALSE|Section;

}
