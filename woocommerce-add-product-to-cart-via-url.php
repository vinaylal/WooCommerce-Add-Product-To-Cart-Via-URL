<?php
/*
Plugin Name:  Add Product To Cart Via URL
Plugin URI:   https://betterdeveloperdocs.com/woocommerce-add-product-to-cart-via-url/
Description:  Allows a CMS users (eg shop admin) to create a URL (for WooCommerce only) with specific product(s) and quantity info. When clicked by a user this URL will load those products into the users cart and take them to the checkout page automatically.
Version:      1.0
Author:       Better Developer Docs 
Author URI:   https://betterdeveloperdocs.com/about/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  WooCommerce
Domain Path:  /languages
*/

define('WCAD_PLUGIN_DIR', plugin_dir_url(realpath(__FILE__)));

$allowedElements = array(
	'a' => array(
		'href' => array(),
		'title' => array(),
		'class' => array(),
		'id' => array(),
		'data-index' => array(),
	),
	'select' => array(),
	'div' => array(
		'class' => array(),
		'id' => array(),
	),
	'form' => array(
		'class' => array(),
		'id' => array(),
		'autocomplete' => array(),
	),
	'p' => array(
		'class' => array(),
		'id' => array(),
	),
	'select' => array(
		'class' => array(),
		'id' => array(),
		'data-search' => array(),
	),
	'option' => array(
		'class' => array(),
		'id' => array(),
		'value' => array(),
	),
	'input' => array(
		'class' => array(),
		'id' => array(),
		'placeholder' => array(),
		'placeholder' => array(),
		'type' => array(),
		'value' => array(),
	)
);
define('WCAD_ALLOWED_ELEMENTS', $allowedElements);

function wcad_custom_register_scripts() {
	wp_enqueue_style( 'wcad-css', WCAD_PLUGIN_DIR .'assets/css/wcad.css', '', '1.0', false );
}
add_action( 'wp_enqueue_scripts', 'wcad_custom_register_scripts' );

// Add the shortcode function so it recognised by the WP System
add_shortcode('wc-cart-url-form', 'wcad_product_url_form');
	
// this is the shortcode function
function wcad_product_url_form() {

  	// lets do some basic checks first
  	if (!is_user_logged_in()) {
	    $output = '<p>You need to be logged into the WP CMS to view this page</p>';
	    return wp_kses($output, WCAD_ALLOWED_ELEMENTS);
	}

	if (!class_exists('WooCommerce')) {
	    $output = '<p>WooCommerce is not installed AND activated - install and create some products before attempting to use.</p>';
	    return wp_kses($output, WCAD_ALLOWED_ELEMENTS);
	}

  	// If you want to edit the HTML in the form you can edit the $output variable below
    $output = '<div class="container">
			    	<div class="row">
			    		<form autocomplete="off" id="wcad_product_url_form">
			    			<p class="wcad_error_message"></p>
			    			<div class="product_url_wrapper">
			    				<div class="setRow prod_qty_set">

										<select data-search="true" id="wcad_prod_url_select_1" class="prod_url_select" placeholder="Search for product of choice...">
				   							<option value="0">Search for product of choice...</option>';

				   								// the dropdown will have all published products but you could edit the selection by using the get_posts() below. See - https://developer.wordpress.org/reference/functions/get_posts/
										    	$products = get_posts( array(
											        'post_type' => 'product',
											        'numberposts' => -1,
											        'post_status' => 'publish',
											    ) );

											    foreach( $products as $product ){
											        $output .= '<option value="' . $product->ID . '">' . $product->post_title . '</option>';
											    }

							$output .= '</select>
			    					
			    						<input  placeholder="Quantity" type="number" class="prod_url_qty" value="1">
		    					</div>
			    			</div>
			    			<div class="setRow">
			    				<a href="' . '#' . '" id="wcad_new_product">Add New Product Row</a>
		    					<input type="submit" value="Get URL" id="wcad_submit_product_url_list">
			    			</div>
			    		</form>
			    		<div class="copybox setRow">
			    			<input type="text" id="urlHolder" value="No value yet"></input>
			    			<a class="wcadHide button" href="'. '#' .'" id="wcad_copy_url">Copy this URL</a>
		    			</div>
			    	</div>
			    </div>';

	return wp_kses($output, WCAD_ALLOWED_ELEMENTS);
}

