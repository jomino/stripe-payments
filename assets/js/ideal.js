(function(){

    var $bank_input = $('input[name="ideal-selection"]');
    var $msg_output = $('.help-text');

    var $container = $('.form-container');
    $container.removeClass('mid');
    $container.addClass('top');

    var stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);

    var elements = stripe.elements();
    
    var options = {
        style: {
            base: {
                fontSize: '14px',
                color: '#3B4856',
                padding: '5px 6px',
                fontSmoothing: 'antialiased',
                '::selection': {
                    color: '#006699',
                }
            }
        }
    };
    
    var idealBank = elements.create('idealBank', options);
    idealBank.mount('#ideal-bank-element');

    var bank = '';

    idealBank.on('change', function(event) {
        bank = event.value;
        $bank_input.val(bank);
        $msg_output.text('');
        $msg_output.addClass('hidden');
    });

    $('form').on('submit',function(e){
        if($bank_input.val()==''){
            e.preventDefault();
            $msg_output.text('Faite dérouler le menu iDeal et choisissez une banque.');
            $msg_output.removeClass('hidden');
            return false;
        }
    });

})();