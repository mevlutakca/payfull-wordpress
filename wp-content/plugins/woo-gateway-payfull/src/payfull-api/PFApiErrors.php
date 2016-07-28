<?php

class PFApiErrors
{
    /**
     * Unknown error.
     */
    const UNKNOW_ERROR              = 0;
    const UNRECOGNIZED_HOST         = 100;

    const INVALID_REQUEST           = 200;
    const MISSING_EXTERNAL_ID       = 201;
    const UNAUTHORIZED              = 202;
    const INVALID_TOKEN             = 203;
    const MISMATCH_TOKEN            = 204;
    const INVALID_STATUS            = 205;
    const INVALIDE_INVOICE          = 206;
    const INVALIDE_INVOICE_AMOUNT   = 207;

    const PAYMENT_FAILED            = 300;

}
