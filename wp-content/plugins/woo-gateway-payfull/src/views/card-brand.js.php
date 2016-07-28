<script type="text/javascript">
    (function ($) {

        cardNumberFiledSelector = $("input[name='card[pan]']");
        cardNumberFiledSelector.keyup(function(){
            var number = $(this).val();
            cardBrandDetector(number);
        });

        function cardBrandDetector(number) {
            cardNumberFiledSelector.removeClass('input-cc-number-not-supported');
            var re_visa = new RegExp("^4");
            var re_master = new RegExp("^5[1-5]");

            if (number.match(re_visa) != null){
                cardNumberFiledSelector.addClass('input-cc-number-visa');
                cardNumberFiledSelector.removeClass('input-cc-number-master');
            }else if (number.match(re_master) != null){
                cardNumberFiledSelector.removeClass('input-cc-number-visa');
                cardNumberFiledSelector.addClass('input-cc-number-master');
            }else{
                cardNumberFiledSelector.removeClass('input-cc-number-visa');
                cardNumberFiledSelector.removeClass('input-cc-number-master');
                cardNumberFiledSelector.addClass('input-cc-number-not-supported');
            }
        }

    })(jQuery);

</script>
