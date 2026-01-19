# UI Patterns 2.x

Expose SDC components as Drupal plugins and use them seamlessly in Drupal development and site-building.

Components are reusable, nestable, guided by clear standards, and can be assembled to build any number of applications. Examples: card, button, slider, pager, menu, toast...

## Project overview

The UI Patterns project provides 3 "toolset" modules:

- **UI Patterns**: the main module, based on Drupal Core SDC API, with some extra features and quality-of-life improvements
- **UI Patterns Library**: generates a pattern library page available at `/admin/appearance/ui/components`
  to be used as documentation for content editors or as a showcase for business. Use this module if you don't plan to
  use more advanced component library systems such as Storybook, PatternLab or Fractal.
- **UI Patterns Legacy**: Load your UI Patterns 1.x components inside UI Patterns 2.x

4 "integration" modules:

- **UI Patterns Layouts**: allows to use components as layouts. This allows patterns to be used with Layout Builder,
  [Display Suite](https://www.drupal.org/project/ds) or [Layout Paragraphs](https://www.drupal.org/project/layout_paragraphs)
  out of the box.
- **UI Patterns Blocks**: allows to use components as Blocks plugins.
- **UI Patterns Field Formatters**: allows to use components as Field Formatters plugins.
- **UI Patterns Views**: allows to use components as Views styles or Views rows plugins.

## Documentation

Documentation is available [here](https://www.drupal.org/docs/contributed-modules/ui-patterns).

## Testing

### Performance testing
UI Patterns use [Gander](https://www.drupal.org/docs/develop/automated-testing/performance-tests) to automate performance measuring.
We suggest to use ddev with the [Gander Addon](https://github.com/tag1consulting/ddev-gander) to view render results.
