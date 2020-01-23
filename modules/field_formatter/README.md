# Field Formatter

[Field Formatter](https://www.drupal.org/project/field_formatter) module provides
a collection of generic formatters for entity reference fields that output only
a specific field of the referenced entity. There are currently three formatters:

- Field formatter with inline settings (Entity reference fields)
- Field formatter from view display (Entity reference fields)
- Link field formatter, which will wrap output of any other formatter with a
  link (all fields)

## Installation

1. Download [Field Formatter](https://www.drupal.org/project/field_formatter)
   from [Drupal.org](https://www.drupal.org/node/2328797/release).
2. Install it in the
   [usual way](https://www.drupal.org/documentation/install/modules-themes/modules-8).

## Usage

### In order to use the "Field formatter with inline settings" and "Field formatter from view display":
1. Create entity reference field:
  * On `admin/structure` choose **Content types**.
  * Choose content type entity reference field should be added to, for example:
    *Article*, and click **Manage fields** and then **+ Add field**.
  * From **References** dropdown choose **Other**, fill the *Label* and click
    **Save and continue**.
  * Choose **Type of item to reference**, for example: *Content*, and click
    **Save field settings**.
  * Select which content types you want to reference in
    **Reference type section**, for example: *Article*, and click
    **Save settings**.
2. Choose field formatter for your entity reference field:
  * Open *Manage display* page for the selected content type (in our case
    *Article*).
  * Choose which formatter you want to use, *Field formatter with inline
    settings* or *Field formatter from view display*.
  * In formatter settings define which field from the referenced entity you want
    to display in your article.
  * You also need to configure *View mode* if you're using *Field formatter from
    view display*.
  * Click **Update** and then **Save**.

### In order to use the "Field Linker" formatter:
1. Go to your entity's **manage display** page.
2. On the field you want to be wrapped with a link, select the formatter to
   be **Field Linker**.
3. This will wrap this field in a link pointing to the main entity's URL.
