<?php
/**
 * Hamrahpay payment gateway class.
 *
 * @author   Hamrahpay
 * @package  LearnPress/Hamrahpay/Classes
 * @version  1.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Gateway_Hamrahpay' ) ) {
	/**
	 * Class LP_Gateway_Hamrahpay
	 */
	class LP_Gateway_Hamrahpay extends LP_Gateway_Abstract {

		/**
		 * @var array
		 */
		private $form_data = array();

		/**
		 * @var string
		 */
		private $api_version    = "v1";
		/**
		 * @var string
		 */
		private $api_url        = 'https://api.hamrahpay.com/api';
		/**
		 * @var string
		 */
		private $second_api_url = 'https://api.hamrahpay.ir/api';
		
		/**
		 * @var string
		 */
		private $API_Key = null;
		
		/**
		 * @var array|null
		 */
		protected $settings = null;

		/**
		 * @var null
		 */
		protected $order = null;

		/**
		 * @var null
		 */
		protected $posted = null;

		/**
		 *
		 * @var string
		 */
		protected $payment_token = null;
		protected $pay_url = null;

		/**
		 * LP_Gateway_Hamrahpay constructor.
		 */
		public function __construct() {
			$this->id = 'hamrahpay';
			$this->api_url          .= '/'.$this->api_version;
			$this->second_api_url   .= '/'.$this->api_version;
		
			$this->method_title       =  'همراه پی';
			$this->method_description = 'پرداخت با درگاه پرداخت همراه پی';
			$this->icon               = '';

			// Get settings
			$this->title       = LP()->settings->get( "{$this->id}.title", $this->method_title );
			$this->description = LP()->settings->get( "{$this->id}.description", $this->method_description );

			$settings = LP()->settings;

			// Add default values for fresh installs
			if ( $settings->get( "{$this->id}.enable" ) ) {
				$this->settings                     = array();
				$this->settings['api_key']        = $settings->get( "{$this->id}.api_key" );
			}
			
			$this->API_Key = $this->settings['api_key'];

			
			
			if ( did_action( 'learn_press/hamrahpay-add-on/loaded' ) ) {
				return;
			}

			// check payment gateway enable
			add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array(
				$this,
				'hamrahpay_available'
			), 10, 2 );

			do_action( 'learn_press/hamrahpay-add-on/loaded' );

			parent::__construct();
			
			// web hook
			if ( did_action( 'init' ) ) {
				$this->register_web_hook();
			} else {
				add_action( 'init', array( $this, 'register_web_hook' ) );
			}
			add_action( 'learn_press_web_hooks_processed', array( $this, 'web_hook_process_hamrahpay' ) );
			
			add_action("learn-press/before-checkout-order-review", array( $this, 'error_message' ));
		}
		

		// This method sends the data to api
		private function post_data($url,$params)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
			]);
			$result = curl_exec($ch);
			//echo curl_error($ch);
			curl_close($ch);
	
			return $result;
		}
	
		// This method returns the api url
		private function getApiUrl($end_point,$use_emergency_url=false)
		{
			if (!$use_emergency_url)
				return $this->api_url.$end_point;
			else
			{
				return $this->second_api_url.$end_point;
			}
		}


		/**
		 * Register web hook.
		 *
		 * @return array
		 */
		public function register_web_hook() {
			learn_press_register_web_hook( 'hamrahpay', 'learn_press_hamrahpay' );
		}
			
		/**
		 * Admin payment settings.
		 *
		 * @return array
		 */
		public function get_settings() {

			return apply_filters( 'learn-press/gateway-payment/hamrahpay/settings',
				array(
					array(
						'title'   => 'فعال سازی',
						'id'      => '[enable]',
						'default' => 'no',
						'type'    => 'yes-no'
					),
					
					array(
						'type'       => 'text',
						'title'      => 'عنوان درگاه پرداخت',
						'default'    => 'همراه پی',
						'id'         => '[title]',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'type'       => 'textarea',
						'title'      => 'توضیحات',
						'default'    => 'پرداخت با درگاه پرداخت همراه پی',
						'id'         => '[description]',
						'editor'     => array(
							'textarea_rows' => 5
						),
						'css'        => 'height: 100px;',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'title'      => 'API Key',
						'id'         => '[api_key]',
						'type'       => 'text',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '[enable]',
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					)
				)
			);
		}

		/**
		 * Payment form.
		 */
		public function get_payment_form() {
			ob_start();
			$template = learn_press_locate_template( 'form.php', learn_press_template_path() . '/addons/hamrahpay-payment/', LP_ADDON_HAMRAHPAY_PAYMENT_TEMPLATE );
			include $template;

			return ob_get_clean();
		}

		/**
		 * Error message.
		 *
		 * @return array
		 */
		public function error_message() {
			if(!isset($_SESSION))
				session_start();
			if(isset($_SESSION['hamrahpay_error']) && intval($_SESSION['hamrahpay_error']) === 1) {
				$_SESSION['hamrahpay_error'] = 0;
				$template = learn_press_locate_template( 'payment-error.php', learn_press_template_path() . '/addons/hamrahpay-payment/', LP_ADDON_HAMRAHPAY_PAYMENT_TEMPLATE );
				include $template;
			}
		}
		
		/**
		 * @return mixed
		 */
		public function get_icon() {
			if ( empty( $this->icon ) ) {
				$this->icon = LP_ADDON_HAMRAHPAY_PAYMENT_URL . 'assets/images/logo.png';
			}

			return parent::get_icon();
		}

		/**
		 * Check gateway available.
		 *
		 * @return bool
		 */
		public function hamrahpay_available() {

			if ( LP()->settings->get( "{$this->id}.enable" ) != 'yes' ) {
				return false;
			}

			return true;
		}
		
		/**
		 * Get form data.
		 *
		 * @return array
		 */
		public function get_form_data() {
			if ( $this->order ) {
				$user            = learn_press_get_current_user();
				$currency_code = learn_press_get_currency()  ;
				if ($currency_code == 'IRT') {
					$amount = $this->order->order_total * 10 ;
				} else {
					$amount = $this->order->order_total ;
				}

				$this->form_data = array(
					'amount'      => $amount,
					'currency'    => strtolower( learn_press_get_currency() ),
					'token'       => $this->token,
					'description' => sprintf( "پرداخت برای %s", $user->get_data( 'email' ) ),
					'customer'    => array(
						'name'          => $user->get_data( 'display_name' ),
						'billing_email' => $user->get_data( 'email' ),
					),
					'errors'      => isset( $this->posted['form_errors'] ) ? $this->posted['form_errors'] : ''
				);
			}

			return $this->form_data;
		}
		
		/**
		 * Validate form fields.
		 *
		 * @return bool
		 * @throws Exception
		 * @throws string
		 */
		public function validate_fields() {
			$posted        = learn_press_get_request( 'learn-press-hamrahpay' );
			$email   = !empty( $posted['email'] ) ? $posted['email'] : "";
			$mobile  = !empty( $posted['mobile'] ) ? $posted['mobile'] : "";
			$error_message = array();
			if ( !empty( $email ) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$error_message[] = "فرمت ایمیل صحیح نیست";
			}
			if ( !empty( $mobile ) && !preg_match("/^(09)(\d{9})$/", $mobile)) {
				$error_message[] = "فرمت شماره موبایل صحیح نیست";
			}
			
			if ( $error = sizeof( $error_message ) ) {
				throw new Exception( sprintf( '<div>%s</div>', join( '</div><div>', $error_message ) ), 8000 );
			}
			$this->posted = $posted;

			return $error ? false : true;
		}
		
		/**
		 * Hamrahpay payment process.
		 *
		 * @param $order
		 *
		 * @return array
		 * @throws string
		 */
		public function process_payment( $order ) {

			if ( $this->get_form_data() ) 
			{
				$this->order = learn_press_get_order( $order );
				$checkout = LP()->checkout();
				$data = [
					'api_key' => $this->API_Key,
					'amount' => $this->form_data['amount'],
					'description' => $this->form_data['description'],
					'email' => (!empty($this->posted['email'])) ? $this->posted['email'] : "",
					'mobile' => (!empty($this->posted['mobile'])) ? $this->posted['mobile'] : "",
					'callback_url' => get_site_url() . '/?' . learn_press_get_web_hook( 'hamrahpay' ) . '=1&order_id='.$this->order->get_id(),
				];
								
				$result = json_decode($this->post_data($this->getApiUrl('/rest/pg/pay-request'),$data),true);
				
				if (!empty($result['status']) && $result['status']==1){
					$this->payment_token = $result['payment_token'];
					$this->pay_url = $result['pay_url'];
				}

				
				$json = array(
					'result'   => $this->payment_token!=null ? 'success' : 'fail',
					'redirect'   => $this->payment_token!=null ? $this->pay_url : ''
				);

				return $json;

			}
			else {
				return array('result'   =>  'fail',	'redirect'   => '');	
			}
		}


		/**
		 * Handle a web hook
		 *
		 */
		public function web_hook_process_hamrahpay() {
			$request = $_REQUEST;
			if(isset($request['learn_press_hamrahpay']) && intval($request['learn_press_hamrahpay']) === 1) {
				if ($request['status'] == 'OK') {
					$order = LP_Order::instance( $request['order_id'] );
					$currency_code = learn_press_get_currency();
					if ($currency_code == 'IRT') {
						$amount = $order->order_total * 10 ;
					} else {
						$amount = $order->order_total ;
					}	
					
					$data = array(
						'api_key' => $this->merchantID,
						'payment_token' => $_GET['payment_token'],
					);
					$result = json_decode($this->post_data($this->post_data($this->getApiUrl('/rest/pg/verify'),$data),true));		
					if($result['status'] == 100) {
						$request["reserve_number"] = $result['reserve_number'];
						$request["reference_number"] = $result['reference_number'];
						$this->payment_token = intval($_GET['payment_token']);
						$this->payment_status_completed($order , $request);
						wp_redirect(esc_url( $this->get_return_url( $order ) ));
						exit();
					}
				}
				
				if(!isset($_SESSION))
					session_start();
				$_SESSION['hamrahpay_error'] = 1;
				
				wp_redirect(esc_url( learn_press_get_page_link( 'checkout' ) ));
				exit();
			}
		}
		

		/**
		 * Handle a completed payment
		 *
		 * @param LP_Order
		 * @param request
		 */
		protected function payment_status_completed( $order, $request ) {

			// order status is already completed
			if ( $order->has_status( 'completed' ) ) {
				exit;
			}

			$this->payment_complete( $order, ( !empty( $request['reserve_number'] ) ? $request['reserve_number'] : '' ), "پرداخت با موفقیت انجام شد." );
			update_post_meta( $order->get_id(), '_hamrahpay_reference_number', $request['reference_number'] );
			update_post_meta( $order->get_id(), '_hamrahpay_reserve_number', $request['reserve_number'] );
			update_post_meta( $order->get_id(), '_hamrahpay_payment_token', $request['payment_token'] );
		}

		/**
		 * Handle a pending payment
		 *
		 * @param  LP_Order
		 * @param  request
		 */
		protected function payment_status_pending( $order, $request ) {
			$this->payment_status_completed( $order, $request );
		}

		/**
		 * @param        LP_Order
		 * @param string $txn_id
		 * @param string $note - not use
		 */
		public function payment_complete( $order, $trans_id = '', $note = '' ) {
			$order->payment_complete( $trans_id );
		}
	}
}