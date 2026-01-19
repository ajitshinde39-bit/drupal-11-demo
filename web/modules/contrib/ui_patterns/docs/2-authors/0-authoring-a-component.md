# Authoring a component

UI Patterns 2 are SDC components, with a few additions and some better practices.

So, we expect the reader to be already comfortable with SDC before reading this chapter which is complementing the SDC documentation: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components

## Links metadata

UI Patterns 2 is adding the `links` metadata property for documentation, with both a compact and a full syntax:

```yaml
name: Example
links:
  - "https://test.com"
  - url: "https://example.com"
    title: "Example"
props: {}
slots: {}
```

## Tags metadata

For documentation only:

```yaml
name: Example
tags: [Foo, Bar, Baz]
slots: {}
props: {}
```

## Component variants

A “variants” property is available in component definition.

Variants are a “glorified” prop, but it is still a prop. This property was added:

- to ease the experience of the front developer which doesn't need to set a schema for an already well-known data type
- because JSON schema enums have no label and using a anyOf with constant is verbose and complicated
- to show this prop will have a special treatment
- to be able to put variant prop (and this prop only) before slots in the form builder trait.

YAML:

```yaml
name: Card
variants:
  default:
    title: Default
    description: An ordinary card.
  highlighted:
    title: Highlighted
    description: A special card.
slots: {}
props: {}
```

Once loaded, this property will also be available as a string prop with enum:

```yaml
name: Card
props:
  type: object
  properties:
    variants:
      type: string
      enum: [default, highlighted]
```

So it can be manipulated like that in render elements, twig functions…

> Related SDC ticket: https://www.drupal.org/project/drupal/issues/3390712

## Explicit prop typing

Using JSON schema for props definition is enough for simple data:

- `type: string` for the strings
- `type: [number, integer]` for the numbers
- `type: string, format: uri` for the URL
- ...

For more complicated data, we need "shortcuts", because it may be difficult for component authors to type complex schema without errors.

Those shortcuts are based on a JSON schema references provider with `ui-patterns://` URL scheme.

So, instead of writing the full prop schema, a component every prop type plugin is a JSON schema reference. For example:

```yaml
props:
  type: object
  properties:
    figcaption_attributes:
      title: "Figcaption attributes"
      description: "The attributes to customize the figcaption tag."
      "$ref": "ui-patterns://attributes"
```

JSON Schema processors can resolve this $ref and build the complete schema to validate.

