<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once(plugin_dir_path( __FILE__ ) . 'classes/InstagramHashViewer.class.php');

$hash_viewer = InstagramHashViewer::getInstance();
add_action('admin_post_submit-form', 'create__new_competition'); // If the user is logged in


function create_new_competition () {

}
