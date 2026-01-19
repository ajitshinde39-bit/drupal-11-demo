<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library\Template;

use Drupal\ui_patterns_library\StoryPluginManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension providing UI Patterns Legacy functionalities.
 *
 * @package Drupal\ui_patterns_library\Template
 */
class TwigExtension extends AbstractExtension {

  public function __construct(protected StoryPluginManager $storyPluginManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'ui_patterns_library';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('component_story', [
        $this,
        'renderComponentStory',
      ]),
    ];
  }

  /**
   * Render given component story.
   *
   * @param string $component_id
   *   Component ID.
   * @param string $story_id
   *   Story ID.
   * @param array $slots
   *   Component slots to override.
   * @param array $props
   *   Component props to override.
   * @param bool $with_wrapper
   *   Wrap the story into a markup.
   *
   * @return array
   *   Pattern render array.
   *
   * @see \Drupal\Core\Theme\Element\ComponentElement
   */
  public function renderComponentStory(string $component_id, string $story_id, array $slots = [], array $props = [], bool $with_wrapper = FALSE) {
    $renderable = [
      '#type' => 'component',
      '#component' => $component_id,
      '#story' => $story_id,
      '#slots' => $slots,
      '#props' => $props,
    ];
    if (!$with_wrapper) {
      return $renderable;
    }
    $story = $this->storyPluginManager->getComponentStories($component_id)[$story_id] ?? [];
    if (!isset($story["library_wrapper"]) || empty($story["library_wrapper"])) {
      return $renderable;
    }
    return [
      '#type' => 'inline_template',
      '#template' => $story["library_wrapper"],
      '#context' => $slots + $props + ['_story' => $renderable],
    ];
  }

}
