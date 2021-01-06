<?php
/**
 * Plugin Name: Woo Simple Packing Slip
 * Plugin URI: https://getrocketship.com/
 * Version: 1.0.1
 * Author: Alex Burca
 * Author URI: https://www.linkedin.com/in/burca-alexandru/
 * Text Domain: woo-order-packing-slip
 */

defined( 'RK_OPS_PATH' ) or define( 'RK_OPS_PATH', plugin_dir_path( __FILE__ ) );
defined( 'RK_OPS_URL' )  or define( 'RK_OPS_URL',  plugin_dir_url( __FILE__ ) );
defined( 'RK_OPS_BASE' ) or define( 'RK_OPS_BASE', plugin_basename( __FILE__ ) );
$version = get_file_data(RK_OPS_PATH.basename(RK_OPS_BASE), array('Version'), 'plugin');
$textDomain = get_file_data(RK_OPS_PATH.basename(RK_OPS_BASE), array('Text Domain'), 'plugin');
$pluginName = get_file_data(RK_OPS_PATH.basename(RK_OPS_BASE), array('Plugin Name'), 'plugin');
defined( 'RK_OPS_VERSION' ) or define( 'RK_OPS_VERSION', $version[0] );
defined( 'RK_OPS_DOMAIN' ) or define( 'RK_OPS_DOMAIN', $textDomain[0] );
defined( 'RK_OPS_NAME' ) or define( 'RK_OPS_NAME', $pluginName[0] );

class WOO_ORDER_PACKING_SLIP{
    function __construct(){
        add_action( 'add_meta_boxes', array($this, 'add_meta_box') );
        add_action( 'admin_init', array($this, 'generate_csv') );
    }

    public function add_meta_box(){
        add_meta_box('woo-order-packing-slip', 'Packing Slip', array($this, 'meta_box_content'), 'shop_order', 'side', 'high', null);
    }

    public function meta_box_content($object){
        echo sprintf('<a href="?post=%s&action=edit&%s=%1$s" class="button grant_access">Generate CSV</a>', $object->ID, RK_OPS_DOMAIN);
    }

    public function generate_csv(){
        //Use 'current_user_can' instead of 'nonces'. Based on https://codex.wordpress.org/WordPress_Nonces
        if(current_user_can('manage_woocommerce') && isset($_GET[RK_OPS_DOMAIN])){
            $order = wc_get_order( $_GET[RK_OPS_DOMAIN] );
            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment; filename="packing-slip-'.$order->get_id().'.csv"' );
            header( 'Pragma: no-cache' );
            header( "Expires: Sat, 26 Jul 1997 05:00:00 GMT" );
            $out = fopen('php://output', 'w');
            fputcsv($out, array('Image', 'SKU', 'UIN', 'PRD_CD', 'Product', 'Quantity', 'Box', 'Pallet'), ',');
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();
                fputcsv($out, array(
                    wp_get_attachment_url( $product->get_image_id() ),
                    $product->get_sku(),
                    '',
                    '',
                    $product->get_title(),
                    $item->get_quantity(),
                    '',
                    ''
                ), ',');
            }
            exit();
        }
    }
}
$WOO_ORDER_PACKING_SLIP = new WOO_ORDER_PACKING_SLIP;