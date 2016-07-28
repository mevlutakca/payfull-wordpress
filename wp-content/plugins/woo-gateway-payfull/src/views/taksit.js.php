<?php
/* @var string $id the of the plugin which used to prefix the HTML elements id's*/
?>

<script type="text/javascript">
    (function ($) {
        window.payfull = {
            bin: false,
            payOneTime: true,
            banks: [],
            total: <?php echo $total;?>,
            currency: "<?php echo $currency;?>",
            
            loadBanks: function() {
                $.ajax({
                    url: "index.php?payfull-api=v1",
                    method: "POST",
                    data: { command:"banks" },
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        payfull.banks = response.data;
                        if(payfull.onLoadBanks) {
                            payfull.onLoadBanks();
                        }
                    }
                });
            },

            onCardChanged: function (element) {
                var bin = $(element).val().replace(/\s/g, '').substr(0, 6);
                if (bin.length < 6) {
                    return;
                }
                if (bin == this.bin) { return; }
                this.bin = bin;
                payfull.refreshTakistPlans();
                var url = "index.php?payfull-api=v1";
                $.ajax({
                    url: url,
                    method: "POST",
                    data: { command:"bin", bin: bin },
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        var bank = response.data.bank_id;
                        if (bank && bank.length && payfull.refreshTakistPlans) {
                            payfull.refreshTakistPlans([bank]);
                        }
                    }
                });
            },

            show3D: function (val) {
                val ? $('#<?php echo $IDS['use3d-row']; ?>').show() : $('#<?php echo $IDS['use3d-row'] ?>').hide();
                if (!val) {
                    $('#<?php echo $IDS['use3d-row'] ?> input[type="checkbox"]').prop("checked", false);
                    $('#<?php echo $IDS['use3d-row'] ?> label').removeClass("checked");
                }
            },

            payWithTaksit: function (count, bank, gateway) {
                console.log("Takist Plan: " + bank + "/" + gateway + ": " + count);
                this.payOneTime = false;
                $('#<?php echo $IDS['installment'] ?>').val(count);
                $('#<?php echo $IDS['bank'] ?>').val(bank);
                $('#<?php echo $IDS['gateway'] ?>').val(gateway);
            },

            payOneShot: function () {
                this.payOneTime = true;
                this.show3D(true);
                this.payWithTaksit(1, '', '');
            },

            run: function () {
                this.loadBanks();
                
                $('#<?php echo $IDS['pan'] ?>').keyup(function () {
                    payfull.onCardChanged(this);
                });

                $('#<?php echo $IDS['use3d-row'] ?> input[type="checkbox"]').change(function () {
                    $(this).is(':checked') ? $('#<?php echo $IDS['use3d-row'] ?> label').addClass("checked") : $('#<?php echo $IDS['use3d-row'] ?> label').removeClass("checked");
                });
                
                $('#<?php echo $IDS['pay-onetime']; ?>').change(function(){
                    if($(this).prop('checked') && payfull.onPayOneTime) {
                        payfull.onPayOneTime(this);
                    }
                });
                
                $('#<?php echo $IDS['pay-taksit']; ?>').change(function(){
                    if($(this).prop('checked') && payfull.onPayTakist) {
                        payfull.onPayTakist(this);
                    }
                });

                if (this.init) {
                    this.init();
                }
            }
        };

    })(jQuery);
</script>