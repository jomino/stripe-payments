(function(){

    var $bank_input = $('input[name="ideal-selection"]');

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

    idealBank.on('change', function(event) {
        var bank = event.value;
        $bank_input.val(bank);
    });

})();