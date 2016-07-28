<?php

/* @vat $this the instnce of WC_Gateway_Payfull */
wp_enqueue_script( 'wc-credit-card-form' );
$IDS = [
    'bank'              => "{$id}-bank",
    'gateway'           => "{$id}-gateway",
    'cardset'           => "{$id}-cardset",
    'holder'            => "{$id}-card-holder",
    'pan'               => "{$id}-card-number",
    'expiry'            => "{$id}-card-expiry",
    'cvc'               => "{$id}-card-cvc",
    'use3d-label'       => "{$id}-use3d-label",
    'use3d'             => "{$id}-use3d",
    'pay-onetime'       => "{$id}-pay-onetime",
    'installment'       => "{$id}-installment",
    'pay-taksit'        => "{$id}-pay-taksit",
    'use3d-row'         => "{$id}-use3d-row",
    'taksit-table'      => "{$id}-taksit-table",
];
    
$LBLS = [
    'holder'        => __( 'Name on Card', 'woocommerce' ),
    'pan'           => __( 'Credit Card Number', 'woocommerce' ),
    'expiry'        => __( 'Expiry (MM/YY)', 'woocommerce' ),
    'cvc'           => __( 'Card Verification Number', 'woocommerce' ),
    'use3d'         => __( 'Use 3D secure Payments System', 'payfull' ),
    'pay-onetime'   => __("Pay one shot", "payfull"),
    'installment'   => __("installment", "payfull"),
    'pay-taksit'    => __("Pay with installment", "payfull"),
    'total'         => __("Total", "payfull"),
];

$VALS = [
    'bank'      => isset($form['bank']) ? $form['bank'] : '',
    'gateway'   => isset($form['gateway']) ? $form['gateway'] : '',
    'holder'    => isset($form['card']['holder']) ? $form['card']['holder'] : '',
    'pan'       => isset($form['card']['pan']) ? $form['card']['pan'] : '',
    'expiry'    => isset($form['card']['expiry']) ? $form['card']['expiry'] : '',
    'cvc'       => isset($form['card']['cvc']) ? $form['card']['cvc'] : '',
];

if($use_installments_table) {
    echo "<style>".$custom_css."</style>";
}

?>
<form method="post" class="col-md-6">
    <input id="<?php echo $IDS['bank']; ?>" type="hidden" name="bank" value="<?php echo $VALS['bank']; ?>" />
    <input id="<?php echo $IDS['gateway']; ?>" type="hidden" name="gateway" value="<?php echo $VALS['gateway']; ?>" />
    <div class="fieldset" id="<?php echo $IDS['cardset']; ?>">
        <?php //do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
        <p class="form-row form-row-wide">
            <label for="<?php echo $IDS['holder']; ?>"><?php echo $LBLS['holder']; ?> <span class="required">*</span></label>
            <input id="<?php echo $IDS['holder']; ?>" value="<?php echo $VALS['holder']; ?>" class="input-text wc-credit-card-form-card-holder" type="text" maxlength="20" autocomplete="off" placeholder="" name="card[holder]" />
        </p>

        <p class="form-row form-row-wide">
            <label for="<?php echo $IDS['pan']; ?>"><?php echo $LBLS['pan']; ?> <span class="required">*</span></label>
            <input id="<?php echo $IDS['pan']; ?>" value="<?php echo $VALS['pan']; ?>" class="input-text wc-credit-card-form-card-number input-cc-number-not-supported" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="card[pan]" />
        </p>

        <p class="form-row form-row-first">
            <label for="<?php echo $IDS['expiry']; ?>"><?php echo $LBLS['expiry']; ?> <span class="required">*</span></label>
            <input id="<?php echo $IDS['expiry']; ?>" value="<?php echo $VALS['expiry']; ?>" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="card[expiry]" />
        </p>

        <p class="form-row form-row-last">
            <label for="<?php echo $IDS['cvc']; ?>"><?php echo $LBLS['cvc']; ?> <span class="required">*</span></label>
            <input id="<?php echo $IDS['cvc']; ?>" value="<?php echo $VALS['cvc']; ?>" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="card[cvc]" />
        </p>

        <p class="form-row form-row-wide payfull-3dsecure" id="<?php echo $IDS['use3d-row'] ?>">
            <label for="<?php echo $IDS['use3d']; ?>">
                <input id="<?php echo $IDS['use3d']; ?>" class="input-checkbox payfull-options-use3d" type="checkbox" name="use3d" value="true" />
                <?php echo $LBLS['use3d']; ?>
            </label>
        </p>
        
        <p class="form-row installment">
            <?php if($use_installments_table) : ?>
                <input id="<?php echo  $IDS['installment']; ?>" type="hidden" name="installment" value="1"/>
                <div class="payfull-payment-options" id="payfull-payment-options">
                    <label class="onetime active" data-type="onetime">
                        <input type="radio" name="pay-onetime"id="<?php echo $IDS['pay-onetime']; ?>" checked="checked">
                        <?php echo $LBLS['pay-onetime']; ?>
                    </label>
                    
                    <label class="installment" data-type="installment">
                        <input type="radio" name="pay-onetime" id="<?php echo  $IDS['pay-taksit']; ?>">
                        <?php echo $LBLS['pay-taksit']; ?>
                    </label>
                </div>
                <div class="taksit-area">
                    <div class="taksit-table" id="<?php echo  $IDS['taksit-table']; ?>">
                        <div class="head tr">
                            <div class="td"><?php echo __('Installments', 'payfull'); ?></div>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <label for="<?php echo  $IDS['installment']; ?>"><?php echo $LBLS['installment']; ?></label>
                <select id="<?php echo  $IDS['installment']; ?>" name="installment">
                    <option value="1">1</option>
                </select>
            <?php endif; ?>
        </p>
        
        <?php //do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
        <div class="clear"></div>
    </div>
    <input type="submit" value="Pay" >
</form>

<?php
$this->renderView(__DIR__."/taksit.js.php", [
    'IDS'       => $IDS,
    'total'     => $order->get_total(),
    'currency'  => $order->get_order_currency(),
    'symbol'    => $symbol,
    'T'         => $LBLS,
]);

if($use_installments_table) {
    $this->renderView(__DIR__."/taksit-table.js.php", [
        'symbol'    => $symbol,
        'T'         => $LBLS,
    ]);
} else {
    $this->renderView(__DIR__."/taksit-droplist.js.php", [
        'symbol'    => $symbol,
        'T'         => $LBLS,
        'drop_id'   => $IDS['installment'],
    ]);
}
?>

<script type="text/javascript">
    (function ($) {
        payfull.run();
    })(jQuery);
</script>

<?php $this->renderView(__DIR__."/card-brand.js.php");?>
<?php $this->renderView(__DIR__."/card-brand.css.php");?>
