# Using a component in Drupal templates

## Presenter templates

Presenter templates are standard Drupal templates that:

- transform data received from Drupal
- use Twig [include()](http://twig.sensiolabs.org/doc/function/include.html) function to include one or more components and pass the transformed data.
- should be totally free of markup
- use [theme suggestions](https://www.drupal.org/docs/8/theming/twig/twig-template-naming-conventions) to plug itself to data model

For example, a “normal” template has markup:

```twig
{% if subtitle %}
<h3 class="callout__subtitle">{{ subtitle }}</h3>
{% endif %}
```

But a presenter template has only a call to a component (and sometimes a bit of logic):

```twig
{{ include('my_theme:menu', {
  'items': items,
  'attributes': attributes.addClass('bg-primary'),
}, with_context=false)}}
```

Examples of presenter templates can be found on those folders:

- https://git.drupalcode.org/project/ui_suite_bootstrap/-/tree/5.1.x/templates
- https://git.drupalcode.org/project/ui_suite_daisyui/-/tree/4.0.x/templates
- https://git.drupalcode.org/project/ui_suite_dsfr/-/tree/1.1.x/templates
- https://git.drupalcode.org/project/ui_suite_material/-/tree/2.0.x/templates
- https://git.drupalcode.org/project/ui_suite_uswds/-/tree/4.0.x/templates

More about presenter templates:

- https://www.aleksip.net/presenting-component-projects (May 2016)
- https://www.mediacurrent.com/blog/accommodating-drupal-your-components/ (Broken link)

### Don’t use presenter templates when site building is possible

Presenter templates are a clever trick, however they hurt the site building because everything which is normally set in the display settings by the site builder has to be done in a Twig file by the developer.

However, there are some cases when site building is not easily possible. For examples:

- rendering a menu. Menus are config entities without configurable displays.
- rendering a content entity where the configurable display has no admin UI.

🚫 Don't do presenter templates when display building is available:

- Node: `/admin/structure/types/manage/{bundle}/display`
- BlockContent: `/admin/structure/block/block-content/manage/{bundle}/display`
- Comment: `/admin/structure/comment/manage/{bundle}/display`
- Media: `/admin/structure/media/manage/{bundle}/display`
- Taxonomy Term: `/admin/structure/taxonomy/manage/{bundle}/overview/display`
- User: `/admin/config/people/accounts/display`
- Views

✅ Presenter templates are OK for renderables without display building:

- Page layout: `page.html.twig` and `region.html.twig`
- Menu config entity: `menu.html.twig`
- ...

### Slots & props

Most of those templates have distinct variables for data to send to slots or props.

For example, in `node.html.twig`:

- `node` variable has the typed data to send to props
- `content` has the renderable data to send to slots

Other example, in `user.html.twig`:

- `user` variable has the typed data to send to props
- `content` has the renderable data to send to slots

## Clean default templates

Even without doing presenter templates, template overriding can be useful to clean some cruft from templates provided by core or contrib modules.

The node.html.twig template is a good example, because it carries a lot of legacy junk, like a title base field display condition based on view mode name (!) and a poor man submitted by.

It is better to keep those templates as lean as possible, and to push the complexity to layouts and other display modes plugins.

For example:

- `field.html.twig`
- `block.html.twig`
- `node.html.twig`
- `taxonomy-term.html.twig`
- `media.html.twig`
- `comment.html.twig`
- ...

Those templates content can be replaced by:

```twig
{% if attributes.storage() %}
<div{{ attributes }}>
{% endif %}
  {{ content }}
{% if attributes.storage() %}
</div>
{% endif %}
```

Or sometimes, by more complex stuff like:

```twig
{% if attributes.storage() %}
<div{{ attributes }}>
{% endif %}
  {{ title_prefix }}
  {{ title_suffix }}
  {{ content }}
  {% if attributes.storage() %}
  <div{{ content_attributes }}>
  {% endif %}
   {{ content }}
  {% if attributes.storage() %}
  </div>
  {% endif %}
{% if attributes.storage() %}
</div>
{% endif %}
```

Some markup of components or some utility styles expect specific markup with direct children like flex feature. Currently, when trying to use those components in lists the wrappers inside templates like field.html.twig and node.html.twig will interfere with the expected markup. So sometimes even the wrappers need to be removed with template overrides.

Use the [Entity View Display Template Suggestions module](https://www.drupal.org/project/entity_vdts) to be able to remove the wrapper of some entity displays. If the theme provides templates with bare minimum markup like just the "content" variable printed, for content entities with the module you will be able to remove the wrapper with configuration.
