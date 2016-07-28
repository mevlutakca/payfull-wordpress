<?php
require_once "PFApiException.php";

class PFApi
{
    public $ver = '1.0';
    /**
     * @var string the url to get the iframe from.
     */
    public $url;
    /**
     * @var string a small description about the payment.
     */
    public $title;
    /**
     * @var string the transaction id that the merchant uses internally for the payment.
     */
    public $external_id;
    /**
     * @var the url to post the final result. When PayFull finishes processing
     * the payment, it will return the resutl to this url (in e-commerce website)
     * where the response must processed.
     */
    public $return_url;
    /**
     * @var float the total amount of the payment.
     */
    public $amount = 0;
    /**
     * @var string currency code of the payment.
     */
    public $currency = 'TRY';
    /**
     * @var bool enable/disable installments
     */
    public $installments = false;
    /**
     * @var string the mode of payment: test or prod
     */
    public $mode = 'test';
    /**
     * @var array (optional) contains the items of the payement. Each entry must
     * have the a title, quantity and a price. The total price of the items (if
     * supplied) MUST be equal to the $amount.
     */
    public $items = [];
    /**
     * @var array the HTML attributes for the iframe tag.
     */
    public $frameOptions = ['width'=>'100%', 'height'=>'100%'];

    public function __construct($config=[])
    {
        foreach($config as $key=>$value) {
            if(!property_exists($this, $key)) {
                throw new PFApiException(strtr('Property "{class}.{property}" is not defined.', array(
                    '{class}' => get_class($this),
                    '{property}' => $key,
                )));
            }
            $this->$key = $value;
        }

        $this->autoload();
    }

    public function getPaymentForm()
    {
        $response = $this->post($this->url, [
            'ver'           => $this->ver,
            'amount'        => $this->amount,
            'currency'      => $this->currency,
            'return_url'    => $this->return_url,
            'external_id'   => $this->external_id,
            'items'         => $this->items,
            'title'         => $this->title,
            'frameOptions'  => $this->frameOptions,
        ]);
        // return $response;
        $response = json_decode($response, true);

        if(isset($response['status']) && $response['status']=='ok') {
            return $response['iframe'];
        } else {
            $error = isset($response['message']) ? $response['message'] : 'Unknown error';
            // $msg = strtr('Failed to get the payment frame: @error', ['@error'=>$error]);
            throw new PFApiException($error, $response);
        }
    }

    protected function autoload()
    {
        spl_autoload_register(function ($class) {
            // $path = __DIR__;//realpath(__DIR__.'/../types');
            // $filename = "$path/$class.php";
            // if(file_exists($filename)) {
            //     require_once $filename;
            // }
        });
    }

    protected static function post($url, $data=array())
    {
        // echo print_r($data, 1);
        // return;
        // return json_encode($data);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_ENCODING       => "",
            CURLOPT_USERAGENT      => "curl",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CUSTOMREQUEST  => "POST",
        );

        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $content  = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if($content === false) {
            $error = $error ? : 'Unknown error';
            $msg = strtr('Error occured during connecting to the server: @error', ['@error'=>$error]);
            throw new PFApiException($msg);
        }

        return $content;
    }


}

