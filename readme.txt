=== Custom Taxonomy Cleanup ===
Contributors: keesiemeijer
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Detect and delete terms from custom taxonomies that are no longer in use 

== Description ==

Custom taxonomy terms are left in the database if a taxonomy is no longer registered (in use).

Plugins and themes can (without you knowing) use custom taxonomies as a way to store data. These terms stay in the database forever if they're not cleaned up by the plugin/theme itself upon deletion.

This plugin provides an easy way to detect and remove terms from taxonomies that are no longer in use. The settings page for this plugin is at wp-admin > Tools > Custom Taxonomy Cleanup.

**Note**: The proper WordPress delete function `wp_delete_term()` is used instead of running a direct MySQL query to delete the terms.