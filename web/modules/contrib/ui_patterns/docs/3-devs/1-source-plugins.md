# Extends with your own source plugins

> ⚠️ **DRAFT: writing in progress**

The most common way of extending UI Patterns 2.x API is to add source plugins.

https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/

- Same source plugin type for slots and props
- Same source plugin type for data from Drupal API or from direct input

## Context awareness

Sometimes sources require another object in order to retrieve the data. This is known as context.

## Default value

Some source plugins are using it to fill the default value of the form, when they extend SourcePluginPropValue because:

- the source stores only one config value called value
- value data is validating to the prop schema

Examples:

- [UrlWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/UrlWidget.php)
- [SelectsWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/SelectsWidget.php)
- [NumberWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/NumberWidget.php)
- [CheckboxesWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/CheckboxesWidget)
- [SelectWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/CheckboxesWidget)
- [CheckboxWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/CheckboxWidget.php)
- [TextfieldWidget](https://git.drupalcode.org/project/ui_patterns/-/tree/2.0.x/src/Plugin/UiPatterns/Source/TextfieldWidget.php)
