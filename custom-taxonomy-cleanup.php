<?php
/*
Plugin Name: Custom Taxonomy Cleanup
Version: 1.0.0
Plugin URI:
Description: Detect and delete terms from custom taxonomies that are no longer in use.
Author: keesiemijer
Author URI:
License: GPL v2+
Text Domain: custom-taxonomy-cleanup
Domain Path: /languages

Custom Taxonomy Cleanup
Copyright 2015  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-taxonomy-cleanup.php';
	$tax_cleanup = new CTC_Taxonomy_Cleanup();
	$tax_cleanup->init();
}