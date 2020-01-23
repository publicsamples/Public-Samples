#!/bin/bash

# Variables
REMOTE_USER="remote_user"
RSYNC="/usr/bin/rsync"
RSYNC_OPTIONS="-avz --delete --copy-links --exclude-from=/var/www/rsync-exclude.txt"
STATIC_DIR="/var/www/static/"
REMOTE_DIR="/var/www/html/"

echo "Starting sync"
# Keep going if something fails.
set +e

# Sync public files from CMS to static web server.
/var/www/html/vendor/bin/drupal --root=/var/www/html/web sgf
# Generate queued pages.
/var/www/html/vendor/bin/drupal --root=/var/www/html/web sgp --queued

# Verify required static files are present.
check_file1=${STATIC_DIR}important.html
if [ ! -e "$check_file1" ]; then
  echo 'Error: important.html does not exist.'
  exit
fi
# Verify that images are present
check_file2=${STATIC_DIR}sites/default/files/important.jpg
if [ ! -e "$check_file2" ]; then
  echo 'Important image does not exist.'
  exit
fi
# Verify that aggregated css/js is present.
count_css=$(find /cms_d8_prod/static-css-js/sites/default/files/css -type f | wc -l)
if [ "$count_css" -lt 10 ]; then
  echo 'count_css is less than 10: '$count_css
  #exit
fi

# Server sync to two load balanced web servers, copy this section for each environment, e.g. development, test, production etc.
# Production rsync
if [ `hostname` == "cms.example.oom" ]; then
  # Scheduled publishing.
  #/usr/bin/curl -k --silent --compressed https://example.com/scheduler/cron/18f26fba8f0217d3147d
  echo "Updating build date"
  # Create an updated.txt file with the current timestamp.
  echo `date` > $STATIC_DIR/updated.txt
  chmod 755 $STATIC_DIR/updated.txt

  for REMOTE_HOST in www1.example.com www2.example.com
  do
     LOG_FILE="/var/www/log/static_$REMOTE_HOST"
     #response=`curl -sI "http://$REMOTE_HOST" | grep HTTP/1.1 | awk {'print $2'}`;
     #if [ $response -eq 200 ]
     #then
   # Now copy the html and module files on remote host
   echo "Syncing site to $REMOTE_HOST"
         timeout 5m $RSYNC $RSYNC_OPTIONS $STATIC_DIR $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR > $LOG_FILE 2>&1
     #else
     #  echo `hostname` Rsync failed to $REMOTE_HOST |mail -s "RSYNC STATUS: FAILED - $REMOTE_HOST" support@example.com
     #fi
  done
fi
