# Custom Taxonomy Cleanup [![Build Status](https://travis-ci.org/keesiemeijer/custom-taxonomy-cleanup.svg?branch=master)](https://travis-ci.org/keesiemeijer/custom-taxonomy-cleanup) #

Version: 1.0.0  
Requires at least: 4.0  
Tested up to: 4.8  

Custom taxonomy terms are left in the database if a taxonomy is no longer registered (in use). 

Plugins and themes can (without you knowing) use custom taxomies as a way to store data. These terms stay in the database forever if they're not cleaned up by the plugin/theme itself upon deletion.

This plugin provides an easy way to detect and remove terms from taxonomies that are no longer in use. The settings page for this plugin is at wp-admin > Tools > Custom Taxonomy Cleanup.

**Note**: The proper WordPress delete function `wp_delete_term()` is used instead of running a direct MySQL query to delete the terms. 

It's recommended you **make a database backup** before deleting terms.

Check out this [sister plugin](https://github.com/keesiemeijer/custom-post-type-cleanup) to delete unused custom post type posts.

## Screenshots

### No terms found
The settings page if there are no terms from unused taxonomies in the database. De-activate or delete the plugin and use it again later.

![No unused taxonomy terms found](https://user-images.githubusercontent.com/1436618/28870690-7c2b0396-7781-11e7-9475-6e75bb0c945c.png)

### Settings Page
The settings page if terms where found (after deleting a batch of 100 terms).

![Settings page for this plugin](https://user-images.githubusercontent.com/1436618/28870688-7c25d4b6-7781-11e7-8b12-e7adf7ebb1c7.png)

### Done
The settings page if all terms were deleted. De-activate or delete the plugin and use it again later.
![Settings page for this plugin](https://user-images.githubusercontent.com/1436618/28870689-7c28d5ee-7781-11e7-9cd5-68d6d36f1b9d.png)