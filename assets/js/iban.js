(function(){

    var $src_input = $('input[name="iban-selection"]');
    var $msg_output = $('.help-text');
    var $form = $('form');

    var $container = $('.form-container');
    $container.removeClass('mid');
    $container.addClass('top');

    var stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
    var elements = stripe.elements();

    var setHelpMessage = function(msg=''){
        if (msg!='') {
            $msg_output.text(msg);
            $msg_output.removeClass('hidden');
        } else {
            $msg_output.text('');
            $msg_output.addClass('hidden');
        }
    };
    
    var options = {
        style: {
            base: {
                fontSize: '14px',
                color: '#3B4856',
                padding: '5px 6px',
                fontSmoothing: 'antialiased'
            }
        },
        supportedCountries: ['SEPA'],
        placeholderCountry: 'BE'
    };
    
    var iBan = elements.create('iban', options);
    iBan.mount('#iban-element');

    iBan.on('change', function(event) {
        if (event.error) {
            setHelpMessage(event.error.message);
        } else {
            setHelpMessage('');
        }
    });

    var sourced;

    var stripeSourceHandler = function(source){
        sourced = source.id;
        $src_input.val(sourced);
        $form.submit();
    };

    $form.on( 'submit', function(e){
        if(!sourced){
            e.preventDefault();
                    
            var sourceData = {
                type: 'sepa_debit',
                currency: 'eur',
                owner: {
                    name: $('input[name="name"]').val(),
                    email: $('input[name="email"]').val(),
                },
                mandate: {
                    notification_method: 'email',
                }
            };

            stripe.createSource(iBan, sourceData).then(function(result) {
                if (result.error) {
                    setHelpMessage(event.error.message);
                } else {
                    setHelpMessage('');
                    stripeSourceHandler(result.source);
                }
            });

        }
        return true;
    });

})();