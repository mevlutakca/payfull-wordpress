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
    public $enable_3dSecure = 1;
    public $enable_installment = 1;
    public $enable_extra_installment = 0;
    public $enable_bkm = 0;
    public $currency_class;
    public $total_selector;
    /**
     * @var array the HTML attributes to resner the iframe
     */
    public $options = [];

    public function __construct($register_hooks=false)
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
        $this->currency_class = $this->get_option('currency_class');
        $this->total_selector = $this->get_option('total_selector');
        $this->enable_3dSecure = $this->get_option('enable_3dSecure');
        $this->enable_installment = $this->get_option('enable_installment');
        $this->enable_extra_installment = $this->get_option('enable_extra_installment');
        $this->enable_bkm = $this->get_option('enable_bkm');

        if($register_hooks) {
            //$this->initApiService();
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_'.strtolower(__CLASS__), array( &$this, 'check_payment_response' ) );
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
                $result = $this->payfull()->banks($data);
                break;
            case 'extra_ins':
                $result = $this->payfull()->extraInstallments($data);
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
                //'description' => __('Enable/Disable "Payfull" checkout.', 'payfull'),
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
            'enable_3dSecure' => array(
                'title' => __('Enable 3D secure', 'payfull'),
                'type' => 'select',
                'options'     => array(__( 'No', 'payfull' ),__( 'Yes', 'payfull' )),
                'description' => __('Choose whether to enable 3D secure payament option.', 'payfull'),
            ),
            'enable_installment' => array(
                'title' => __('Enable Installment', 'payfull'),
                'type' => 'select',
                'options'     => array(__( 'No', 'payfull' ),__( 'Yes', 'payfull' )),
                'description' => __('Choose whether to enable installment option.', 'payfull'),
            ),
            'enable_extra_installment' => array(
                'title' => __('Enable Extra Installment', 'payfull'),
                'type' => 'select',
                'options'     => array(__( 'No', 'payfull' ),__( 'Yes', 'payfull' )),
                'description' => __('Choose whether to enable extra installment option.', 'payfull'),
            ),
            'enable_bkm' => array(
                'title' => __('Enable BKM EXpress', 'payfull'),
                'type' => 'select',
                'options'     => array(__( 'No', 'payfull' ),__( 'Yes', 'payfull' )),
                'description' => __('Choose whether to enable BKM Express gateway.', 'payfull'),
            ),
            'total_selector' => array(
                'title' => __('Total Selector', 'payfull'),
                'type' => 'text',
                'default' => '.order_details .amount',
                'description' => __('A jQuery selector of the HTML element that contains the total amount in checkout page.', 'payfull'),
            ),
            'currency_class' => array(
                'title' => __('Currency Class', 'payfull'),
                'type' => 'text',
                'default' => 'woocommerce-Price-currencySymbol',
                'description' => __('The CSS class(es) to be applied to the curreny on checkout page', 'payfull'),
            ),
            'custom_css' => [
                'title' => __('Custom Css', 'payfull'),
                'type' => 'textarea',
                'default' => file_get_contents (WP_PLUGIN_DIR. '/woo-gateway-payfull/assets/custom.css'),
                // 'description' => __('Customiz the installments table.', 'payfull'),
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
            $error = $this->getErrorMessage($response,"Unknown error occured");
            $order->add_order_note("Refunding {$crcy} {$amount} failed. ".$error);
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
            'total_selector' => $this->get_option('total_selector'),
            'currency_class' => $this->get_option('currency_class'),
            'currency_symbol' => get_woocommerce_currency_symbol($order->get_order_currency()),
            'custom_css' => $this->get_option('custom_css'),
            'enable_3dSecure' => intval($this->enable_3dSecure) === 1,
            'enable_installment' => intval($this->enable_installment)===1,
            'enable_extra_installment' => intval($this->enable_extra_installment)===1,
            'enable_bkm' => intval($this->enable_bkm)===1,
        ]);
        do_action( 'woocommerce_credit_card_form_end', $this->id );
    }
    
    protected function sendPayment($order, $data)
    {
        $use3d               = 0;
        $installments        = 1;
        $card                = isset($data['card']) ? $data['card'] : null;
        $extraInsCampaignId  = isset($data['campaign_id']) ? $data['campaign_id'] : null;

        if($this->enable_3dSecure && isset($data['use3d'])) {
            $use3d = ($data['use3d']=="true");
        }

        if($this->enable_installment && isset($data['installment'])) {
            $installments = intval($data['installment']);
            $installments = $installments <=0 ? 1 : $installments;
        }
        
        $fname = $order->billing_first_name;
        $lname = $order->billing_last_name;
        $order->update_status('wc-pending', 'Process payment by Payfull');

        $total = $order->get_total();

        
        $request = [
            'total'                 => $order->get_total(),
            'currency'              => $order->get_order_currency(),
            'installments'          => $installments,
            'passive_data'          => $order->id,//json_encode(['order-id' => $order->id]),
            'cc_name'               => $card['holder'],
            'cc_number'             => str_replace(' ', '', $card['pan']),
            'cc_month'              => $card['month'],
            'cc_year'               => $card['year'],
            'cc_cvc'                => $card['cvc'],
            'customer_firstname'    => $fname,
            'customer_lastname'     => $lname,
            'customer_email'        => $order->billing_email,
            'customer_phone'        => $order->billing_phone,
            'payment_title'         => "{$fname} {$lname} | order $order->id | ".$order->get_total().$order->get_order_currency(),
        ];

        $bank_id = isset($data['bank']) ? $data['bank'] : null;
        $gateway = isset($data['gateway']) ? $data['gateway'] : null;

        if(!isset($gateway, $bank_id) AND $installments > 1) {
            wc_add_notice( __('Invalid installment information.', 'payfull'), 'error' );
            return;
        }

        $fee = $this->payfull()->getCommission($total, $bank_id, $installments);
        WC()->session->set( 'installment_fee',    $fee );

        if($bank_id != '')              $request['bank_id']     = $bank_id;
        if($gateway != '')              $request['gateway']     = $gateway;
        if(isset($extraInsCampaignId))  $request['campaign_id'] = $extraInsCampaignId;

        if($use3d) {
            $checkout_url = $order->get_checkout_payment_url(true);
            $return_url = add_query_arg(['order-id'=>$order->id, 'wc-api'=>'WC_Gateway_Payfull'], $checkout_url);

            $request['use3d'] = 1;
            $request['return_url'] = $return_url;
        }

        $data["useBKM"] = isset($data["useBKM"])?$data["useBKM"]:0;
        if($data["useBKM"]){
            unset($request['cc_name']);
            unset($request['cc_number']);
            unset($request['cc_month']);
            unset($request['cc_year']);
            unset($request['cc_cvc']);
            $request['installments'] = $this->enable_installment;
            $request['bank_id']      = 'BKMExpress';
            $checkout_url            = $order->get_checkout_payment_url(true);
            $return_url              = add_query_arg(['order-id'=>$order->id, 'wc-api'=>'WC_Gateway_Payfull'], $checkout_url);
            $request['return_url']   = $return_url;
        }

        $return_json = !($use3d OR $data["useBKM"]);
        
        $response = $this->payfull()->send('Sale', $request, $return_json);

        if($use3d or $data["useBKM"]) {
            if(strpos($response, '<html')===false AND json_decode($response) == null) {
                $error = $this->getErrorMessage($response,__('Invalid response received.', 'payfull'));
                //$order->update_status('wc-failed', $error);
                wc_add_notice( $error, 'error' );
                $order->add_order_note('Could not complete the transaction.' . $error);
                return;
            }elseif(strpos($response, '<html')!==false){
                echo $response;
                exit;
            }
        }

        $response = (json_decode($response) == null)?$response:(array)json_decode($response);
        if($this->processPaymentResponse($order, $response)) {
            $message = __('Thank you for shopping with us. Your transaction is succeeded.', 'payfull');
            wc_add_notice($message);
            $thank_url = $order->get_checkout_order_received_url();
            wp_redirect($thank_url);
            exit;
        }
    }

    public function check_payment_response()
    {
        global $woocommerce;
        
        if ( ! defined( 'ABSPATH' ) ) {
            throw new \Exception('Wordpress is not running.');
        }
        if(!defined('WOOCOMMERCE_VERSION')) {
            throw new \Exception('WooCommerce is not running.');
        }
        
        $type       = "error";
        $title      = __('Bad request', 'payfull');
        $data       = $_POST;
        
        array_walk_recursive($data, function(&$item) {
            $item  = sanitize_text_field($item);
        });

        $tx             = isset($data['transaction_id']) ? $data['transaction_id'] : false;
        $order_id       = isset($data['passive_data']) ? $data['passive_data'] : (isset($_GET['order-id']) ? $_GET['order-id'] : null);
        $order          = wc_get_order($order_id);
        $hash           = $this->generateHash($data);
        $redirect_url   = $woocommerce->cart->get_checkout_url();
        
        if(!isset($order)) {
            $message = __('Order not found.', 'payfull');
            if($tx) {
                $message = printf(__('The payment is done but your order not found. Your transaction id is "%1$s"', 'payfull'), $tx);
            }
        }
        // else if($order->status != 'wc-pending' || $order->status=='completed') {
        //     $message = __('Invalid status for the requested order'. ' '.$order_id.'.', 'payfull');
        // }
        else if($hash != $data['hash']) {
             $message = __('Invalid hash code', 'payfull').' '.$order_id;
        }
        else {
            if($this->processPaymentResponse($order, $data)) {
                $message = __('Thank you for shopping with us. Your transaction is succeeded.', 'payfull');
                wc_add_notice($message);
                $redirect_url = $order->get_checkout_order_received_url();
                wp_redirect($redirect_url); 
                exit;
            }
            else {
                $order->update_status('wc-failed', '3D Payment failed');
                $message = $this->getErrorMessage($data,__('Unexpected error occurred while processing your request.', 'payfull'));
                $order->add_order_note($message);
            }
        }
        // error happened:
        wc_add_notice($message, 'error');
        wp_redirect($redirect_url);
    }

    protected function generateHash($params){
        $arr = [];
        unset($params['hash']);
        foreach($params as $param_key=>$param_val){$arr[strtolower($param_key)]=$param_val;}
        ksort($arr);
        $hashString_char_count = "";
        foreach ($arr as $key=>$val) {
            $hashString_char_count .= mb_strlen($val) . $val;
        }
        $hashString_char_count      = strtolower(hash_hmac("sha1", $hashString_char_count, $this->password));
        return $hashString_char_count;
    }

    protected function processPaymentResponse($order, $response)
    {
        if(isset($response['status']) && $response['status']) {
            $xid = $response['transaction_id'];
            if(empty($xid)) {
                $order->add_order_note("Invalid response: Transaction id is missing.");
                return false;
            }
            $order->add_order_note("Payment Via Payfull, Transaction ID: {$xid}");

            $installments      = isset($response['installments'])?$response['installments']:1;
            $extraInstallments = isset($response['extra_installments'])?$response['extra_installments']:'';

            $this->saveOrderCommission($order, WC()->session->get('installment_fee'), $installments, $extraInstallments);
            unset(WC()->session->installment_fee); // there is no need any more

            $order->update_status('wc-processing', "Payment succeeded. Transaction ID: {$xid}");
            //$order->reduce_order_stock();
            $order->payment_complete($xid);
            WC()->cart->empty_cart();
            update_post_meta( $order->id, '_payfull_transaction_id', $xid );
            return true;
        } else {
            return false;
        }
    }

    protected function getErrorMessage($response, $default)
    {
        if(isset($response['ErrorMSG']) && strlen($response['ErrorMSG']))
            return $response['ErrorMSG'];
        return $default;
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
        }elseif(!$this->checkCCNumber($form['card']['pan'])){
            $errors[] = __('Please enter a valid credit card number.', 'payfull');
        }

        if(!isset($form['card']['year']) || empty($form['card']['year'])) {
            $errors[] = __('Card expiration year cannot be empty.', 'payfull');
        } else {
            $y = intval($form['card']['year']);
            $y += ($y>0 && $y < 99) ? 2000 : 0;
            if($y < date('Y')) {
                $errors[] = __('The expiration year is invalid', 'payfull');
            }
        }

        if(!isset($form['card']['month']) || empty($form['card']['month'])) {
            $errors[] = __('Card expiration month cannot be empty.', 'payfull');
        }else {
            $m = intval($form['card']['month']);
            if($m<1 || $m > 12) {
                $errors[] = __('The expiration month is invalid: '.var_export($form['card']['month'], 1), 'payfull');
            }
        }
        if(!$this->checkCCEXPDate($form['card']['month'], $form['card']['year'])){
            $errors[] = __('The expiration month is invalid: '.var_export($form['card']['month'], 1), 'payfull');
        }

        if(!isset($form['card']['cvc']) || empty($form['card']['cvc'])) {
            $errors[] = __('Card CVC cannot be empty.', 'payfull');
        }elseif(isset($form['card']['pan']) AND !$this->checkCCCVC($form['card']['pan'], $form['card']['cvc'])){
            $errors[] = __('Please enter a valid credit card verification number.', 'payfull');
        }
        
        if($this->enable_installment && (!isset($form['installment']) || intval($form['installment'])<1)) {
            $errors[] = __('The installment value must be a positive integer.', 'payfull');
        }

        if(!$this->enable_bkm AND isset($form['useBKM']) AND $form['useBKM']) {
            $errors[] = __('BKM Express is inactive.', 'payfull');
        }

        if($this->enable_bkm AND isset($form['useBKM']) AND $form['useBKM']) {
            $errors = [];
        }

        
        return count($errors) ? $errors : true;
    }

    protected function saveOrderCommission($order, $amount, $installments, $extraInstallments)
    {
        if($extraInstallments != '' AND $extraInstallments != 0){
            $installments .= ' +'.$extraInstallments;
            $installments  = __('Installment Commission'.' ('.$installments.')', 'payfull');
        }
        if($installments == 1){
            $oneShotCommission = $this->payfull()->oneShotCommission();
            $total             = $order->get_total();
            $amount            = ($total*$oneShotCommission/100);
            $installments      = __('Commission', 'payfull');
        }

        $fee            = new stdClass();
        $fee->tax       = 0;
        $fee->amount    = $amount;
        $fee->taxable   = false;
        $fee->name      = $installments;
        $order->add_fee($fee);
        $order->calculate_totals();
    }

    protected function checkCCEXPDate($month, $year){
        if(strtotime('01-'.$month.'-'.$year) <= time()){
            return false;
        }
        return true;
    }

    protected function checkCCNumber($cardNumber){
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $len = strlen($cardNumber);
        if ($len < 15 || $len > 16) {
            return false;
        }else {
            switch($cardNumber) {
                case(preg_match ('/^4/', $cardNumber) >= 1):
                    return true;
                    break;
                case(preg_match ('/^5[1-5]/', $cardNumber) >= 1):
                    return true;
                    break;
                default:
                    return false;
                    break;
            }
        }
    }

    protected function checkCCCVC($cardNumber, $cvc){
        // Get the first number of the credit card so we know how many digits to look for
        $firstnumber = (int) substr($cardNumber, 0, 1);
        if ($firstnumber === 3){
            if (!preg_match("/^\d{4}$/", $cvc)){
                // The credit card is an American Express card but does not have a four digit CVV code
                return false;
            }
        }
        else if (!preg_match("/^\d{3}$/", $cvc)){
            // The credit card is a Visa, MasterCard, or Discover Card card but does not have a three digit CVV code
            return false;
        }
        return true;
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
