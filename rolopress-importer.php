<?php
/*
Plugin Name: RoloPress Importer
Plugin URI: http://sudarmuthu.com/wordpress/rolopress-importer
Description: Import contacts into RoloPress.
Version: 0.1
Author: Sudar
Author URI: http://sudarmuthu.com/
Text Domain: rolopress-importer

=== RELEASE NOTES ===
2010-10-02 - v0.1 - Initial Release

Based on CSV Importer WordPress Plugin (http://wordpress.org/extend/plugins/csv-importer/) by Denis Kobozev
*/

/**
 * LICENSE: The MIT License {{{
 *
 * Copyright (c) <2009> <Denis Kobozev, Sudar>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Denis Kobozev <d.v.kobozev@gmail.com>, Sudar <http://sudarmuthu.com>
 * @copyright 2009 Denis Kobozev, Sudar
 * @license   The MIT License
 * }}}
 */

class CSVImporterPlugin {
    var $defaults = array(
        'csv_post_title' => null,
        'csv_post_post' => null,
        'csv_post_type' => null,
        'csv_post_excerpt' => null,
        'csv_post_date' => null,
        'csv_post_tags' => null,
        'csv_post_categories' => null,
        'csv_post_author' => null,
        'csv_post_slug' => null,
    );

    var $log = array();

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'rolopress-importer', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks

