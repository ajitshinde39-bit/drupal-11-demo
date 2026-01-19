<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Template;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Template\ComponentNodeVisitor as CoreComponentNodeVisitor;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\TwigFunction;

/**
 * Provides a Node Visitor to change the generated parse-tree.
 */
class ModuleNodeVisitorAfterSdc extends ModuleNodeVisitorBase {

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    if (!$node instanceof ModuleNode) {
      return $node;
    }
    $component = $this->getComponent($node);
    if (!($component instanceof Component)) {
      return $node;
    }
    $line = $node->getTemplateLine();
    $function = $this->buildPreprocessPropsFunction($line, $component, $env);
    $node = $this->injectFunction($node, $function);
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    $priority = &drupal_static(__METHOD__);
    if (!isset($priority)) {
      $original_node_visitor = new CoreComponentNodeVisitor($this->componentManager);
      // Ensure that this node visitor's priority is higher than core's visitor,
      // because this class has to run after core's class.
      $priority = $original_node_visitor->getPriority() + 1;
    }
    return is_numeric($priority) ? (int) $priority : 0;
  }

  /**
   * Build the _ui_patterns_preprocess_props Twig function.
   *
   * @param int $line
   *   The line.
   * @param \Drupal\Core\Plugin\Component $component
   *   The component.
   * @param \Twig\Environment $env
   *   A Twig Environment instance.
   *
   * @return \Twig\Node\Node
   *   The Twig function.
   */
  protected function buildPreprocessPropsFunction(int $line, Component $component, Environment $env): Node {
    $component_id = $component->getPluginId();
    $function_parameter = new ConstantExpression($component_id, $line);
    $function_parameters_node = new Node([$function_parameter]);
    $function = new FunctionExpression(
      new TwigFunction('_ui_patterns_preprocess_props', [$env->getExtension(TwigExtension::class), 'preprocessProps'], ['needs_context' => TRUE]),
      $function_parameters_node,
      $line
    );
    return new PrintNode($function, $line);
  }

}
