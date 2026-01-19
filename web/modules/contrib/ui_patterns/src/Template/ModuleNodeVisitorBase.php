<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Template;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Theme\ComponentPluginManager;
use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Provides a Node Visitor to change the generated parse-tree.
 */
abstract class ModuleNodeVisitorBase implements NodeVisitorInterface {

  /**
   * The component plugin manager.
   */
  protected ComponentPluginManager $componentManager;

  /**
   * Constructs a new ComponentNodeVisitor object.
   *
   * @param \Drupal\Core\Theme\ComponentPluginManager $component_plugin_manager
   *   The component plugin manager.
   */
  public function __construct(ComponentPluginManager $component_plugin_manager) {
    $this->componentManager = $component_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    return $node;
  }

  /**
   * Finds the SDC for the current module node.
   *
   * A duplicate of \Drupal\Core\Template\ComponentNodeVisitor::getComponent()
   *
   * @param \Twig\Node\Node $node
   *   The node.
   *
   * @return \Drupal\Core\Plugin\Component|null
   *   The component, if any.
   */
  protected function getComponent(Node $node): ?Component {
    $component_id = $node->getTemplateName();
    if (!preg_match('/^[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*:[a-z]([a-zA-Z0-9_-]*[a-zA-Z0-9])*$/', $component_id)) {
      return NULL;
    }
    try {
      return $this->componentManager->find($component_id);
    }
    catch (ComponentNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * Injects custom Twig nodes into given node as child nodes.
   *
   * The function will be injected direct after  validate_component_props
   * function already injected by SDC's ComponentNodeVisitor.
   *
   * @param \Twig\Node\Node $node
   *   The node where we will inject the function in.
   * @param \Twig\Node\Node $function
   *   The Twig function.
   *
   * @return \Twig\Node\Node
   *   The node with the function inserted.
   */
  protected function injectFunction(Node $node, Node $function): Node {
    $insertion = new Node([$node->getNode('display_start'), $function]);
    $node->setNode('display_start', $insertion);
    return $node;
  }

}
