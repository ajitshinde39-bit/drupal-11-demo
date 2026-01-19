<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;

/**
 * Component display form.
 */
final class ComponentFormDisplayDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $component_id = $route_match->getParameter('component_id');
    $form_mode = $route_match->getParameter('form_mode_name');
    return ComponentFormDisplay::loadByFormMode($component_id, $form_mode);
  }

}