It is important to understand the [URI is opaque](https://www.w3.org/DesignIssues/Axioms.html#opaque). In `ui-patterns://attributes` the word "attributes" means nothing. No information must be guessed from that. Only the data retrieved from the request is relevant.

UI Patterns is then working on the already resolved JSON schema.

UI Patterns 2.x is shipped with some prop type plugins included.

### Attributes

Reference shortcut: `ui-patterns://attributes`

Resolved schema:

```yaml
type: object
patternProperties:
  ".+":
    anyOf:
      - type:
          - string
          - number
      - type: array
        items:
          anyOf:
            - type: number
            - type: string
```

The value is transformed into an `\Drupal\Core\Template\Attribute` object before being sent to the template.

### Boolean

Reference shortcut: `ui-patterns://boolean`

It is better to use the resolved schema:

```yaml
type: boolean
```

Default form widget: a checkbox.

### List of enums

Reference shortcut: `ui-patterns://enum_list`

Resolved schema:

```yaml
type: array
items:
  type:
    - string
    - number
    - integer
  enum: []
```

Because the enum values are expected, it is better to directly use the schema like that:

```yaml
type: array
minItems: 2
maxItems: 4
items:
  enum: [Apple, Banana, Cocoa]
```

With two optional properties used in the component form:

- `minItems` to set the number of required values (default: 0)
- `maxItems` to set the number of values (default: 1)

Default form widget: A `maxItems` number of select lists.

Useful to build grid layouts where cols and responsive cols can be set for each cell/region:

```yaml
title: "Number of columns the cells span"
type: array
maxItems: 4
items:
  type: integer
  enum: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
default: [4, 4, 4, 4]
```

Examples:

- [Material design 2](https://git.drupalcode.org/project/ui_suite_material/-/blob/2.0.x/components/grid_row_4/)
- [Bootstrap 5](https://git.drupalcode.org/project/ui_suite_bootstrap/-/blob/5.1.x/components/grid_row_4/)
- [DaisyUI 4](https://git.drupalcode.org/project/ui_suite_daisyui/-/blob/4.0.x/components/grid_4_regions/)

### Enum

A single value restricted to a fixed set of values.

Reference shortcut: `ui-patterns://enum`

Resolved schema:

```yaml
type: ["string", "number", "integer"]
enum: []
```

It is better to use the resolved schema with only the enum values:

```yaml
enum:
  - "Foo"
  - "Bar"
  - "Baz"
```

```yaml
enum:
  - 5
  - 10
  - 15
```

Default form widget: A select list.

### Set of enums

Set of unique predefined string or number items.

Reference shortcut: `ui-patterns://enum_set`

Resolved schema:

```yaml
type: array
uniqueItems: true
items:
  type:
    - string
    - number
    - integer
  enum: []
```

It is necessary to set the enum values:

```yaml
ui-patterns://enum_set
items:
  enum: ["Foo", "Bar", "Baz"]
```

Default form widget: A checkbox for each value.

### Identifier

A string with restricted characters, suitable for an HTML ID.

Reference shortcut: `ui-patterns://attributes`

Resolved schema:

```yaml
type: string
pattern: '(?:--|-?[A-Za-z_\x{00A0}-\x{10FFFF}])[A-Za-z0-9-_\x{00A0}-\x{10FFFF}\.]*']
```

Default form widget: A textfield.

### Links

A list of link objects. Useful to model menu, breadcrumbs, pagers...

Reference shortcut: `ui-patterns://links`

Resolved schema:

```yaml
type: array
items:
  type: object
  properties:
    title:
      type: string
    url:
      "$ref": ui-patterns://url
    attributes:
      "$ref": ui-patterns://attributes
    link_attributes:
      "$ref": ui-patterns://attributes
    below:
      type: array
      items:
        type: object
```

Default form source: A Drupal menus selector.

### List

List of free string or number items.

Reference shortcut: `ui-patterns://list`

Resolved schema:

```yaml
type: array
items:
  type: [string, number, integer]
```

Default form widget: A textarea. One value per line.

### Number

Reference shortcut: `ui-patterns://number`

It is better to use the resolved schema:

- for a decimal value: `type: number`
- for an integer value: `type: integer`

Default form widget: a number textfield with incremental steps (0.01 for decimal)

It is possible to set minimum and/or maximum values:

```yaml
type: integer
minimum: 1789
maximum: 2025
```

### String

Reference shortcut: `ui-patterns://string`

It is better to use the resolved schema:

```yaml
type: string
```

Default form widget: a textfield.

### URL

Reference shortcut: `ui-patterns://url`

Resolved schema:

```yaml
type: string
format: iri-reference
```

Default form widget: a textfield.

## Prop default values

UI Patterns 2 uses <code>default</code> when building the component form, as <code>#default_value</code>.

<code>default</code> must not be used in the rendering process (sending default value if prop value is missing or empty) because:

- sometimes we want a default value in forms while allowing the user to set empty or missing value
- the [|default()](https://twig.symfony.com/doc/3.x/filters/default.html) Twig filter is the expected tool for such enforcement

## meta:enum

For your information, in UI Patterns 2, we use `meta:enum` which is not an official standard but supported by some popular projects:

- [adobe/jsonschema2md](https://github.com/adobe/jsonschema2md)
- [coveooss/json-schema-for-human](https://github.com/coveooss/json-schema-for-human)

```yaml
props:
  type: object
  properties:
    position:
      type: string
      enum:
        - top
        - bottom
      "meta:enum":
        top: Top
        bottom: Bottom
```

So:

- If an item is in enum but not in meta:enum, its label will be the item string
- If an item is in meta:enum but not in enum, it is ignored.
