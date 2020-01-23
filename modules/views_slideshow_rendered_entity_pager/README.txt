INTRODUCTION
------------
This module extends the Views Slideshow pager to provide a new widget to
display rendered entities in the pager (instead of simple bullet for example).
You can select a specific view mode to be used in the pager.

Development of this module has been based on Views Slideshow Simple Pager
which is providing another pager widget to display item numbers.

 * For a full description of the module, visit the project page:
   https://drupal.org/sandbox/vtriclot/2422491

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/2422491


REQUIREMENTS
------------
This module requires the following modules:

 * Views Slideshow (https://drupal.org/project/views_slideshow)


CONFIGURATION
-------------
As the module is only extending the Views Slideshow pager, it doesn't require
any specific configuration. To use it, create a Views with the Slideshow format.
Edit the format settings, select "Pager" in "Top" or "Bottom" widgets. Select
"Rendered Entity" as "Pager type" and select the expected view mode in the new
field.


MAINTAINERS
-----------
Current maintainers:
 * Vincent Bouchet (vbouchet) - https://drupal.org/user/1671428
