<?php
/**
 * Description of PayfullService
 *
 * @author houmam
 */
class PayfullService {
    public $username;
    public $password;
    public $endpoint;
    public $language;
    public $client_ip;


    public function __construct($config=[]) {
        
        if (!empty($config)) {
            foreach ($config as $name => $value) {
                if(!property_exists($this, $name)) {
                    throw new Exception(strtr('Property "{class}.{property}" is not defined.', array(
                        '{class}' => get_class($this),
                        '{property}' => $name,
                    )));
                }
                $this->$name = $value;
            }
        }
    }
    
    public function bin($bin)
    {
        return $this->send('Get', [
            'get_param' => 'Issuer',
            'bin' => $bin
        ]);
    }
    
    public function banks()
    {
        return $this->send('Get', [
            'get_param' => 'Installments',
        ]);
    }
    
    public function refund($transaction_id, $amount)
    {
        return $this->send('Return', [
            'transaction_id' => $transaction_id,
            'total' => $amount
        ]);
    }
    
    public function send($op, $data, $return_json=true)
    {
        if(empty($this->client_ip)) {
            $this->client_ip = $_SERVER['REMOTE_ADDR'] ;
        }
        $data['type'] = $op;
        $data['merchant'] = $this->username;
        $data['language'] = $this->language;
        $data['client_ip'] = $this->client_ip;
        //return $data;
        $data['hash'] = $this->hash($data);
//        print_r($data);
//        exit;
        $content = self::post($this->endpoint, $data);
        
        if($return_json){
            return json_decode($content, true);
        }
        return $content;
    }
    
    private function hash($data) 
    {
        $message = '';
        ksort($data);
        foreach($data as $key=>$value) {
            $message .= strlen($value).$value;
        }
        $hash = hash_hmac('sha1', $message, $this->password);
        
        return $hash;
    }
    
    public static function post($url, $data=array())
    {
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
            throw new Exception(strtr('Error occured in sending data to ASSECO: {error}', array(
                '{error}' => $error,
            )));
        }

        return $content;
    }
}
