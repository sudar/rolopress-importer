=== RoloPress Importer ===
Contributors: sudar
Requires at least: 2.9
Tested up to: 3.0.1
Stable tag: 0.1
Tags: csv, import, batch, spreadsheet, excel

Import contacts from CSV files into WordPress.


== Description ==

This plugin imports posts from CSV (Comma Separated Value) files into your
WordPress blog. It can prove extremely useful when you want to import a bunch
of posts from an Excel document or the like - simply export your document into
a CSV file and the plugin will take care of the rest.

= Features =

*   Imports post title, body, excerpt, tags, date, categories etc.
*   Supports custom fields, custom taxonomies and comments
*   Deals with Word-style quotes and other non-standard characters using
    WordPress' built-in mechanism (same one that normalizes your input when you
    write your posts)
*   Columns in the CSV file can be in any order, provided that they have correct
    headings


== Screenshots ==

1.  Plugin's interface


== Installation ==

Installing the plugin:

1.  Unzip the plugin's directory into `wp-content/plugins`.
1.  Activate the plugin through the 'Plugins' menu in WordPress.
1.  The plugin will be available under Tools -> RoloPress Importer on
    WordPress administration page.


== Usage ==


== Frequently Asked Questions ==


== Credits ==

This plugin uses [php-csv-parser][3] by Kazuyoshi Tlacaelel and is also based on the [CSV Import][4] WordPress plugin by Denis Kobozev.

[3]: http://code.google.com/p/php-csv-parser/
[4]: http://wordpress.org/extend/plugins/csv-importer/

== Changelog ==

###v0.1 (2010-10-02)

*   first version

==Readme Generator==

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.