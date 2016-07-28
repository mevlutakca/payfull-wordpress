<?php
ini_set('display_errors', 1);

class WC_Gateway_Payfull extends WC_Payment_Gateway
{
    const INSTALLMENTS_TYPE_TABLE = "table";
    const INSTALLMENTS_TYPE_LIST = "list";
    
    protected static $_instance = null;
    
    private $_payfull;
    
    public $username = null;
    public $password = null;
    public $custom_css = null;
    public $endpoint = null;
    public $installments_type = "list";
    /**
     * @var array the HTML attributes to resner the iframe
     */
    public $options = [];

    public function __construct($register_hooks=true)
    {
        $this->id = 'woo_gateway_payfull';
        $this->icon = plugins_url('woo-gateway-payfull/assets/img/icon.png');
        $this->has_fields = false;
        $this->method_title = __('Payfull', 'payfull');
        $this->method_description = __('Process payment via Payfull service.', 'payfull');
        $this->order_button_text = __('Proceed to Payfull', 'payfull');
        $this->supports = array(
            'products',
			//'default_credit_card_form',
			'refunds',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');
        $this->username = $this->get_option('username');
        $this->password = $this->get_option('password');
        $this->custom_css = $this->get_option('custom_css');
        $this->endpoint = $this->get_option('endpoint');
        $this->installments_type = $this->get_option('installments_type');

        if($register_hooks) {
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_'.strtolower(__CLASS__), array( $this, 'check_payment_response' ) );
        }
    }
    
    public function version()
    {
        return "v1";
    }

    public function &payfull() {
        if(!$this->_payfull) {
            require_once 'PayfullService.php';
            $lang = get_locale();
            $lang = explode('_', $lang);
            $this->_payfull = new PayfullService([
                'username' => $this->username,
                'password' => $this->password,
                'endpoint' => $this->endpoint,
                'language' => $lang[0],
            ]);
        }
        
        return $this->_payfull;
    }
    
    public function initApiService()
    {
        add_rewrite_tag( '%payfull-api%', '([^&]+)' );
        add_action( 'template_redirect', array($this, 'handleApiRequest'));
    }
    
    public function handleApiRequest()
    {
        global $wp_query;

        $payfull = $wp_query->get( 'payfull-api' );

        if ( ! $payfull ) {
            return;
        }
        $params = explode('/', $payfull);
        $version = $params[0];
        $data = $_POST;
        $result = null;
        
        if(!isset($data['command'])) {
            throw new Exception("Invalide request.");
        }
        if($version!="v1") {
            throw new Exception("unsupported version.");
        }
        
        $cmd = $data['command'];
        switch($cmd) {
            case 'bin':
                $result = $this->payfull()->bin($data['bin']);
                break;
            case 'banks':
                $result = $this->payfull()->banks();
                break;
            default:
                $result = ['error' => true, 'message'=>'Unsupported command'];
                break;
        }   

        wp_send_json( $result );
    }

    /**
     * override
     */
    public function init_settings()
    {
        parent::init_settings();
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enabled', 'payfull'),
                'type' => 'checkbox',
//                'description' => __('Enable/Disable "Payfull" checkout.', 'payfull'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Title', 'payfull'),
                'type' => 'text',
                'description' => __('The title which the user will see in the checkout page.', 'payfull'),
                'default' => __('Payfull Checkout', 'payfull'),
            ],
            'description' => array(
                'title' => __('Description', 'wc_iyzicocheckout'),
                'type' => 'textarea',
                'description' => __('The message to display during checkout.', 'wc_iyzicocheckout'),
                'default' => __('Pay via Payfull, pay safely with your credit card.', 'payfull'),
            ),
            'endpoint' => [
                'title' => __('Endpoint', 'payfull'),
                'type' => 'text',
                'description' => __('The api url to "Payfull" service.', 'payfull'),
                'default' => '',
            ],
            'username' => array(
                'title' => __('Api Username', 'payfull'),
                'type' => 'text',
                'default' => '',
                // 'description' => __('', 'payfull'),
            ),
            'password' => array(
                'title' => __('Api Password', 'payfull'),
                'type' => 'text',
                'default' => '',
                // 'description' => __('', 'payfull'),
            ),
            'installments_type' => array(
                'title' => __('Installment Type', 'payfull'),
                'type' => 'select',
               'options'     => array(
                    self::INSTALLMENTS_TYPE_LIST          => __( 'Drop down list', 'payfull' ),
                    self::INSTALLMENTS_TYPE_TABLE         => __( 'Detailed Table', 'payfull' ),
                ),
                'description' => __('Choose how to render installments option.', 'payfull'),
            ),
            'custom_css' => [
                'title' => __('Custom Css', 'payfull'),
                'type' => 'textarea',
                'default' => file_get_contents (WP_PLUGIN_DIR. '/woo-gateway-payfull/assets/custom.css'),
//                'description' => __('Customiz the installments table.', 'payfull'),
            ],
        ];
    }

    function process_payment( $order_id ) {
    	global $woocommerce;
        
    	$order      = wc_get_order( $order_id );
        
        if(!$order) {
            wc_add_notice( __('Failed to process the payment because of invalid order', 'payfull'), 'error' );
            return array(
                'result' => 'error'
            );
        }
        
        if($order) {
            $checkout_payment_url = $order->get_checkout_payment_url(true);

            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    array(
                        'order' => $order->id,
                        'key' => $order->order_key,
                    ),
                    $checkout_payment_url
                ),
            );
        }

        wc_add_notice( __('Failed to process the payment because of invalid order', 'payfull'), 'error' );
        return null;

    }
    
    public function process_refund( $order_id, $amount = null,$reason = '' )
    {
        
        if(!isset($amount)) {
            return false;
        }
        $order = wc_get_order($order_id);
        
        if($order) {
            // $xid = $order->get_transaction_id();
            $xid = get_post_meta( $order->id, '_payfull_transaction_id', true );
            if(empty($xid)) {
                $order->add_order_note(__('Can not refund this order because the transaction id is missing.', 'payfull'));
                return false;
            }
            
            $crcy = $order->get_order_currency();
            $response = $this->payfull()->refund($xid, $amount);
            if(isset($response['status']) && $response['status']) {
                $order->add_order_note("Refunding {$crcy} {$amount} succeeded. Transaction Id: ".$response['transaction_id']);
                return true;
            }
            
            $order->add_order_note("Refunding {$crcy} {$amount} failed. Response: <pre>".print_r($response,1)."</pre>");
        }
        
        return false;
    }

    public function receipt_page($order_id)
    {
        $o = new WC_Order;
        $order = wc_get_order(isset($order_id) ? $order_id : false);
        if($order===false) {
            throw new \Exception('Invalid request, the order is not recognized.');
        }
        
        $data = [];
        do_action( 'woocommerce_credit_card_form_start', $this->id );
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            array_walk_recursive($data, function(&$item) {
                $item = sanitize_text_field($item);
            });
            
            $errors = $this->validatePaymentForm($data);
            if($errors !== true) {
                foreach ($errors as $err) {
                    wc_add_notice($err, 'error');
                }
            } else {
                $this->sendPayment($order, $data);
            }
        }
        
        $this->renderView('views/payment-form.php', [
            'this'=>$this,
            'id' => esc_attr($this->id),
            'order' => $order,
            'args' => $args,
            'form' => $data,
            'symbol' => get_woocommerce_currency_symbol($order->get_order_currency()),
            'custom_css' => $this->get_option('custom_css'),
            'use_installments_table' => $this->installments_type==self::INSTALLMENTS_TYPE_TABLE
        ]);
        do_action( 'woocommerce_credit_card_form_end', $this->id );
    }
    
    protected function sendPayment($order, $data)
    {
        $card = isset($data['card']) ? $data['card'] : null;
        $use3d = isset($data['use3d']) ? ($data['use3d']=="true") : false;
        
        $fname = $order->billing_first_name;
        $lname = $order->billing_last_name;

        $installments = intval($data['installment']);
        $expiry = array_map('trim', explode('/', $card['expiry']));
        $order->update_status('wc-pending', 'Process payment by Payfull');
        
        $request = [
            'total' => $order->get_total(),
            'currency' => $order->get_order_currency(),
            'installments' => $installments,
            'passive_data' => $order->id,//json_encode(['order-id' => $order->id]),
            'cc_name' => $card['holder'],
            'cc_number' => str_replace(' ', '', $card['pan']),
            'cc_month' => $expiry[0],
            'cc_year' => (strlen($expiry[1])==2 ? "20" : "").$expiry[1],
            'cc_cvc' => $card['cvc'],
            'customer_firstname' => $fname,
            'customer_lastname' => $lname,
            'customer_email' => $order->billing_email,
            'customer_phone' => $order->billing_phone,
            'payment_title' => "{$fname} {$lname} | order $order->id | ".$order->get_total().$order->get_order_currency(),
        ];

        if($installments > 1) {
            $request['bank_id'] = $data['bank'];
            $request['gateway'] = $data['gateway'];
        }

        if($use3d) {
            $checkout_url = $order->get_checkout_payment_url(true);
            $return_url = add_query_arg(['order-id'=>$order->id, 'wc-api'=>'WC_Gateway_Payfull'], $checkout_url);

            $request['use3d'] = 1;
            $request['return_url'] = $return_url;
        }
        
        $response = $this->payfull()->send('Sale', $request, !$use3d);

        if($use3d) {
            if(strpos($response, '<html>')===false) {
                echo $response;
                exit;
                $error = isset($response['ErrorMSG']) ? $response['ErrorMSG'] : __('Processing payment failed.', 'payfull');
                //$order->update_status('wc-failed', $error);
                wc_add_notice( $error, 'error' );
                $order->add_order_note('3D Payment failed. Response:<pre>' . print_r($response, 1).'</pre>');
                return;
            }
        }
        
        if($this->processPaymentResponse($order, $response)) {
            $thank_url = $order->get_checkout_order_received_url();
            wp_redirect($thank_url);
            exit;
        }
    }

    function check_payment_response()
    {
        global $woocommerce;
        
        if ( ! defined( 'ABSPATH' ) ) {
            throw new \Exception('Wordpress is not running.');
        }
        if(!defined('WOOCOMMERCE_VERSION')) {
            throw new \Exception('WooCommerce is not running.');
        }

        $order_id = isset($_GET['order-id']) ? $_GET['order-id'] : null;
        $order = wc_get_order($order_id);
        
        
        $type = "error";
        $title = __('Bad request', 'payfull');
        $data = $_POST;
        array_walk_recursive($data, function(&$item) {
            $item = sanitize_text_field($item);
        });
            
        $redirect_url = $woocommerce->cart->get_checkout_url();
        $status = isset($data['status']) ? $data['status'] : null;
        
        if(!isset($order)) {
            $message = __('Invalid data received from payment service.', 'payfull');
        }
        else if(!$order || $order->post_status != 'wc-pending' || $order->status=='completed') {
            $message = __('Invalid status for the requested order'. ' '.$order_id.'.', 'payfull');
        }
        else if($this->processPaymentResponse(order, $response)) {
            $redirect_url = $order->get_checkout_order_received_url();
            wp_redirect($redirect_url); 
            exit;
        }
        else {
            $message = isset($data['ErrorMSG']) ? $data['ErrorMSG'] :  __('Unexpected error occured while processing your request.', 'payfull');
        }
        
        if($type=='error') {
            $order->add_order_note('Payment failed. Response:<pre>' . print_r($data, 1).'</pre>');
        }

        wc_add_notice($message, $type);
        wp_redirect($redirect_url);        
    }
    
    protected function processPaymentResponse($order, $response)
    {
        if(isset($response['status']) && $response['status']) {
            $xid = $response['transaction_id'];
            if(empty($xid)) {
                print_r($response);
                exit;
            }
            $order->add_order_note("Payment Via Payfull, Transaction ID: {$xid}");
            $order->update_status('wc-processing', "Payment succeeded. Transaction ID: {$xid}");
            //$order->reduce_order_stock();
            $order->payment_complete($xid);
            WC()->cart->empty_cart();
            update_post_meta( $order->id, '_payfull_transaction_id', $xid );
            
            $message = __('Thank you for shopping with us. Your transaction is succeeded.', 'payfull');
            wc_add_notice($message);

            return true;
        } else {
            $error = isset($response['ErrorMSG']) ? $response['ErrorMSG'] : __('Processing payment failed.', 'payfull');
            //$order->update_status('wc-failed', $error);
            $order->add_order_note('Payment failed. Response:<pre>' . print_r($response, 1).'</pre>');
            wc_add_notice( $error, 'error' );
            return false;
        }
    }

        /**
     * @return boolyean|array true on success otherwise it resturns array of errors
     */
    protected function validatePaymentForm($form)
    {
        $errors = [];
        if(!isset($form['card']['holder']) || empty($form['card']['holder'])) {
            $errors[] = __('Holder name cannot be empty.', 'payfull');
        }
        if(!isset($form['card']['pan']) || empty($form['card']['pan'])) {
            $errors[] = __('Card number cannot be empty.', 'payfull');
        }
        if(!isset($form['card']['expiry']) || empty($form['card']['expiry'])) {
            $errors[] = __('Card expiry date cannot be empty.', 'payfull');
        }
        if(!isset($form['card']['cvc']) || empty($form['card']['cvc'])) {
            $errors[] = __('CVC date cannot be empty.', 'payfull');
        } else {
            $v = explode('/', $form['card']['expiry']);
            $m = isset($v[0]) ? intval($v[0]) : -1;
            $y = isset($v[1]) ? intval($v[1]) : -1;
            $y += ($y>0 && $y < 99) ? 2000 : 0;
            if($m<1 || $m > 12 || $y < date('Y')) {
                $errors[] = __('The expiry date is invalide or already expired', 'payfull');
            }
        }
        
        if(!isset($form['installment']) || intval($form['installment'])<1) {
            $errors[] = __('The installment value must be a positive integer.', 'payfull');
        }
        
        return count($errors) ? $errors : true;
    }
    
    protected  function renderView($_viewFile_,$_data_=null,$_return_=false)
    {
        if(is_array($_data_)) {
			extract($_data_,EXTR_PREFIX_SAME,'data');
        } else {
			$data=$_data_;
        }
		if($_return_) {
			ob_start();
			ob_implicit_flush(false);
			require($_viewFile_);
			return ob_get_clean();
		}
		else {
			require($_viewFile_);
        }
    }


}
