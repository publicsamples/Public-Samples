#!/bin/sh

cd /var/www/html/web

# Delete
/var/www/html/vendor/bin/drupal sgd --pages
/var/www/html/vendor/bin/drupal sgd --esi

# Pages
/var/www/html/vendor/bin/drupal sgp /important-page1
/var/www/html/vendor/bin/drupal sgp /important-page2
/var/www/html/vendor/bin/drupal sgp /error-403
/var/www/html/vendor/bin/drupal sgp /error-404

# Node
/var/www/html/vendor/bin/drupal sgpt node bio 0 10000&
sleep 2
#/var/www/html/vendor/bin/drupal sgpt node blog 0 10000&
#sleep 2
#/var/www/html/vendor/bin/drupal sgpt node contact_info 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node event 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node gallery 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node major_landing 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node news_release 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node page 0 10000&
sleep 2
/var/www/html/vendor/bin/drupal sgpt node section_landing 0 10000&
sleep 2

# Media
/var/www/html/vendor/bin/drupal sgpt media remote_video 0 10000&
sleep 2

# Taxonomy
/var/www/html/vendor/bin/drupal sgp /admin/structure/taxonomy/manage/cities/cincinnati
/var/www/html/vendor/bin/drupal sgpt vocabulary topics 0 10000&