        // Settings hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );


//        add_action( 'admin_init', array(&$this, 'add_settings') );
//
//        // Display twitter textbox in the comment form
//        add_action('comment_form', array(&$this, 'add_twitter_field'), 9);
//
//        // Display Twitter field in user's profile page
//        add_filter('user_contactmethods', array(&$this, 'add_contactmethods'), 10, 1);
//
//        // Save the twitter field
//        // priority is very low (50) because we want to let anti-spam plugins have their way first.
//        add_filter('comment_post', array(&$this, 'save_twitter_field'), 50);
//
//        //hook the show gravatar function
//        add_filter('get_avatar', array(&$this, 'change_avatar'), 10, 5);
//        add_filter('get_avatar_comment_types', array(&$this, 'add_avatar_types'));
//
//        // Enqueue the script
//        add_action('template_redirect', array(&$this, 'add_script'));
//
//        // add action links
//        $plugin = plugin_basename(__FILE__);
//        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('RoloPress Importer', 'roloPress-importer'), __('RoloPress Importer', 'roloPress-importer'), 8, 'roloPress-importer', array(&$this, 'settings_page') );
    }

    // determine value of option $name from database, $default value or $params,
    // save it to the db if needed and return it
    function process_option($name, $default, $params) {
        if (array_key_exists($name, $params)) {
            $value = stripslashes($params[$name]);
        } elseif (array_key_exists('_'.$name, $params)) {
            // unchecked checkbox value
            $value = stripslashes($params['_'.$name]);
        } else {
            $value = null;
        }
        $stored_value = get_option($name);
        if ($value == null) {
            if ($stored_value === false) {
                if (is_callable($default) &&
                    method_exists($default[0], $default[1])) {
                    $value = call_user_func($default);
                } else {
                    $value = $default;
                }
                add_option($name, $value);
            } else {
                $value = $stored_value;
            }
        } else {
            if ($stored_value === false) {
                add_option($name, $value);
            } elseif ($stored_value != $value) {
                update_option($name, $value);
            }
        }
        return $value;
    }

    // Plugin's interface
    function settings_page() {
//        $opt_draft = $this->process_option('rp-csv-import-fileer_import_as_draft',
//            'publish', $_POST);
//        $opt_cat = $this->process_option('rp-csv-import-fileer_cat', 0, $_POST);

//        if ('POST' == $_SERVER['REQUEST_METHOD']) {
        if (isset($_POST['rp-csv-import-button'])) {
            $this->import(compact('opt_draft', 'opt_cat'));
        }

        // form HTML {{{
?>

<div class="wrap">
    <h2>Import CSV</h2>
    <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
        <!-- Import as draft -->
        <p>
        <input name="_rp-csv-import-fileer_import_as_draft" type="hidden" value="publish" />
        <label><input name="rp-csv-import-fileer_import_as_draft" type="checkbox" <?php if ('draft' == $opt_draft) { echo 'checked="checked"'; } ?> value="draft" /> Import posts as drafts</label>
        </p>

        <!-- Parent category -->
        <p>Organize into category <?php wp_dropdown_categories(array('show_option_all' => 'Select one ...', 'hide_empty' => 0, 'hierarchical' => 1, 'show_count' => 0, 'name' => 'rp-csv-import-fileer_cat', 'orderby' => 'name', 'selected' => $opt_cat));?><br/>
            <small>This will create new categories inside the category parent you choose.</small></p>

        <!-- File input -->
        <p>
            <label for="rp-csv-import-file">Upload file:</label><br/>
            <input name="rp-csv-import-file" id="rp-csv-import-file" type="file" value="" aria-required="true" />
        </p>
        <p class="submit"><input type="submit" class="button" name="rp-csv-import-button" value="Import" /></p>
    </form>
</div><!-- end wrap -->

<?php
        // end form HTML }}}

    }

    /**
     * Print output messages in the header
     */
    function print_messages() {
        if (!empty($this->log)) {

        // messages HTML {{{
?>

<div class="wrap">
    <?php if (!empty($this->log['error'])): ?>

    <div class="error">

        <?php foreach ($this->log['error'] as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    <?php if (!empty($this->log['notice'])): ?>

    <div class="updated fade">

        <?php foreach ($this->log['notice'] as $notice): ?>
            <p><?php echo $notice; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>
</div><!-- end wrap -->

<?php
        // end messages HTML }}}

            $this->log = array();
        }
    }

    /**
     * Import contacts from CSV files
     * @return <type>
     */
    function import($options) {
        if (empty($_FILES['rp-csv-import-file']['tmp_name'])) {
            $this->log['error'][] = 'No file uploaded, aborting.';
            $this->print_messages();
            return;
        }

        //TODO: Using proper Plugin path
        require_once 'File_CSV_DataSource/DataSource.php';

        $time_start = microtime(true);

        $csv = new File_CSV_DataSource;
        $file = $_FILES['rp-csv-import-file']['tmp_name'];
        $this->stripBOM($file);

        if (!$csv->load($file)) {
            $this->log['error'][] = 'Failed to load file, aborting.';
            $this->print_messages();
            return;
        }

        // pad shorter rows with empty values
        $csv->symmetrize();

        // WordPress sets the correct timezone for date functions somewhere
        // in the bowels of wp_insert_post(). We need strtotime() to return
        // correct time before the call to wp_insert_post().
        $tz = get_option('timezone_string');
        if ($tz && function_exists('date_default_timezone_set')) {
            date_default_timezone_set($tz);
        }

        $skipped = 0;
        $imported = 0;
        $comments = 0;
        foreach ($csv->connect() as $csv_data) {
            if ($post_id = $this->create_post($csv_data, $options)) {
                $imported++;
                $comments += $this->add_comments($post_id, $csv_data);
                $this->create_custom_fields($post_id, $csv_data);
            } else {
                $skipped++;
            }
        }

        if (file_exists($file)) {
            @unlink($file);
        }

        $exec_time = microtime(true) - $time_start;

        if ($skipped) {
            $this->log['notice'][] = "<b>Skipped {$skipped} posts (most likely due to empty title, body and excerpt).</b>";
        }
        $this->log['notice'][] = sprintf("<b>Imported {$imported} posts and {$comments} comments in %.2f seconds.</b>", $exec_time);
        $this->print_messages();
    }

    function create_post($data, $options) {
        extract($options);

        $data = array_merge($this->defaults, $data);
        $type = $data['csv_post_type'] ? $data['csv_post_type'] : 'post';
        $valid_type = (function_exists('post_type_exists') &&
            post_type_exists($type)) || in_array($type, array('post', 'page'));

        if (!$valid_type) {
            $this->log['error']["type-{$type}"] = sprintf(
                'Unknown post type "%s".', $type);
        }

        $new_post = array(
            'post_title' => convert_chars($data['csv_post_title']),
            'post_content' => wpautop(convert_chars($data['csv_post_post'])),
            'post_status' => $opt_draft,
            'post_type' => $type,
            'post_date' => $this->parse_date($data['csv_post_date']),
            'post_excerpt' => convert_chars($data['csv_post_excerpt']),
            'post_name' => $data['csv_post_slug'],
            'post_author' => $this->get_auth_id($data['csv_post_author']),
            'tax_input' => $this->get_taxonomies($data),
        );

        // pages don't have tags or categories
        if ('page' !== $type) {
            $new_post['tags_input'] = $data['csv_post_tags'];

            // Setup categories before inserting - this should make insertion
            // faster, but I don't exactly remember why :) Most likely because
            // we don't assign default cat to post when csv_post_categories
            // is not empty.
            $cats = $this->create_categories($data, $opt_cat);
            $new_post['post_category'] = array_merge($cats['old'], $cats['new']);
        }

        // create!
        $id = wp_insert_post($new_post);

        if ('page' !== $type && !$id) {
            // cleanup new categories on failure
            foreach ($cats['new'] as $c) {
                wp_delete_term($c, 'category');
            }
        }
        return $id;
    }

    // Lookup existing categories or create new ones
    function create_categories($data, $parent_id) {
        $ids = array(
            'old' => array(),
            'new' => array(),
        );
        $category_names = explode(',', $data['csv_post_categories']);
        foreach ($category_names as $cat_name) {
            $cat_name = trim($cat_name);

            if (!empty($cat_name)) {
                // Searching or creating the category
                if (is_numeric($cat_name)) {
                    // it's an id, not a name
                    if (null !== get_category($cat_name)) {
                        $ids['old'][] = $cat_name;
                    } else {
                        $this->log['error'][] =
                            "There is no category with id {$cat_name}.";
                    }
                } else {
                    $term = is_term($cat_name, 'category', $parent_id);
                    if (!$term) {
                        $category = array(
                            'cat_name' => $cat_name,
                            'category_description' => '',
                            'category_nicename' => sanitize_title($cat_name),
                            'category_parent' => $parent_id,
                        );
                        $cat_id = wp_insert_category($category);
                        $ids['new'][] = $cat_id;
                    } else {
                        $ids['old'][] = $term['term_id'];
                    }
                }
            }
        }
        return $ids;
    }

    // Parse taxonomy data from the file
    //
    // array(
    //      // hierarchical taxonomy name => ID array
    //      'my taxonomy 1' => array(1, 2, 3, ...),
    //      // non-hierarchical taxonomy name => term names string
    //      'my taxonomy 2' => array('term1', 'term2', ...),
    // )
    function get_taxonomies($data) {
        $taxonomies = array();
        foreach ($data as $k => $v) {
            if (preg_match('/^csv_ctax_(.*)$/', $k, $matches)) {
                $t_name = $matches[1];
                if (is_taxonomy($t_name)) {
                    $taxonomies[$t_name] = $this->create_terms($t_name,
                        $data[$k]);
                } else {
                    $this->log['error'][] = "Unknown taxonomy $t_name";
                }
            }
        }
        return $taxonomies;
    }

    // Return an array of term IDs for hierarchical taxonomies or the original
    // string from CSV for non-hierarchical taxonomies. The original string
    // should have the same format as csv_post_tags.
    function create_terms($taxonomy, $field) {
        if (is_taxonomy_hierarchical($taxonomy)) {
            $term_ids = array();
            foreach ($this->_parse_tax($field) as $row) {
                list($parent, $child) = $row;
                $parent_ok = true;
                if ($parent) {
                    $parent_info = is_term($parent, $taxonomy);
                    if (!$parent_info) {
                        // create parent
                        $parent_info = wp_insert_term($parent, $taxonomy);
                    }
                    if (!is_wp_error($parent_info)) {
                        $parent_id = $parent_info['term_id'];
                    } else {
                        // could not find or create parent
                        $parent_ok = false;
                    }
                } else {
                    $parent_id = 0;
                }

                if ($parent_ok) {
                    $child_info = is_term($child, $taxonomy, $parent_id);
                    if (!$child_info) {
                        // create child
                        $child_info = wp_insert_term($child, $taxonomy,
                            array('parent' => $parent_id));
                    }
                    if (!is_wp_error($child_info)) {
                        $term_ids[] = $child_info['term_id'];
                    }
                }
            }
            return $term_ids;
        } else {
            return $field;
        }
    }

    // hierarchical taxonomy fields are tiny CSV files in their own right
    function _parse_tax($field) {
        $data = array();
        if (function_exists('str_getcsv')) { // PHP 5 >= 5.3.0
            $lines = explode("\n", $field);

            foreach ($lines as $line) {
                $data[] = str_getcsv($line, ',', '"');
            }
        } else {
            // Use temp files for older PHP versions. Reusing the tmp file for
            // the duration of the script might be faster, but not necessarily
            // significant.
            $handle = tmpfile();
            fwrite($handle, $field);
            fseek($handle, 0);

            while (($r = fgetcsv($handle, 999999, ',', '"')) !== false) {
                $data[] = $r;
            }
            fclose($handle);
        }
        return $data;
    }

    function add_comments($post_id, $data) {
        // First get a list of the comments for this post
        $comments = array();
        foreach ($data as $k => $v) {
            // comments start with cvs_comment_
            if (    preg_match('/^csv_comment_([^_]+)_(.*)/', $k, $matches) &&
                    $v != '') {
                $comments[$matches[1]] = 1;
            }
        }
        // Sort this list which specifies the order they are inserted, in case
        // that matters somewhere
        ksort($comments);

        // Now go through each comment and insert it. More fields are possible
        // in principle (see docu of wp_insert_comment), but I didn't have data
        // for them so I didn't test them, so I didn't include them.
        $count = 0;
        foreach ($comments as $cid => $v) {
            $new_comment = array(
                'comment_post_ID' => $post_id,
                'comment_approved' => 1,
            );

            if (isset($data["csv_comment_{$cid}_author"])) {
                $new_comment['comment_author'] = convert_chars(
                    $data["csv_comment_{$cid}_author"]);
            }
            if (isset($data["csv_comment_{$cid}_author_email"])) {
                $new_comment['comment_author_email'] = convert_chars(
                    $data["csv_comment_{$cid}_author_email"]);
            }
            if (isset($data["csv_comment_{$cid}_url"])) {
                $new_comment['comment_author_url'] = convert_chars(
                    $data["csv_comment_{$cid}_url"]);
            }
            if (isset($data["csv_comment_{$cid}_content"])) {
                $new_comment['comment_content'] = convert_chars(
                    $data["csv_comment_{$cid}_content"]);
            }
            if (isset($data["csv_comment_{$cid}_date"])) {
                $new_comment['comment_date'] = $this->parse_date(
                    $data["csv_comment_{$cid}_date"]);
            }

            $id = wp_insert_comment($new_comment);
            if ($id) {
                $count++;
            } else {
                $this->log['error'][] = "Could not add comment $cid";
            }
        }
        return $count;
    }

    function create_custom_fields($post_id, $data) {
        foreach ($data as $k => $v) {
            // anything that doesn't start with csv_ is a custom field
            if (!preg_match('/^csv_/', $k) && $v != '') {
                add_post_meta($post_id, $k, $v);
            }
        }
    }

    function get_auth_id($author) {
        if (is_numeric($author)) {
            return $author;
        }
        $author_data = get_userdatabylogin($author);
        return ($author_data) ? $author_data->ID : 0;
    }

    // Convert date in CSV file to 1999-12-31 23:52:00 format
    function parse_date($data) {
        $timestamp = strtotime($data);
        if (false === $timestamp) {
            return '';
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
    }

    /**
     * delete BOM from UTF-8 file
     * @param <type> $fname
     */
    function stripBOM($fname) {
        $res = fopen($fname, 'rb');
        if (false !== $res) {
            $bytes = fread($res, 3);
            if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $this->log['notice'][] = 'Getting rid of byte order mark...';
                fclose($res);

                $contents = file_get_contents($fname);
                if (false === $contents) {
                    trigger_error('Failed to get file contents.', E_USER_WARNING);
                }
                $contents = substr($contents, 3);
                $success = file_put_contents($fname, $contents);
                if (false === $success) {
                    trigger_error('Failed to put file contents.', E_USER_WARNING);
                }
            } else {
                fclose($res);
            }
        } else {
            $this->log['error'][] = 'Failed to open file, aborting.';
        }
    }

    // PHP4 compatibility
    function CSVImporterPlugin() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'CSVImporterPlugin' ); function CSVImporterPlugin() { global $CSVImporterPlugin; $CSVImporterPlugin = new CSVImporterPlugin(); }
?>