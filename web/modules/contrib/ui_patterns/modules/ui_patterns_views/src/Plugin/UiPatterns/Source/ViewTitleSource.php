<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_views\Plugin\UiPatterns\Source;

use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\views\ViewExecutable;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'view_title',
  label: new TranslatableMarkup('[View] Title'),
  description: new TranslatableMarkup('The title of the view.'),
  prop_types: ['string'],
  context_definitions: [
    'ui_patterns_views:view_entity' => new EntityContextDefinition('entity:view', label: new TranslatableMarkup('View')),
  ]
)]
class ViewTitleSource extends ViewsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $view = $this->getView();
    if (!$view instanceof ViewExecutable) {
      return '';
    }
    return $view->getTitle();
  }

}
