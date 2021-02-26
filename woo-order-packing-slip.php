<?php
/**
 * Plugin Name: Woo Simple Packing Slip
 * Version: 1.2
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
    public $header = array('Image', 'SKU', 'UIN', 'PRD_CD', 'Product', 'Quantity', 'Box', 'Pallet');

    function __construct(){
        add_action( 'add_meta_boxes', array($this, 'add_meta_box') );
        add_action( 'admin_init', array($this, 'generate_csv') );
        add_action( 'admin_init', array($this, 'handleAddTemplate'));
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue') );
        add_action( 'admin_footer', array($this, 'addTemplate'));
    }

    public function enqueue(){
        if($this->isSingleOrderPage()){
            wp_enqueue_style(RK_OPS_DOMAIN, RK_OPS_URL . 'resources/style.css', array(), filemtime(RK_OPS_URL . 'resources/style.css'));
            wp_enqueue_script(RK_OPS_DOMAIN, RK_OPS_URL . 'resources/custom.js', array('jquery'), filemtime(RK_OPS_URL . 'resources/custom.js'));
        }
    }

    public function handleAddTemplate(){
        if(! empty( $_POST ) && check_admin_referer('handle_add_template', RK_OPS_DOMAIN)){
            $newTemplate = array();
            foreach($this->header as $item){
                if(isset($_POST[$item])){
                    $newTemplate[] = $item;
                }
            }
            if(!empty($newTemplate)){
                $oldTemplates = get_option(RK_OPS_DOMAIN);
                $oldTemplates[] = $newTemplate;
                update_option( RK_OPS_DOMAIN, $oldTemplates);
            }
        }
    }

    public function isSingleOrderPage(): bool
    {
        $screen = get_current_screen();
        if($screen->post_type == 'shop_order')  return true;
        return false;
    }

    public function addTemplate(){
        if($this->isSingleOrderPage()){
            $fields = '';
            foreach($this->header as $item){
                $fields .= sprintf('<label>%s<input type="checkbox" name="%1$s" value="%1$s"></label>', $item);
            }
            echo sprintf('<div class="form-packing-slip-add"><div class="packing-popup-container"><span class="packing-popup-close">&times;</span><h2>Add template</h2><form method="post">%s <button type="submit" class="button grant_access">Add</button>%s</form></div></div>', $fields, wp_nonce_field( 'handle_add_template', RK_OPS_DOMAIN ));
        }
    }

    public function add_meta_box(){
        add_meta_box('woo-order-packing-slip', 'Packing Slip', array($this, 'meta_box_content'), 'shop_order', 'side', 'high', null);
    }

    public function meta_box_content($object){
        $templates = get_option( RK_OPS_DOMAIN);
        if($templates){
            echo '<select id="package-template" name="package-template"><option value="none">Select Template</option>';
            foreach ($templates as $index => $template){
                echo sprintf('<option value="%s">%s</option>', $index, implode(', ', $template));
            }
            echo '</select>';
        }
        echo '<span id="packing-slip-add" class="button">Add Template</span>';
        if($templates) {
            echo sprintf('<a href="?post=%s&action=edit&%s=%1$s" id="package_download_csv" class="button disabled">Generate CSV</a>', $object->ID, RK_OPS_DOMAIN);
        }
    }

    public function generate_csv(){
        //Use 'current_user_can' instead of 'nonces'. Based on https://codex.wordpress.org/WordPress_Nonces
        if(current_user_can('manage_woocommerce') && isset($_GET[RK_OPS_DOMAIN]) && isset($_GET['templateId']) ){
            $templateId = $_GET['templateId'];
            $templates = get_option(RK_OPS_DOMAIN);
            if(isset($templates[$templateId])) {
                $fields =$templates[$templateId];
                $order = wc_get_order($_GET[RK_OPS_DOMAIN]);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="packing-slip-' . $order->get_id() . '.csv"');
                header('Pragma: no-cache');
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                $out = fopen('php://output', 'w');
                fputcsv($out, $fields, ',');
                foreach ($order->get_items() as $item_id => $item) {
                    $product = $item->get_product();
                    $values = array();
                    foreach ($fields as $field){
                        switch ($field){
                            case 'Image':
                                $values[] = wp_get_attachment_url($product->get_image_id());
                                break;
                            case 'SKU':
                                $values[] = $product->get_sku();
                                break;
                            case 'Product':
                                $values[] = $product->get_title();
                                break;
                            case 'Quantity':
                                $values[] = $item->get_quantity();
                                break;
                            default:
                                $values[] = '';
                                break;
                        }
                    }
                    fputcsv($out, $values, ',');
                }
                exit();
            }
        }
    }
}
$WOO_ORDER_PACKING_SLIP = new WOO_ORDER_PACKING_SLIP;