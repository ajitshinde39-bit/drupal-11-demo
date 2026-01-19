<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'entity_link',
  label: new TranslatableMarkup('[Entity] Link'),
  description: new TranslatableMarkup('Url from an entity link.'),
  prop_types: ['url'], tags: ['entity'],
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: TRUE
    ),
  ]
)]
class EntityLinksSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      'template' => NULL,
      'absolute' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $urls = $this->getPropValueUrlAndString();
    return $urls["url_string"] ?? '';
  }

  /**
   * Get the setting about absolute URL.
   *
   * @return bool
   *   If the url is absolute.
   */
  protected function isAbsoluteUrl(): bool {
    $is_absolute = $this->getSetting('absolute') ?? $this->defaultSettings()['absolute'];
    return $is_absolute ? TRUE : FALSE;
  }

  /**
   * Returns the url from context and configuration.
   *
   * @return array<string, NULL|string|Url>
   *   url and url as string in an array
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getPropValueUrlAndString() : array {
    $is_absolute = $this->isAbsoluteUrl();
    $empty_return = [
      "url" => NULL,
      "url_string" => "",
    ];
    $template = $this->getSetting('template');
    if (!$template) {
      return $empty_return;
    }
    $link_templates = $this->getEntityLinkTemplates();
    if (!array_key_exists($template, $link_templates)) {
      return $empty_return;
    }
    $entity = $this->getContextValue("entity");
    $link_templates = [];
    if (($entity instanceof EntityInterface) && $entity->id()) {
      try {
        $url = $entity->toUrl($template, ["absolute" => $is_absolute]);
        $url_string = $url->toString();
        return [
          "url" => $url,
          "url_string" => $url_string,
        ];
      }
      catch (RouteNotFoundException $e) {
        $link_template_entity_type = $entity->getEntityType()->getLinkTemplate($template);
        $link_template_entity_type_url = Url::fromUri("internal:" . $link_template_entity_type, ["absolute" => $is_absolute]);
        return [
          "url" => $link_template_entity_type_url,
          "url_string" => $link_template_entity_type_url->toString(),
        ];
      }

    }
    return $empty_return;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $entity_links = $this->getEntityLinkTemplates();
    $link_templates_options = array_keys($entity_links);
    $link_templates_options = array_combine($link_templates_options, $link_templates_options);
    asort($link_templates_options);
    $form["template"] = [
      '#type' => 'select',
      '#title' => $this->t("Select"),
      '#options' => $link_templates_options,
      '#default_value' => $this->getSetting('template') ?? '',
      "#empty_option" => $this->t("- Select -"),
      '#empty_value' => '',
    ];
    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Absolute URL'),
      '#default_value' => $this->isAbsoluteUrl(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->getSetting('template') ?? '',
    ];
  }

  /**
   * Get menus list.
   *
   * @return array
   *   List of menus.
   */
  protected function getEntityLinkTemplates(): array {
    $entity = $this->getContextValue("entity");
    $link_templates = [];
    if ($entity instanceof EntityInterface) {
      $link_templates = $entity->getEntityType()->getLinkTemplates();
    }
    return $link_templates;
  }

  /**
   * {@inheritdoc}
   */
  public function alterComponent(array $element): array {
    $entity = $this->getContextValue("entity");
    if (!($entity instanceof EntityInterface)) {
      return $element;
    }

    $entityCache = CacheableMetadata::createFromObject($entity);
    $cache = CacheableMetadata::createFromRenderArray($element);
    $cache->merge($entityCache);
    $cache->applyTo($element);
    return $element;
  }

}
