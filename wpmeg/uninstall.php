<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

remove_menu_page( 'wpmeg' );
delete_option( 'wpmeg_api' );
 ?>
