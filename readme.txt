=== Plugin Name ===
Contributors: support@wpadmin.ca
Donate link: http://wpadmin.ca
Tags: Cloudfront, AWS, Amazon, S3, Backup.
Requires at least: 4.4.2
Tested up to: 4.8
Stable tag: 0.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Amazon AWS S3 to backup your WordPress Site daily. This plugin will backup the wp-config.php, wp-content folder and database to Amazon AWS S3.

== Description ==

Use Amazon AWS S3 to backup your WordPress Site daily. This plugin will backup the wp-config.php, wp-content folder and database to Amazon AWS S3. You can create, list and even delete folder on Amazon AWS S3.

== Installation ==

= Using the WordPress Plugin Search =

1. Navigate to the `Add New` sub-page under the Plugins admin page.
2. Search for `Amazon AWS S3`.
3. The plugin should be listed first in the search results.
4. Click the `Install Now` link.
5. Lastly click the `Activate Plugin` link to activate the plugin.

= Uploading in WordPress Admin =

1. [Download the plugin zip file](https://downloads.wordpress.org/plugin/aws-s3-by-wpadmin.0.4.zip) and save it to your computer.
2. Navigate to the `Add New` sub-page under the Plugins admin page.
3. Click the `Upload` link.
4. Select `aws-s3-by-wpadmin` zip file from where you saved the zip file on your computer.
5. Click the `Install Now` button.
6. Lastly click the `Activate Plugin` link to activate the plugin.

= Using FTP =

1. [Download the plugin zip file](https://downloads.wordpress.org/plugin/aws-s3-by-wpadmin.0.4.zip) and save it to your computer.
2. Extract the `aws-s3-by-wpadmin` zip file.
3. Create a new directory named `aws-s3-by-wpadmin` directory in the `../wp-content/plugins/` directory.
4. Upload the files from the folder extracted in Step 2.
4. Activate the plugin on the Plugins admin page.

== Frequently Asked Questions ==

= Got a Question? =

[Send me an email](http://wpadmin.ca/contact-us/)




== Screenshots ==

1. screenshot-1.jpg


== Changelog ==0.6.1REsolved Memory Leak while creating zip0.6Backups failed if the size of wp-content was a few 100 megabytes, swithched to backups of themes, plugins & uplaods folders.0.5Setup number of Full BackupsAutomatically cleanup incremental backups when a full backup is completed.

== Upgrade Notice ==

None Yet.