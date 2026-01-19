<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\EventSubscriber;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity\EntityTypeEventSubscriberTrait;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionEvent;
use Drupal\Core\Field\FieldStorageDefinitionEventSubscriberTrait;
use Drupal\ui_patterns\DerivableContextPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UiPatternsEntitySchemaSubscriber.
 *
 * Allow to do things when the entity types change. For example, rebuild the
 * source plugin definitions.
 */
class UiPatternsEntitySchemaSubscriber implements EntityTypeListenerInterface, EventSubscriberInterface {

  use EntityTypeEventSubscriberTrait;
  use FieldStorageDefinitionEventSubscriberTrait;

  /**
   * Constructs a ViewsEntitySchemaSubscriber.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $sourceManager
   *   The entity type manager.
   * @param \Drupal\ui_patterns\DerivableContextPluginManager $derivableContextPluginManager
   *   The derivable context plugin manager.
   */
  public function __construct(protected CachedDiscoveryInterface $sourceManager, protected DerivableContextPluginManager $derivableContextPluginManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return array_merge(
      static::getEntityTypeEvents(),
      static::getFieldStorageDefinitionEvents(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeEvent(EntityTypeEvent $event, string $event_name): void {
    $this->sourceManager->clearCachedDefinitions();
    $this->derivableContextPluginManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionEvent(FieldStorageDefinitionEvent $event, string $event_name): void {
    $this->sourceManager->clearCachedDefinitions();
    $this->derivableContextPluginManager->clearCachedDefinitions();
  }

}
