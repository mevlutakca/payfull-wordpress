<?php

class PFApiException extends \Exception
{
    /**
     * @var array the data to be carried with the exception
     */
    public $data = [];

    public function __construct($message, $codeOrData=[], $previous=null)
    {
        $code = isset($codeOrData) ? $codeOrData : 0;
        $this->data = [];
        if(is_array($codeOrData)) {
            $this->data = $codeOrData;
            $code = isset($codeOrData['code']) ? $codeOrData['code'] : 0;
        }

        parent::__construct($message, $code, $previous);
    }
}
