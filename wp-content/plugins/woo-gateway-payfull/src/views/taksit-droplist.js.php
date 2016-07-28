<?php
/* @var string $drop_id the HTML id of the drop list*/
?>
<script type="text/javascript">
    (function ($) {
        $.extend(window.payfull, {

            refreshTakistPlans: function (banks) {
                var bankName = banks[0];
                this.payOneShot();

                var $e = $('#<?php echo $drop_id;?>');
                $e.empty();
                $e.data('bank', '');
                $e.data('has3d', true);
                $e.data('gateway', '');
                $e.append('<option selected="selected" value="1">1</option>');
                
                for (var i in this.banks) {
                    var bank = this.banks[i];
                    if (bank.bank == bankName) {
                        var opt, t, fee;
                        $e.data('bank', bank.bank);
                        $e.data('has3d', bank.has3d);
                        $e.data('gateway', bank.gateway);
                        console.log(bank.installments);
                        for (var j in bank.installments) {
                            opt = bank.installments[j];
                            
                            fee = parseFloat(opt.commission);
                            t = Math.round(this.total * (1+fee)*100)/100;
                            $e.append('<option value="' + opt.count + '">' + opt.count + ' - '+t+' '+this.currency+'</option>');
                        }
                        break;
                    }
                }
            },

            /* element: jquery select element*/
            onTakistListChanged: function (element) {
                this.show3D(element.data('has3d'));
                if(element.val()==1) {
                    this.payOneShot();
                } else {
                    this.payWithTaksit(element.val(), element.data('bank'), element.data('gateway'));
                }
            },

            init: function () {
                $('#<?php echo $drop_id;?>').change(function(){
                    payfull.onTakistListChanged($(this));
                });
            }
        });

    })(jQuery);
</script>