// Include WP Code to support AJAX
add_action( 'wp_enqueue_scripts', 'wcad_producturlform_ajax_enqueue' );
	
function wcad_producturlform_ajax_enqueue() {
	// Enqueue javascript on the frontend.
	wp_enqueue_script(
		'wcad_producturlform-ajax-script',
		WCAD_PLUGIN_DIR . 'assets/js/wcad.js',	
		array('jquery')
	);

	// The wp_localize_script allows us to output the ajax_url path for our script to use.
	wp_localize_script(
		'wcad_producturlform-ajax-script',
		'wcad_producturlform_ajax_obj',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce('ajax-nonce')
		)
	);
}

add_action( 'wp_ajax_wcad_producturlform_ajax_request', 'wcad_producturlform_ajax_request' );
   	
// If the user clicks the button with '+' symbol this function is called and adds another input and quantity set of fields for them to select another product.
function wcad_producturlform_ajax_request() {

	// lets sanitize and do some basic checks 
	$num_selects = sanitize_text_field($_REQUEST['num_selects']);
   	if(!isset($num_selects)){
    	print_r(wp_kses('<p>The number of select field (num_selects) is not set and therefore we cannot proceed with the AJAX call. Check the form is not mal formed.</p>', WCAD_ALLOWED_ELEMENTS));
    	die();
    }

	$products = get_posts( array(
		'post_type' => 'product',
		'numberposts' => -1,
		'post_status' => 'publish',
	) );

	$output = array();
	$output = '<div class="csetRow prod_qty_set" id="wcad_prod_qty_set_' . $num_selects . '"><select id="wcad_prod_url_select_' . $num_selects . '" class="prod_url_select" placeholder="Search for product of choice...">';
	$output .= '<option value="0">Search for product of choice...</option>';
	foreach( $products as $product ){
		$output .= '<option value="' . $product->ID . '">' . $product->post_title . '</option>';
	}

	$output .= '</select><input  placeholder="Quantity" type="number" class="prod_url_qty" value="1"><a href="'. '#'.'" class="wcad_remove_input" data-index="' . $num_selects . '">x</a>';
	
	print_r(wp_kses($output, WCAD_ALLOWED_ELEMENTS));
    die();
}

function wcad_add_multiple_products_to_cart( $url = false ) {

	// sanitize our request variables
	$add_to_cart_clean = sanitize_text_field($_REQUEST['add-to-cart']);
	// Make sure WC is installed, and add-to-cart query arg exists, and contains at least one comma.
	if ( 
		! class_exists( 'WC_Form_Handler' ) || 
		empty( $add_to_cart_clean ) || 
		false === strpos( $add_to_cart_clean, ',' ) 
	){
		return;
	}else{
		// Remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
		remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );
		$product_ids = explode( ',', $add_to_cart_clean );

		foreach ( $product_ids as $id_and_quantity ) {
			// single product and qty combo URL: https://yoursite.com/checkout/?add-to-cart=635:1,
			// multiple products and qty combo URL: https://yoursite.com/checkout/?add-to-cart=635:1,6452:2
			// todo: support for variable and grouped products to come
			$id_and_quantity = explode( ':', $id_and_quantity );
			$product_id = $id_and_quantity[0];
			$quantity = $id_and_quantity[1];
			WC()->cart->add_to_cart( $product_id, $quantity );
		}
		return;
	}
}

// Fire before the WC_Form_Handler::add_to_cart_action callback.
add_action( 'wp_loaded', 'wcad_add_multiple_products_to_cart', 15 );
