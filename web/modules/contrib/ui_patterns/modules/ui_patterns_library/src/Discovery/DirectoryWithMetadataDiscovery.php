<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\Discovery\RegexRecursiveFilterIterator;

/**
 * Does the actual finding of the directories with metadata files.
 */
class DirectoryWithMetadataDiscovery extends YamlDirectoryDiscovery {

  /**
   * Constructs a DirectoryWithMetadataDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(array $directories, string $file_cache_key_suffix, protected FileSystemInterface $fileSystem) {
    parent::__construct($directories, $file_cache_key_suffix);
  }

  /**
   * Gets an iterator to loop over the files in the provided directory.
   *
   * This method exists so that it is easy to replace this functionality in a
   * class that extends this one. For example, it could be used to make the scan
   * recursive.
   *
   * @param string $directory
   *   The directory to scan.
   *
   * @return \RecursiveIteratorIterator
   *   A \RecursiveIteratorIterator object or array where the values are
   *   \SplFileInfo objects.
   */
  protected function getDirectoryIterator($directory): \RecursiveIteratorIterator {
    // Use FilesystemIterator to not iterate over the . and .. directories.
    $flags = \FilesystemIterator::KEY_AS_PATHNAME
      | \FilesystemIterator::CURRENT_AS_FILEINFO
      | \FilesystemIterator::SKIP_DOTS;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    // Detect "my_component.my_story.story.yml".
    $regex = '/^([a-z0-9_-])+\.([a-z0-9_-])+\.story\.yml$/i';
    $filter = new RegexRecursiveFilterIterator($directory_iterator, $regex);
    // @phpstan-ignore-next-line
    return new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::LEAVES_ONLY, $flags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifier($file, array $data): string {
    $full_id = $this->fileSystem->basename($file, '.story.yml');
    [$component_id, $story_id] = explode(".", $full_id);
    // A story author can explicitly target a component in the definition if
    // the story is in an other provider (theme or module) than the target
    // component.
    if (isset($data["component"]) && is_string($data["component"]) && !empty($data["component"])) {
      return sprintf('%s:%s', $data["component"], $story_id);
    }
    $provider_paths = array_flip($this->directories);
    $provider = $this->findProvider($file, $provider_paths);
    return sprintf('%s:%s:%s', $provider, $component_id, $story_id);
  }

  /**
   * Finds the provider of the discovered file.
   *
   * The approach here is suboptimal because the provider is actually set in
   * the plugin definition after the getIdentifier is called. So we either do
   * this, or we forego the base class.
   *
   * @param string $file
   *   The discovered file.
   * @param array $provider_paths
   *   The associative array of the path to the provider.
   *
   * @return string
   *   The provider
   */
  private function findProvider(string $file, array $provider_paths): string {
    $parts = explode(DIRECTORY_SEPARATOR, $file);
    array_pop($parts);
    if (empty($parts)) {
      return '';
    }
    $provider = $provider_paths[implode(DIRECTORY_SEPARATOR, $parts)] ?? '';
    return empty($provider)
      ? $this->findProvider(implode(DIRECTORY_SEPARATOR, $parts), $provider_paths)
      : $provider;
  }

}
