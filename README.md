# Custom Taxonomy CleanUp

version:         1.0  
Tested up to WP: 4.3  

Custom taxonomy terms are left in the database if a taxonomy is no longer registered (in use). 

Plugins and themes can (without you knowing) use custom taxomies as a way to store data. These terms stay in the database forever if they're not cleaned up by the plugin/theme itself upon deletion.

This plugin provides an easy way to detect and remove terms from taxonomies that are no longer in use.

**Note**: The proper WordPress delete function `wp_delete_term()` is used instead of running a direct MySQL query to delete the terms. 

It's recommended you make a database backup before deleting terms.