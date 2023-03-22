<?php 
/*
Plugin Name: Sendinblue on Gravity Forms (Enhanced Edition)
Plugin URI: https://github.com/AmphiBee/gf-sendinblue-enhanced
Description:       Add on for Sendinblue on Gravity Forms (originally gf-sendinblue by Zypac)
Version:           1.1
Author:            AmphiBee
Author URI:        https://amphibee.fr/
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       gf-sendinblue 
*/

define('GF_SENDINBLUE_ADDON_VERSION', 1.0);
add_action( 'gform_loaded', array( 'GF_Sendinblue_AddOn_Bootstrap', 'load' ), 5 );
class GF_Sendinblue_AddOn_Bootstrap {
    public static function load() {
        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
        require_once( 'sendinblue-functions.php' );
        GFAddOn::register( 'GFSendinblueAddOn' );
    }
}

function gf_sendinblue_addon() {
    return GFSendinblueAddOn::get_instance();
}
