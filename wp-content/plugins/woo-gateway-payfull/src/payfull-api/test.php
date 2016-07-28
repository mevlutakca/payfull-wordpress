<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require_once "PFApi.php";
require_once "PFApiException.php";

$data = [
    'url'           => 'http://192.168.33.10/linux/t4u/drupal/iframe/api',
    'title'         => 'A small title for testing',
    'amount'        => 600.00,
    'currency'      => 'TRY',
    // 'return_url'     => 'http://armine.payfull.com/',
    'return_url'     => 'http://192.168.33.10/linux/t4u/codebase/t4u_iframe/api/return.php',
    'items'         => [
        ['title'=> 'Invoice item 1', 'q'=> 1, 'price'=>100],
        ['title'=> 'Invoice item 2', 'q'=> 1, 'price'=>200],
        ['title'=> 'Invoice item 3', 'q'=> 1, 'price'=>300],
    ],
];
$api = new PFApi($data);

?>
<h1>Hi ! I am merchant</h1>
<div style="padding:20px;margin:20px; border:2px solid #D0D0E7;min-height:30px;">
    <?php
    try{
        echo $api->getPaymentForm();
    }
    catch(PFApiException $xp) {
        echo "<h3>".$xp->getMessage()."</h3>";
        echo "<pre>";
        print_r($xp->data);
        echo "</pre>";
    }
    ?>
</div>
