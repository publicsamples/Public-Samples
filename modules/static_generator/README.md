# Drupal Static Generator

A static page generator for Drupal

- [Static Generator](#drupal-admin-ui)
  * [Installation](#installation)
    + [Requirements](#requirements)
    + [Steps](#steps)
  * [Settings](#settings)
  * [Generation](#generation)
    + [Overview](#overview)
    + [Full Generation](#full-generation)
    + [Page Generation](#page-generation)
    + [Block Generation](#block-generation)
    + [Redirect Generation](#redirect-generation)
    + [Files Generation](#files-generation)

## Overview

Typically the Static Generator (SG) module is installed on a Drupal site that is located behind a firewall.
That way content editors can edit the content in a more secure environment.  As content
is published, static HTML files are generated and then pushed out to a public facing static site, along
with other required files, e.g. css, javascript, media asset files etc.

Blocks and elements having a class name beginning with 'sg-esi--' may be replaced with ESI markup that
references ESI fragment include files.  This makes possible the changing of blocks and sg-esi elements'
content without having to re-generate every page that has the content, only the ESI fragment needs to be regenerated.
By default all blocks are ESIed, but may be excluded on the settings page. Note that ESI of blocks has proved problematic,
as the block system itself is very complex.  The preferred method of implementing ESI is by assigning at the theme level
a class name beginning with "sg-esi--\<id\>' where id is a user assigned id that is used for the ESI filename,
thus the id must be a valid filename.  For best performance only the sg-esi-- tags should be used,
in which case the generation of Block ESIs may be disabled from the settings page.

### Requirements

- Drupal 8.6 or greater
- PHP 7.2 or greater
- Drupal Console

### Installation
- Install this module using the normal Drupal module installation process.

- Configure a private directory in settings.php and enter in SG settings page.
- Create directories for static css and js, e.g. /var/www/static-css-js/css and  /var/www/static-css-js/js.  Enter
these directories in the SG settings page.
- Configure other SG settings at /admin/config/system/static_generator.
- Create a script that uses rsync to push generated static files to a public facing server that serves
the static files.  An example script is included in the SG module's scripts directory.
- Create a script to generate xml files, e.g. rss files. An example script is included in the SG module's scripts directory.

## Settings

- The settings page is located at: /admin/config/system/static_generator.

- Generator Directory: Determines where the generated files are placed. The default setting is to generate files in the
  private files directory.  To generate files in a directory that is within the private files directory,
  specify that directory in the setting, e.g. private://<generator_directory>.
- rSync Public: The rSync command line options for public file generation. Note that
  a file name rsync_exclude_static.txt must be present in the public files directory
  top level folder, containing public files that should not be synced to the static (private)
  files directory.
- rSync Code: The rSync command line options for code file generation.  This makes available to the static
  site all image assets contained in core and contrib modules, which could potentially be referenced in the
  aggregated CSS files used by both the live and static sites.
- Paths to generate: Specify paths to generate.  This often includes paths for views that have a page display,
 or the "Default front page" setting from /admin/config/system/site-information (generated as index.html).
- Paths and Patterns to not generate: Specify which paths and/or patterns to never generate. For example, to never
  generate the /about page, enter "/about", or to never generate any path starting with /about, enter "/about/*".
  This setting is helpful in the situation where a specific page is not generating correctly, the generation
  can be halted and the generated page replaced with a manually created page.  That way the site is functional until the
  generation issue is resolved.
- Blocks to ESI: Specify which blocks to ESI include. If left blank, all blocks are ESI included.
- Blocks and Patterns to not ESI: Specify block id's for blocks that should not be ESI included.
  Patterns may be specified by appending an asterisk, e.g. "some_block_id*" indicates that all blocks with a
  block id beginning with "some_block_id" should not be ESI included.  This is typically done for blocks
  that are unique to each page, for example a breadcrumb block, so there is not advantage to removing the block markup
  from the page and placing it in a block fragment file.
- Frequently changing ESIs: Specify <sg-esi--> tags and blocks that change frequently.  Typically a cron process will
  be run periodically, e.g. once per hour, to update these ESIs.  To generate from the command line: drupal sgp --frequent.
- Entity Types: Specify which entity types to generate. Currently only nodes are supported. IMPORTANT NOTE:
It is required that whatever content types are selected, the same content types be selected in the workflow
configuration at /admin/config/workflow/workflows.  This is required because the workflow system is used to
automatically generate a static page .html file when a node (content) is published.

## Generation
## Overview
The Static Generator module renders each page and then creates a file for the page
in the appropriate directory within the static generation directory, which is specified
in the settings. The pages are rendered for the Annonymous user.  Only published pages
are generated.

### Full Site Generation
To generate the entire site, including pages, ESI's, and public files and code files:
```
drupal sg
```
Note that whatever files are in the static directory are deleted first,
except for those files or directories specified in settings as "non-Drupal".

A preferable method of generating the entire site is to create a script that specifies each entity type to generate.
An example script is included in the SG module's scripts directory.

### Page Generation

To generate all pages:
```
drupal sgp
```

To generate a specific page:

```
drupal sgp '/node/123'
```

To generate a test sample of a specified number of pages (e.g. 50 pages)
```
drupal sgp '' '50'
```

### Blocks
To generate all blocks:
```
drupal sgb
```

To generate a specific block:

```
drupal sgb 'block_id'
```

To generate frequently changing ESIs:

```
drupal sgp --frequent
```

### Redirects
To generate redirects (requires the redirect module be installed):
```
drupal sgr
```

### Files

To generate all files:
```
drupal sgf
```
To generate public files:
```
drupal sgf -- public
```
To generate private files:
```
drupal sgf -- private
```

## Deleting
To delete all generated pages, ESI's, public files, and code files
(everything except files specified in settings as "non-Drupal"):
```
drupal sgd
```
To delete all generated pages:
```
drupal sgd --pages
```
The delete all pages command will not delete those files and directories listed in the
SG Settings "Drupal files and directories" and "non-Drupal files and directories"

### Workflow Integration

Once the Drupal core workflow module has been installed, and workflow
has been enabled for a specific content, media, or taxonomy vocabulary type at /admin/config/workflow/workflows,
page files will be automatically generated whenever a new version of
the page is published.

### Performance

It would be ideal if the core rendering worked, it has a bug when rendering blocks that have content that varies
by page.  For that reason, the SG settings page has the render method defaulted to Guzzle, which is much slower.
When generating an entire site, see the example script full_site_gen.sh in the scripts directory to lean how to
launch multiple generating processes at once using the &.  Test timings show that breaking the generation up into small pieces
and running simultaneously is much faster than generating pages sequentially.

### CSS/JS Aggregation

The SG public files (/sites/default/files) rsync process (drupal sgf --public) excludes the aggregated CSS and JS files located in
/sites/default/files/css and /sites/default/files/js.  This is because the type of rsync command required differs for css/js files
compared to the other files in the /sites/default/files.  For css/js, a non-deleting rsync is required so that older
versions of css/js files are available (they have unique names based on a hash).  In order for this to work, special
css/js directories must be created and then saved in the SG settings page (see css and js fields).  Then the static
location is automatically generated to have sym links to the actual css and js directories.  This in turn effects the main rsync
script which must have the "copy links" option so that the sym linked files are pushed to the static website.  See
the rsync.sh script example in the scripts directory to better understand this process.  Essentially the css and js files
are never deleted, they just "pile up".  Some sort of maintenance process will be needed to clean up old the old
css and js files, e.g delete ones older than a year.

### Example scripts and cron settings

See the scripts directory included with this module for example scripts and cron settings.
The rsync.sh script does an rsync from the behind the firewall static directory to
the public facing website.  A script is also included (gen_pages.sh) that typically runs during site off-peak hours
(often at night) to regenerate all files, to assure the static site is up to date with CMS changes.  Note that the script
has an option to delete all pages and ESI's, which is typically not done by cron, as the static site would be temporily
blank until the pages were re-generated.  For this reason the delete pages/ESI lines are typically commented out and
the cron calls the script which simply writes over
