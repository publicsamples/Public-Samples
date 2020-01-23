CONTENTS OF THIS FILE
---------------------

 * Overview
 * Instructions

OVERVIEW
--------

Auto Menutitle is a simple module that adds to Drupal's core menu functionality
by providing a default setting for the 'Provide a menu link' checkbox.

Using this module, you can tell specific content types to automatically create
menu links for content based on the content's title.

For example, say you want all 'Basic Pages' on your site to have a link in the
Main Menu. You simply set the default value of the 'Provide a menu link'
checkbox for the Basic Page content type to TRUE (and configure the other menu
settings appropriately), then all future pages will have a link created in the
Main Menu automatically.

INSTRUCTIONS
------------

- Install and enable the module.
  (http://drupal.org/documentation/install/modules-themes/modules-8)

- Edit a content type for which you want to enable default menu links.
  (http://www.example.com/admin/structure/types)

- In the 'Menu settings' tab select the menu you want your links created in,
  choose the 'Default parent item' of the links, and tick the 'Provide a menu
  link by default' box.

- Save the content type, then add some content of that type to see the menu link
  created automatically.
  (http://www.example.com/node/add)
