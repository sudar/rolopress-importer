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
also uses code from http://code.google.com/p/php-csv-parser/
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

/**
 * RoloPress CSV Importer class
 *
 */
class RoloPressCSVImporter {

    var $log = array(); // for outputing error messages

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'rolopress-importer', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks

        add_action( 'admin_menu', array(&$this, 'register_settings_page') );        // Settings hooks
    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_management_page( __('RoloPress Importer', 'rolopress-importer'), __('RoloPress Importer', 'rolopress-importer'), 8, 'rolopress-importer', array(&$this, 'settings_page') );
    }

    /**
     * Add settings page
     */
    function settings_page() {
        if (isset($_POST['rp-csv-import-button'])) {
            // if it is a form submit
            $this->import();
        }

        // form HTML {{{
?>

<div class="wrap">
    <h2><?php _e('RoloPress Importer', 'rolopress-importer'); ?></h2>
    <p><?php _e('You can import contacts into RoloPress which were exproted as .csv files from other places like Google Contacts or Outlook. Select the file and then click the import button to import contacts.', 'rolopress-importer'); ?></p>
    <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
        <!-- File input -->
        <p>
            <label for="rp-csv-import-file"><?php _e('CSV file', 'rolopress-importer'); ?>: </label>
            <input name="rp-csv-import-file" id="rp-csv-import-file" type="file" value="" aria-required="true" />
        </p>
        <p class="submit"><input type="submit" class="button" name="rp-csv-import-button" value="<?php _e('Import', 'rolopress-importer');?>" /></p>
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
    function import() {
        if (empty($_FILES['rp-csv-import-file']['tmp_name'])) {
            $this->log['error'][] = __('No file uploaded, aborting.', 'rolopress-importer');
            $this->print_messages();
            return;
        }

        require_once plugin_dir_path(__FILE__). 'File_CSV_DataSource/DataSource.php';

        $time_start = microtime(true);

        $csv = new File_CSV_DataSource;
        $file = $_FILES['rp-csv-import-file']['tmp_name'];
        $this->stripBOM($file);

        if (!$csv->load($file)) {
            $this->log['error'][] = __('Failed to load file, aborting.', 'rolopress-importer');
            $this->print_messages();
            return;
        }

        // pad shorter rows with empty values
        $csv->symmetrize();

        $skipped = 0;
        $imported = 0;

        foreach ($csv->connect() as $csv_data) {
            if ($post_id = $this->create_contact($csv_data)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        if (file_exists($file)) {
            @unlink($file);
        }

        $exec_time = microtime(true) - $time_start;

        if ($skipped) {
            $this->log['notice'][] = '<b>' . sprintf(_n('Skipped %d contact', 'Skipped %d contacts', $skipped, 'rolopress-importer'), $skipped) .  __('most likely due to empty title, body and excerpt).', 'rolopress-importer') . '</b>';
        }
        $this->log['notice'][] = '<b>' . sprintf(_n("Imported %d contact in %.2f seconds." , "Imported %d contacts in %.2f seconds.", $imported, 'rolopress-importer'), $imported, $exec_time);
        $this->print_messages();
    }

    /**
     * Create new contact
     *
     * @param <type> $data
     * @return <type>
     */
    function create_contact($details) {

        $new_contact = array();
        foreach ($details as $key => $value) {

            switch ($key) {
                case "First Name":
                case "First":
                    $new_contact['rolo_contact_first_name'] = $value;
                    break;

                case "Last Name":
                case "Last":
                    $new_contact['rolo_contact_last_name'] = $value;
                    break;

                case "Middle Name":
                case "Middle":
                    //TODO - Should include it. Right now ingnoring it.
                    break;

                case "Title":
                case "Job Title":
                    $new_contact['rolo_contact_title'] = $value;
                    break;

                case "Company":
                    $new_contact['rolo_contact_company'] = $value;
                    break;

                case "E-mail Address":
                case "Email":
                    $new_contact['rolo_contact_email'] = $value;
                    break;

                case "Home":
                case "Home Phone":
                    $new_contact['rolo_contact_phone_Home'] = $value;
                    break;

                case "Mobile":
                case "Mobile Phone":
                    $new_contact['rolo_contact_phone_Mobile'] = $value;
                    break;

                case "Work":
                case "Business Phone":
                    $new_contact['rolo_contact_phone_Work'] = $value;
                    break;

                case "Fax":
                case "Home Fax":
                case "Other Fax":
                    $new_contact['rolo_contact_phone_Fax'] = $value;
                    break;

                case "Other":
                case "Other Phone":
                    $new_contact['rolo_contact_phone_Other'] = $value;
                    break;

                case "Web Page":
                case "Personal Website":
                case "Business Website":
                    $new_contact['rolo_contact_website'] = $value;
                    break;

                case "MSN ID":
                    $new_contact['rolo_contact_IM_MSN'] = $value;
                    break;

                case "AIM ID":
                    $new_contact['rolo_contact_IM_AOL'] = $value;
                    break;

                case "Google ID":
                    $new_contact['rolo_contact_IM_Gtalk'] = $value;
                    break;

                case "Skype ID":
                    $new_contact['rolo_contact_IM_Skype'] = $value;
                    break;

                case "Work Address":
                case "Home Address":
                case "Business Address":
                case "Other Address":
                    //TODO: Should include street address
                    $new_contact['rolo_contact_address'] = $value;
                    break;

                case "Work City":
                case "Home City":
                case "Business City":
                case "Other City":
                    $new_contact['rolo_contact_city'] = $value;
                    break;

                case "Work State":
                case "Home State":
                case "Business State":
                case "Other State":
                    $new_contact['rolo_contact_state'] = $value;
                    break;

                case "Work Country":
                case "Home Country":
                case "Business Country":
                case "Other Country":
                    $new_contact['rolo_contact_country'] = $value;
                    break;

                case "Work Postal Code":
                case "Home Postal Code":
                case "Business Postal Code":
                case "Other Postal Code":
                case "Work ZIP":
                case "Home ZIP":
                case "Business ZIP":
                case "Other ZIP":
                    $new_contact['rolo_contact_zip'] = $value;
                    break;

                default:
                    break;
            }
        }

        $new_post = array();

        $new_post['post_title'] = $new_contact['rolo_contact_first_name'];
        if (isset($new_contact['rolo_contact_last_name'])) {
            $new_post['post_title'] .= ' ' . $new_contact['rolo_contact_last_name'];
        }

        $new_post['post_type'] = 'post';
        $new_post['post_status'] = 'publish';

        $contact_id = wp_insert_post($new_post);

        if ($contact_id != '') {

            // Store only first name and last name as seperate custom fields
            update_post_meta($contact_id, 'rolo_contact_first_name', $new_contact['rolo_contact_first_name']);
            update_post_meta($contact_id, 'rolo_contact_last_name', $new_contact['rolo_contact_last_name']);

            // store the rest as custom taxonomies
            wp_set_post_terms($contact_id, ($new_contact['rolo_contact_city'] == 'City') ? '' : $new_contact['rolo_contact_city'], 'city');
            wp_set_post_terms($contact_id, ($new_contact['rolo_contact_state'] == 'State') ? '' : $new_contact['rolo_contact_state'], 'state');
            wp_set_post_terms($contact_id, ($new_contact['rolo_contact_zip'] == 'Zip') ? '' : $new_contact['rolo_contact_zip'], 'zip');
            wp_set_post_terms($contact_id, ($new_contact['rolo_contact_country'] == 'Country') ? '' : $new_contact['rolo_contact_country'], 'country');

            // store the array as post meta
            update_post_meta($contact_id, 'rolo_contact' , $new_contact);

            // Set the custom taxonmy for the post
            wp_set_post_terms($contact_id, 'Contact', 'type');


            $company_name = $new_contact['rolo_contact_company'];

            if ($company_name != '') {
               // Set the custom taxonmy for the post
                wp_set_post_terms($contact_id, $company_name, 'company');

                $company_id = get_post_by_title(stripslashes($company_name));
                if (!$company_id) {
                    // create an empty post for company
                    $new_post = array();

                    $new_post['post_title'] = $company_name;
                    $new_post['post_type'] = 'post';
                    $new_post['post_status'] = 'publish';

                    $company_id = wp_insert_post($new_post);

                    // Store only company name as seperate custom field
                    update_post_meta($company_id, 'rolo_company_name', $company_name);

                    // Set the custom taxonmy for the post
                    wp_set_post_terms($company_id, 'Company', 'type');
                    wp_set_post_terms($company_id, $company_name, 'company');
                }
            }

            return $contact_id;
        } else {
            //some problem in importing the contact
            $this->log['error'][] = __('Failed to insert the contact into DB.', 'rolopress-importer');
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
                $this->log['notice'][] = __('Getting rid of byte order mark...', 'rolopress-importer');
                fclose($res);

                $contents = file_get_contents($fname);
                if (false === $contents) {
                    trigger_error(__('Failed to get file contents.', 'rolopress-importer'), E_USER_WARNING);
                }
                $contents = substr($contents, 3);
                $success = file_put_contents($fname, $contents);
                if (false === $success) {
                    trigger_error(__('Failed to put file contents.', 'rolopress-importer'), E_USER_WARNING);
                }
            } else {
                fclose($res);
            }
        } else {
            $this->log['error'][] = __('Failed to open file, aborting.', 'rolopress-importer');
        }
    }

    // PHP4 compatibility
    function RoloPressCSVImporter() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'RoloPressCSVImporter' ); function RoloPressCSVImporter() { global $RoloPressCSVImporter; $RoloPressCSVImporter = new RoloPressCSVImporter(); }
?>