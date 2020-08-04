<?php
/*
 * Plugin Name: Decidir Genosha Payment Gateway
 * Plugin URI: https://genosha.com.ar
 * Description: Pagos para SPS Decidir.
 * Author: Genosha
 * Author URI: http://genosha.com.ar
 * Version: 1.0.1
 * */
define('BASE_PATH', plugin_dir_path(__FILE__));
define('BASE_URL', plugin_dir_url(__FILE__));

// incluimos autoload de composer ya que estamos usando SDK de Decidir, info: https://github.com/decidir/sdk-php-v2
require BASE_PATH . 'vendor/autoload.php';

defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}



function genosha_sps_decidir( $gateways ) {
	$gateways[] = 'WC_Gateway_SPS_Decidir'; //convencion para nombre de clase WC_Gateway_aca_el_nombre_que_quieras
	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'genosha_sps_decidir' );

function wc_genosha_sps_decidir_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=genosha_decidir' ) . '">' . __( 'Configurar', 'genosha' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_genosha_sps_decidir_links' );



add_action( 'plugins_loaded', 'genosha_sps_init', 11 );

function genosha_sps_init() {

    class WC_Gateway_SPS_Decidir extends WC_Payment_Gateway 
    {
        //constructor
        public function __construct()
        {
            $this->id = 'genosha_decidir'; //id del pago
            $this->icon = BASE_PATH . 'img/logo.png'; //icono que se muestra en el checkout
            $this->has_fields = true; //para crear el formulario personalizado
            $this->method_title = 'SPS Decidir'; 
            $this->method_description ='Pagar con tarjeta de crédito y débito (solo Argentina)';

            $this->support = ['products']; //los pagos pueden soportar productos (products), suscripciones (subscriptions), reembolsos (refunds), en este caso es solo para productos.

            $this->init_form_field(); //inicia todas las opciones
            $this->init_settings(); //iniciamos las opciones del plugin
            
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->public_key = $this->get_option('public_key');
            $this->private_key = $this->get_option('private_key');

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); //guardamos las opciones
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) ); //gracias

			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 ); //emails

            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) ); //registrmos los scripts y css
            
        }

        //opciones del plugin
        public function init_form_field()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Activar/Desactivar',
                    'label'       => 'Activar SPS Decidir',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Titulo',
                    'type'        => 'text',
                    'description' => 'Titulo que verán los clientes durante el checkout.',
                    'default'     => 'Tarjeta de Crédito/Débito',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Descripción',
                    'type'        => 'textarea',
                    'description' => 'Descripción que verán los clientes durante el checkout.',
                    'default'     => 'Pagar con tarjeta de crédito o débito median SPS Decidir.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Activar Modo Test',
                    'type'        => 'checkbox',
                    'description' => 'Para pruebas del plugin.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'public_key' => array(
                    'title'       => 'Public Key',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Private Key',
                    'type'        => 'text'
                )
            );
        }

        //formulario
        public function payment_field() 
        {

        }

        //scripts y css
        public function payment_scripts()
        {

        }

        //validacion
        public function validate_fields()
        {

        }

        //proceso de pago
        public function proccess_payment( $order_id )
        {

        }

        //gracias
        public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
        }

        //email
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

        //webhooks, en caso de necesitarse
        public function webhooks()
        {

        }

    }

}