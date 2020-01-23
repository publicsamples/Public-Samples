#!/bin/bash

STATIC_DIR="/var/html/static"
HOST=`hostname`

# Generate XML Sitemap
cp /var/www/html/web/sites/default/files/xmlsitemap/NXkjkscfaf0440PdSzaEEnEVgmaugggggL25KojD4e9aZwOM/1.xml $STATIC_DIR/sitemap.xml

# RSS feeds
FILE_NAME_EVENTS=$STATIC_DIR/events.xml
curl -k -o $FILE_NAME_EVENTS https://$HOST/events.xml
FILE_EVENTS=$(<$FILE_NAME_EVENTS)
echo "${FILE_EVENTS//$HOST/www.example.com}" > $FILE_NAME_EVENTS

mkdir -p $STATIC_DIR/rss
FILE_NAME_FAQS=$STATIC_DIR/rss/efs-web-faqs.xml
curl -k -o $FILE_NAME_FAQS https://$HOST/rss/efs-web-faqs.xml
FILE_FAQS=$(<$FILE_NAME_FAQS)
echo "${FILE_FAQS//$HOST/www.example.com}" > $FILE_NAME_FAQS

mkdir -p $STATIC_DIR/news-updates
FILE_NAME_PR=$STATIC_DIR/about-us/news-updates/press-releases.xml
curl -k -o $FILE_NAME_PR https://$HOST/about-us/news-updates/press-releases.xml
FILE_PR=$(<$FILE_NAME_PR)
echo "${FILE_PR//$HOST/www.example.com}" > $FILE_NAME_PR

FILE_NAME_1=$STATIC_DIR/rss.xml
curl -k -o $FILE_NAME_1 https://$HOST/rss.xml
FILE_1=$(<$FILE_NAME_1)
echo "${FILE_1//https:\/\/example.com\/%3Ca%20href%3D%22https%3A\/\/example.com/https://www.example.com}" > $FILE_NAME_1
FILE_1=$(<$FILE_NAME_1)
echo "${FILE_1//%22%3E%3C\/a%3E/}" > $FILE_NAME_1
FILE_1=$(<$FILE_NAME_1)
echo "${FILE_1//https:\/\/example.com\/rss10.xml/https://example.com/rss.xml}" > $FILE_NAME_1
