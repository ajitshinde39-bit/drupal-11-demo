<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ui_patterns_legacy\ConfigurationConverter;

/**
 * Service to update Layout Builder sections using ui_patterns.
 */
class ConfigurationUpdater implements ConfigurationUpdaterInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigManagerInterface $configManager,
    protected ConfigFactoryInterface $configFactory,
    protected ConfigurationConverter $configurationConverter,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function migrateConfiguration(string $filter = '*'): void {
    // Get all config entities that depends on ui_patterns.
    $dependencies = $this->configManager->findConfigEntityDependencies('module', [
      'ui_patterns',
      'ui_patterns_ds',
      'ui_patterns_entity_links',
      'ui_patterns_field_formatters',
      'ui_patterns_field_group',
      'ui_patterns_layouts',
      'ui_patterns_library',
      'ui_patterns_pattern_block',
      'ui_patterns_settings',
      'ui_patterns_views',
      'ui_patterns_views_style',
    ]);
    $dependenciesIds = \array_map(static fn ($item) => $item->getConfigDependencyName(), $dependencies);

    // Get all config entity_view_display entities.
    $entityViewDisplaysIds = $this->configFactory->listAll('core.entity_view_display.');
    $entityViewDisplaysIds = \array_combine($entityViewDisplaysIds, $entityViewDisplaysIds);
    $dependenciesIds = \array_merge($dependenciesIds, $entityViewDisplaysIds);

    // Only keep config entities matching the filter.
    $regexFilter = '/^' . \str_replace('*', '.*', $filter) . '$/i';
    $filteredDependenciesIds = \array_filter($dependenciesIds, static fn ($name) => (bool) \preg_match($regexFilter, $name));

    // Convert each config entity.
    foreach ($filteredDependenciesIds as $filteredDependencyId) {
      $configEntity = $this->configFactory->getEditable($filteredDependencyId);
      $converted = $this->configurationConverter->convert($configEntity->getRawData());
      $configEntity->setData($converted);
      $configEntity->save();
      $this->messenger->addStatus($this->t('@config has been converted!', [
        '@config' => $configEntity->getName(),
      ]));
    }
  }

}
