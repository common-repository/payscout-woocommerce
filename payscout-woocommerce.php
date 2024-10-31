<?php
/**
 * Plugin Name: Woocommerce Payscout Payment Gateway 
 * Plugin URI: Plugin URI: https://wordpress.org/plugins/authorizenet-woocommerce-lightweight-addon/
 * Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via Payscout.
 * Version: 1.0.0
 * Author: Victor Vally, Alex Bordbar
 * Author URI: https://www.payscout.com/
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function payscout_init()
{
	
	function add_payscout_gateway_class( $methods ) 
	{
		$methods[] = 'WC_Payscout_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_payscout_gateway_class' );
	
	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_Payscout_Gateway extends WC_Payment_Gateway 
		{
		public function __construct()
		{

		$this->id               = 'payscout';
		$this->icon             = plugins_url( 'images/payscout.png' , __FILE__ ) ;
		$this->has_fields       = true;
		$this->method_title     = 'Payscout Settings';		
		$this->init_form_fields();
		$this->init_settings();
		$this->supports                     = array(  'default_credit_card_form');
		$this->title			           		   = $this->get_option( 'payscout_title' );
		$this->payscout_username        = $this->get_option( 'payscout_username' );
		$this->payscout_password  = $this->get_option( 'payscout_password' );		
		$this->payscout_cardtypes       = $this->get_option( 'payscout_cardtypes'); 
		
		$this->payscout_liveurl         = 'https://secure.payscout.com/api/transact.php';       
        
			
		 if (is_admin()) 
		 {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 		 }

		}
		
		
		
		public function admin_options()
		{
		?>
		<h3><?php _e( 'Payscout Payment Gateway', 'woocommerce' ); ?></h3>
		<p><?php  _e( 'payscout is a payment gateway service provider allowing merchants to accept credit card.', 'woocommerce' ); ?></p>
		<table class="form-table">
		  <?php $this->generate_settings_html(); ?>
		</table>
		<?php
		}
		
		
		
		public function init_form_fields()
		{
		$this->form_fields = array
		(
			'enabled' => array(
			  'title' => __( 'Enable/Disable', 'woocommerce' ),
			  'type' => 'checkbox',
			  'label' => __( 'Enable Payscout', 'woocommerce' ),
			  'default' => 'yes'
			  ),
			'payscout_title' => array(
			  'title' => __( 'Title', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This controls the title which the buyer sees during checkout.', 'woocommerce' ),
			  'default' => __( 'Payscout', 'woocommerce' ),
			  'desc_tip'      => true,
			  ),
			'payscout_username' => array(
			  'title' => __( 'Merchant Username', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the Merchant username provide by Payscout.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Payscout Merchant Username'
			  ),
			'payscout_password' => array(
			  'title' => __( 'Merchant Password', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the merchant password.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Payscout Merchant Password'
			  ),
			'payscout_cardtypes' => array(
			 'title'    => __( 'Accepted Cards', 'woocommerce' ),
			 'type'     => 'multiselect',
			 'class'    => 'chosen_select',
			 'css'      => 'width: 350px;',
			 'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
			 'options'  => array(
				'mastercard'       => 'MasterCard',
				'visa'             => 'Visa',
				'discover'         => 'Discover',
				'amex' 		    => 'American Express',
				'jcb'		    => 'JCB',
				'dinersclub'       => 'Dinners Club',
			 ),
			 'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
			),
	  	);
  		}
			


  		/*Get Icon*/
		public function get_icon() {
		$icon = '';
		if(is_array($this->payscout_cardtypes ))
		{
        foreach ( $this->payscout_cardtypes  as $card_type ) {

				if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
					
					$icon .= '<img src="'.esc_url( $url ).'" alt="'.esc_attr( strtolower( $card_type ) ).'" />';
				}
			}
		}
		else
		{
			$icon .= '<img src="'.esc_url( plugins_url( 'images/payscout.png' , __FILE__ ) ).'" alt="Payscout Payment Gateway" />';	  
		}

         return apply_filters( 'woocommerce_payscout_icon', $icon, $this->id );
		}
 
		public function get_payment_method_image_url( $type ) {

		$image_type = strtolower( $type );
				return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.png' , __FILE__ ) ); 
		}
		/*Get Icon*/


		/*Get Card Types*/
		function get_card_type($number)
		{
		    $number=preg_replace('/[^\d]/','',$number);
		    if (preg_match('/^3[47][0-9]{13}$/',$number))
		    {
		        return 'amex';
		    }
		    elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		    {
		        return 'dinersclub';
		    }
		    elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
		    {
		        return 'discover';
		    }
		    elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
		    {
		        return 'jcb';
		    }
		    elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
		    {
		        return 'mastercard';
		    }
		    elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
		    {
		        return 'visa';
		    }
		    else
		    {
		        return 'unknown card';
		    }
		}// End of getcard type function
		
		
		//Function to check IP
		function get_client_ip() 
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
				$ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = '0.0.0.0';
			return $ipaddress;
		}
		
		//End of function to check IP

		/*Initialize Payscout Parameters*/
		public function payscout_params($wc_order)
      	{      

				$exp_date         = explode( "/", sanitize_text_field($_POST['payscout-card-expiry']));
				$exp_month        = str_replace( ' ', '', $exp_date[0]);
				$exp_year         = str_replace( ' ', '',$exp_date[1]);

				if (strlen($exp_year) == 2) {
				$exp_year += 2000;
				}
      	
				$payscout_params_args = array(
				'username'                  => $this->payscout_username,
				'password'               => $this->payscout_password,
				'type'                   => 'sale',
				'ccnumber'               => sanitize_text_field(str_replace(' ','',$_POST['payscout-card-number'])),
				'ccexp'               => $exp_month.$exp_year,
				'cvv'              => sanitize_text_field($_POST['payscout-card-cvc']), 
				'orderid'            => $wc_order->get_order_number(),
				'ponumber'			=> $wc_order->get_order_number(),
				'orderdescription'            => get_bloginfo('blogname').' Order #'.$wc_order->get_order_number(),
				'amount'                 => $wc_order->order_total,
				'firstname'             => $wc_order->billing_first_name ,
				'lastname'              => $wc_order->billing_last_name ,
				'company'                => $wc_order->billing_company ,
				'address1'                => $wc_order->billing_address_1,
				'address2'				=> $wc_order->billing_address_2,
				'country'                => $wc_order->billing_country,
				'phone'                  => $wc_order->billing_phone,
				'state'                  => $wc_order->billing_state,
				'city'                   => $wc_order->billing_city,
				'zip'                    => $wc_order->billing_postcode,
				'email'                  => $wc_order->billing_email,
				'fax'			       => '',
				'website'				=> '',
				'shipping_firstname'     => $wc_order->shipping_first_name,
				'shipping_lastname'      => $wc_order->shipping_last_name,
				'shipping_company'        => $wc_order->shipping_company,
				'shipping_address1'        => $wc_order->shipping_address_1,
				'shipping_address2'		=> $wc_order->shipping_address_2,
				'shipping_city'           => $wc_order->shipping_city,
				'shipping_state'          => $wc_order->shipping_state,
				'shipping_zip'            => $wc_order->shipping_postcode,
				'shipping_country'        => $wc_order->shipping_country,
				'ipaddress'		       => $this->get_client_ip(),
				'tax'                    => $wc_order->get_total_tax() ,
				'shipping'			       => $wc_order->get_total_shipping(),
				'shipping_email'   => ''
				  
				   );
        			 return $payscout_params_args;
     	 } // End of payscout_params
		
		
		
		
		
		/*Payment Processing Fields*/
		public function process_payment($order_id)
		{
		
			global $woocommerce;
         		$wc_order = new WC_Order($order_id);
         		
			$cardtype = $this->get_card_type(sanitize_text_field(str_replace(' ','',$_POST['payscout-card-number'])));
			
         		if(!in_array($cardtype ,$this->payscout_cardtypes ))
         		{
         			wc_add_notice('Merchant do not support accepting in '.$cardtype,  $notice_type = 'error' );
         			return array (
								'result'   => 'success',
								'redirect' => WC()->cart->get_checkout_url(),
							   );
				die;
         		}
         
			
			$gatewayurl = $this->payscout_liveurl;
			
			
			$params = $this->payscout_params($wc_order);
         
			$post_string = '';
			foreach( $params as $key => $value )
			{ 
			  $post_string .= urlencode( $key )."=".urlencode($value )."&"; 
			}
			$post_string = rtrim($post_string,"&");

			/*HTTP POST API*/
				$response = wp_remote_post( $gatewayurl, array(
					'method'       => 'POST',
					'body'         => $post_string,
					'redirection'  => 0,
					'timeout'      => 70,
					'sslverify'    => false,
				) );
			
				if ( is_wp_error( $response ) ) throw new Exception( __( 'Problem connecting to the payment gateway.', 'woocommerce' ) );
			
				if ( empty( $response['body'] ) ) throw new Exception( __( 'Empty Payscout response.','woocommerce') );
			
				$content = $response['body'];
				/*foreach ( preg_split("/\r?\n/", $content) as $line ) {
					if ( preg_match("/^1|2|3\|/", $line ) ) {
						$response_array = explode( "&", $line );
					}
				}*/
				/*HTTP POST API */
		
		
		
		$response_array = array();

			$arrresults = explode('&', $content);
			$i = 1;
			foreach ($arrresults as $arresult) {
				$response_array[$i] = trim($arresult, '"');
				$i++;
			}
			
			

		if ( count($response_array) > 1 )
		{
			if( str_replace("response=", "", $response_array[1]) == '1' )
			{
				$wc_order->add_order_note( __( str_replace("responsetext=", "", $response_array[2]). 'on '.date("d-m-Y h:i:s e").' with Transaction ID = '.str_replace("transactionid=", "", $response_array[4]).' using payscout gateway, authorization code ='.str_replace("responsetext=", "", $response_array[1]).', card code verification=1, cardholder authentication verification response code='.str_replace("response_code=", "", $response_array[2]), 'woocommerce' ) );
			
			$wc_order->payment_complete(str_replace("responsetext=", "", $response_array[2]));
			WC()->cart->empty_cart();
			return array (
						'result'   => 'success',
						'redirect' => $this->get_return_url( $wc_order ),
					   );
			}
			else 
			{
				$wc_order->add_order_note( __( 'payment failed.'.str_replace("responsetext=", "", $response_array[2]).'--'.'--', 'woocommerce' ) );
				wc_add_notice('Error Processing Payments', $notice_type = 'error' );
			}
		}
		else 
		{
			$wc_order->add_order_note( __( 'payment failed.', 'woocommerce' ) );
			wc_add_notice('Error Processing Payments', $notice_type = 'error' );
		}
        
		}// End of process_payment
		
		
		}// End of class WC_Payscout_Gateway
	} // End if WC_Payment_Gateway
}// End of function payscout_init

add_action( 'plugins_loaded', 'payscout_init' );


/*Plugin Settings Link*/
function payscout_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_payscout_gateway">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'payscout_settings_link' );