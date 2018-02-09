<?php
/*
Plugin Name: YOURLS EE Mass Import
Plugin URI: https://github.com/p-arnaud/yourls-ee-mass-import
Description: This plugin enables mass imports.
Version: 1.1
Author: p-arnaud
Author URI: https://github.com/p-arnaud
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();


// Register plugin page in admin page
yourls_add_action( 'plugins_loaded', 'ee_mass_import_display_panel' );
function ee_mass_import_display_panel() {
    yourls_register_plugin_page( 'ee_mass_import', 'YOURLS EE Mass Import', 'ee_mass_import_display_page' );
}


// Function which will draw the admin page
function ee_mass_import_display_page() {
    global $ydb;
    $expiration_date_plugin = yourls_is_active_plugin('yourls-ee-expiration-date/plugin.php');
    $password_plugin = yourls_is_active_plugin('yourls-ee-password/plugin.php');

    if (isset($_FILES['csv_file'])) {
        $row = 1;
        $first_line = true;
        if (($handle = fopen($_FILES['csv_file']['tmp_name'], "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($first_line == true) {
                    $first_line = false;
                    continue;
                }
                else {
                    $num = count($data);
                    $row++;
                    $url = $data[0];

                    if ($data[1] != "") {
                        $keyword = $data[1];
                        $keyword = str_replace(' ', '', $keyword);
                    }
                    else {
                        $keyword = '';
                    }

                    if ($data[2] != "") {
                        $title = $data[2];
                    }
                    else {
                        $title = '';
                    }
                    echo '<br>Importing <em>' . $url . '</em></br>';

                    if ($expiration_date_plugin == 1) {
                        $expiration_date = ee_expiration_date_sanitize_date($data[3]);
                        if ($data[3] != "" && !$expiration_date) {
                            echo '<strong>Link error</strong>, not inserted: Date error.<br>';
                            continue;
                        }
                    }

                    if ($password_plugin == 1) {
                        $password = yourls_sanitize_string($data[4]);
                        if ($data[4] != "" && !$password) {
                            echo '<strong>Link error</strong>, not inserted: Password error.<br>';
                            continue;
                        }
                    }

                    $result = yourls_add_new_link( $url, $keyword, $title );
                    if ($result['status'] == 'fail') {
                        echo '<strong>Link error</strong>, not inserted: '. $result['message'] . '<br>';
                        continue;
                    }

                    if ($expiration_date_plugin == 1 && $data[3] != "" && $expiration_date) {
                        if ($expiration_date) {
                            $shorturl = $result['url']['keyword'];
                            $ee_date_array = json_decode( $ydb->option[ 'ee_expirationdate' ], true );
                            $ee_date_array[$shorturl] = $expiration_date;
                            yourls_update_option( 'ee_expirationdate', json_encode( $ee_date_array ) );
                        }
                    }

                    if ($password_plugin == 1 && $data[4] != "" && $password) {
                        if ($password) {
                            $shorturl = $result['url']['keyword'];
                            $ee_password_array = json_decode( $ydb->option[ 'ee_password' ], true );
                            $ee_password_array[$shorturl] = $password;
                            yourls_update_option( 'ee_password', json_encode( $ee_password_array ) );
                        }
                    }
                }
            }
            fclose($handle);
        }
    }
    ?>
    <form action="plugins.php?page=ee_mass_import" method="post" enctype="multipart/form-data">
        <h2>Select CSV file to upload:</h2>
        <ul>
            <li>First line must be: <strong>url	name	shorturl	expirationdate	password</strong></li>
            <li><strong>url</strong> is mandatory</li>
            <li><strong>expirationdate</strong> format must be: <em>yyyy-mm-dd</em></li>
            <li>Separator must be <strong>,</strong></li>
        </ul>
        <input type="file" name="csv_file" id="csv_file">
        <input type="submit" value="Upload CSV" name="submit">
    </form>
    <?php
}

?>
