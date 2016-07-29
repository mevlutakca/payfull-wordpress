<?php
$visa_img_path = plugins_url( 'images/visa.png', __FILE__ );
$master_img_path = plugins_url( 'images/master.png', __FILE__ );
$not_supported_img_path = plugins_url( 'images/unknown.png', __FILE__ );

?>
<style type="text/css">

.input-cc-number-visa {
    background: rgba(0, 0, 0, 0) url("<?php echo $visa_img_path; ?>") no-repeat scroll right center / 8% auto !important;
    float: left;
}

.input-cc-number-master {
    background: rgba(0, 0, 0, 0) url("<?php echo $master_img_path; ?>") no-repeat scroll right center / 7% auto !important;
    float: left;
}

.input-cc-number-not-supported {
    background: rgba(0, 0, 0, 0) url("<?php echo $not_supported_img_path; ?>") no-repeat scroll right center / 4% auto !important;
    float: left;
}

</style>