<?php

?>

<script type="text/javascript">
    (function ($) {
        $.extend(window.payfull, {

            showTakistTable: function (val) {
                val ? $('.taksit-area').show() : $('.taksit-area').hide();
            },

            refreshTakistPlans: function (banks) {
                this.payOneShot();
                $('.taksit-table .td.active').removeClass('active').addClass('disabled ');
                $('.taksit-table .td.selected').removeClass('selected');
                $('.taksit-table .td input[type="radio"]').prop('disabled',true).prop('checked',false);
                for (var i in banks) {
                    $('.taksit-table .td.' + banks[i].toLowerCase())
						.removeClass('disabled ').addClass('active')
						.find('input[type="radio"]').prop('disabled',false)
					;
                }
            },

            onTakistPlanClicked: function (element) {
                console.log(element);
                if ($(element).hasClass('disabled')) {
                    return false;
                }
                this.show3D($(element).data('has3d'));
                this.payWithTaksit($(element).data('count'), $(element).data('bank'), $(element).data('gateway'));

                $('.taksit-table .td.selected').removeClass('selected');
                $(element).addClass('selected');
            },

            onPayOneTime: function (element) {
                this.payOneShot();
                this.showTakistTable(false);

                console.log(element);
                $('.payfull-payment-options').find('label').removeClass('active');
                $(element).parent().addClass('active');
                
                $('.taksit-table .td.selected').removeClass('selected');
                $('.taksit-table .td input[type="radio"]').prop('checked',false);
            },

            onPayTakist: function (element) {
                this.showTakistTable(true);
                console.log(element);

                $('.payfull-payment-options').find('label').removeClass('active');
                $(element).parent().addClass('active');
            },
            
            onLoadBanks: function() {
                this.buildTable();
                $('.taksit-table .td.full').click(function () {
                    console.log('onTakistPlanClicked');
                    payfull.onTakistPlanClicked(this);
                });  
            },
            
            buildTable: function() {
                var b, opt, index = 1, empty = false, cells, found, rid, n, g, c, h, total, fee, ext, td, evod;
                var $table = $('.taksit-table');
                var $head = $('.taksit-table .head');
                for(var i in this.banks) {
                    b = this.banks[i];
                    $head.append('<div class="td"><img alt="'+b.bank+'" src="'+b.image+'" /></div>');
                }
                while(true) {
                    index++;
                    empty = true;
                    evod = index%2==0 ? "even" : "odd";
                    var $row = $('<div class="tr '+evod+'"></div>');
                    
                    for(var i in this.banks) {
                        b = this.banks[i];
                        for(var j in b.installments) {
                            opt = b.installments[j];
                            if(opt.count == index) {
                                empty = false;
                                break;
                            }
                        }                       
                    }
                    if (empty) { break; }
                    cells = [];
                    cells.push($('<div class="td">'+index+'</div>'));
                    for(var i in this.banks) {
                        b = this.banks[i];
                        n = b.bank; h = b.gateway; c =index;
                        h = b.has3d;
                        found = false;
                        for(var j in b.installments) {
                            opt = b.installments[j];
                            if( opt.count == index) {
                                found = true;
                                fee = parseInt(opt.commission.replace('%', ''));
                                rid = 'taksit-option-'+n+'-'+g+'-'+c;
                                total = Math.round((1 + fee/100) * this.total*100)/100;
                                ext = Math.round(this.total*100 / index) / 100;
                                td = ''
                                    + '<div class="td data disabled full '+n.toLocaleLowerCase()+'" data-count="'+c+'" data-bank="'+n+'" data-gateway="'+g+'" data-has3d="'+h+'">'
                                    + '<label for="'+rid+'">'
                                    + '<p><input type="radio" name="taksit-option" disabled id="'+rid+'" value="'+n+'||'+g+'||'+c+'"></p>'
                                    + fee+'%'
                                    + '<p>'+c+' x <?php echo $symbol;?> '+ext+'</p>'
                                    + '<p><?php echo $T['total'];?>: <strong><?php echo $symbol;?> '+total+'</strong></p>'
                                    + '</label>'
                                ;
                                cells.push($(td));
                                break;
                            }
                        }
                        if(!found) {
                            cells.push($('<div class="td data empty"></div>'));
                        }
                    }
                    console.log('('+index+'): '+cells.length);
                    $row.append(cells);
                    $table.append($row);
                }
            },

            init: function () {
                              
            }
        });

    })(jQuery);
</script>