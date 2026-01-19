# Prop types

> ⚠️ **DRAFT: writing in progress**

Prop type plugins act as a proxy between component definition and source plugins. For streamlined software architecture, we consider “slot” as a “prop type”. All slots will have the “slot” prop type, no exceptions.

Not the same as JSON schema types.

## Extends with your own prop types

### Add your own plugin

You can add your own prop types

### Add a prop type adapter plugin

However, those themes don't use the explicit prop typing from UI Patterns and rely only on the schema compatibility checker.

Sometimes, a prop JSON schema is different enough to not be caught by the compatibility checker, but close enough to address the same Drupal API as existing prop type with only some small unidirectional transformation of the data.

But we prefer to keep the list of prop types as tight as possible, and their JSON schemas as strict as possible, because they are also used for data validation.
So we need a new plugin type:

- Lightweight, just a proxy, with basic unidirectional transformations
- We will not be afraid to create a lot of them
