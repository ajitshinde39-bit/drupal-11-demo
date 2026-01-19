<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns_library\Discovery\DirectoryWithMetadataPluginDiscovery;

/**
 * Defines a plugin manager to deal with stories.
 *
 * Modules and themes can define stories in a
 * component/{component_id}/{component_id}.{story_id}.stories.yml file.
 * Each story has the following structure:
 *
 * @code
 *   name: STRING
 *   description: STRING
 *   component: STRING
 *   slots: ARRAY
 *   slots: PROPS
 * @endcode
 *
 * @see \Drupal\ui_patterns_library\StoryDefault
 * @see \Drupal\ui_patterns_library\StoryInterface
 */
final class StoryPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The full plugin ID: {component_provider}:{component_id}:{story_id}.
    'id' => '',
    // The story machine name. Unique in a single component scope.
    'machineName' => '',
    'name' => '',
    'description' => '',
    // Component plugin ID: {provider}:{component_id}.
    'component' => '',
    'slots' => [],
    'props' => [],
    'class' => StoryDefault::class,
  ];

  /**
   * Constructs StoryPluginManager object.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cache_backend,
    protected FileSystemInterface $fileSystem,
    protected MessengerInterface $messenger,
    protected ComponentPluginManager $componentPluginManager,
  ) {
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('component_story_info');
    $this->setCacheBackend($cache_backend, 'component_story_plugins');
  }

  /**
   * Get component stories.
   *
   * @return array[]
   *   An array of component definitions.
   */
  public function getComponentStories(string $component_id): array {
    $stories = [];
    $definitions = $this->getDefinitions();
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->componentPluginManager;
    $negotiated_definition = $manager->negotiateDefinition($component_id);
    $replacer_component_id = $negotiated_definition['replaced_by'] ?? $component_id;

    $definitions = array_filter($definitions, static function ($definition) use ($component_id, $replacer_component_id) {
      return isset($definition['component']) &&
        ($definition['component'] === $component_id || $definition['component'] === $replacer_component_id);
    });
    foreach ($definitions as $story_id => $definition) {
      // A story in a replaced component will replace the original story,
      // when the machine name is the same.
      $machineName = $definition['machineName'] ?? $story_id;
      // We use machineName as a key instead of plugin ID because we are
      // already inside the component scope.
      $stories[$machineName] = $definition;
    }
    if (empty($stories)) {
      $stories['examples'] = $this->addStoryFromExamples($negotiated_definition);
    }
    return $stories;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $directories = $this->getScanDirectories();
      $file_cache_key_suffix = 'component_story';
      // Discovers multiple YAML files in a set of directories.
      // We don't use YamlDiscovery because it expects {provider}.{name}.yml
      // structure.
      $this->discovery = new DirectoryWithMetadataPluginDiscovery($directories, $file_cache_key_suffix, $this->fileSystem);
    }
    return $this->discovery;
  }

  /**
   * Get the list of directories to scan.
   *
   * @return string[]
   *   The directories.
   */
  private function getScanDirectories(): array {
    $extension_directories = [
      ...$this->moduleHandler->getModuleDirectories(),
      ...$this->themeHandler->getThemeDirectories(),
    ];
    return array_map(
      static fn(string $path) => rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'components',
      $extension_directories
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(mixed &$definition, mixed $plugin_id): void {
    parent::processDefinition($definition, $plugin_id);
    $definition = $this->setComponentId($definition);
    $definition = $this->setMachineName($definition);
  }

  /**
   * Set component ID.
   *
   * Format: "provider:component_id"
   */
  protected function setComponentId(array $definition): array {
    if (
      isset($definition['component']) &&
      is_string($definition['component']) &&
      $this->checkComponentId($definition['component'])
      ) {
      return $definition;
    }
    [$provider, $component_id] = explode(":", $definition["id"]);
    $definition['component'] = $provider . ":" . $component_id;
    return $definition;
  }

  /**
   * Set machine name.
   *
   * Only the story ID, unique in a single component scope. So the same as the
   * plugin ID minus the component ID.
   */
  protected function setMachineName(array $definition): array {
    [, , $story_id] = explode(":", $definition["id"]);
    $definition['machineName'] = $story_id;
    return $definition;
  }

  /**
   * Check component ID.
   */
  protected function checkComponentId(string $component_id): bool {
    $machine_name = '([a-z0-9_-])+';
    $regex = '/^' . $machine_name . '\:' . $machine_name . '$/i';
    return (bool) preg_match($regex, $component_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists(mixed $provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Add story from examples.
   *
   * SDC theme can use the JSON schema examples property instead of relying on
   * stories.
   */
  protected function addStoryFromExamples(array $definition): array {
    $story = $this->initStoryFromSlots($definition['slots'] ?? []);
    foreach ($definition['props']['properties'] ?? [] as $prop_id => $prop) {
      // To avoid InvalidComponentException: Data provided to prop
      // is not a valid instance of "Drupal\Core\Template\Attribute".
      if ($prop['type'] === 'Drupal\Core\Template\Attribute') {
        $story['props'][$prop_id] = [];
      }
      if (!isset($prop["examples"]) || empty($prop["examples"])) {
        if (in_array($prop_id, $definition['props']['required'] ?? [])) {
          $default = $this->getDefaultValue($prop);
          if (!is_null($default)) {
            $story['props'][$prop_id] = $default;
          }
        }
        continue;
      }
      $story['props'][$prop_id] = $prop["examples"][0];
    }
    return $story;
  }

  /**
   * Init story from slots.
   *
   * @param array $slots
   *   Component slots.
   *
   * @return array
   *   Story data.
   */
  protected function initStoryFromSlots(array $slots): array {
    $story = [];
    foreach ($slots as $slot_id => $slot) {
      if (!isset($slot["examples"]) || empty($slot["examples"])) {
        $slot["examples"] = [$slot['title']];
      }
      $story['slots'][$slot_id] = $slot["examples"][0];
    }
    return $story;
  }

  /**
   * Get default value for prop.
   *
   * @param array $prop
   *   Prop JSON schema definition.
   *
   * @return mixed
   *   NULL if no default value found.
   */
  protected function getDefaultValue(array $prop): mixed {
    if (isset($prop['default'])) {
      return $prop['default'];
    }

    // Then, we try to get the first value from enumeration.
    if (isset($prop['enum']) && !empty($prop['enum'])) {
      return $prop['enum'][0];
    }

    // Finally, we set the boolean value to false. Boolean is the only JSON
    // schema type where we can be sure the default value is OK because there is
    // no additional criteria to deal with:
    // - string has minLength, maxLength, pattern...
    // - array has items, minItems, maxItems, uniqueItems...
    // - object has properties...
    // - number and integer have multipleOf, minimum, maximum...
    if ($prop['type'] === 'boolean') {
      return FALSE;
    }

    // There is this weird mechanism in SDC adding the object type to all
    // props. We need to deal with that until we remove it.
    // @see \Drupal\Core\Theme\Component\ComponentMetadata::parseSchemaInfo()
    if (is_array($prop['type']) && empty(\array_diff($prop['type'], ['object', 'boolean']))) {
      return FALSE;
    }

    return NULL;
  }

}
