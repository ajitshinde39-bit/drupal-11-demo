<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'breadcrumb',
  label: new TranslatableMarkup('Breadcrumb'),
  description: new TranslatableMarkup('Breadcrumb source plugin.'),
  prop_types: ['links']
)]
class BreadcrumbSource extends SourcePluginBase {

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);
    $plugin->breadcrumbManager = $container->get('breadcrumb');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $breadcrumb = $this->breadcrumbManager->build($this->routeMatch);
    $links = [];
    foreach ($breadcrumb->getLinks() as $link) {
      $links[] = [
        "title" => $link->getText(),
        "url" => $link->getUrl()->toString(),
      ];
    }
    return $links;

  }

  /**
   * {@inheritdoc}
   */
  public function alterComponent(array $element): array {
    $breadcrumb = $this->breadcrumbManager->build($this->routeMatch);
    $renderable = $breadcrumb->toRenderable();

    $breadcrumbCache = CacheableMetadata::createFromRenderArray($renderable);
    $cache = CacheableMetadata::createFromRenderArray($element);
    $cache->merge($breadcrumbCache);
    $cache->applyTo($element);
    return $element;
  }

}
