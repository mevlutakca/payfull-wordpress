<?php
$visa_img_path = plugins_url( 'images/payfull_creditcard_visa.png', __FILE__ );
$master_img_path = plugins_url( 'images/payfull_creditcard_master.png', __FILE__ );
$not_supported_img_path = plugins_url( 'images/payfull_creditcard_not_supported.png', __FILE__ );
?>

<style type="text/css">

.input-cc-number-visa {
    background: rgba(0, 0, 0, 0) url("<?php echo $visa_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
}

.input-cc-number-master {
    background: rgba(0, 0, 0, 0) url("<?php echo $master_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
}

.input-cc-number-not-supported {
    background: rgba(0, 0, 0, 0) url("<?php echo $not_supported_img_path; ?>") no-repeat scroll right center / 4% auto;
    float: left;
}

</style>