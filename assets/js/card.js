(function(){

    var $container = $('.form-container');
    var $msg_output = $('.help-text');
    var $name = $('input[name="name"]');
    var $email = $('input[name="email"]');
    var $button = $('#card-button');
    var client_secret = $.data($button,'secret');
    var redir_url = $('input[name="redir-url"]').val();

    var stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
    var elements = stripe.elements();

    var redirect = function(url){
        window.location.href = url;
    };

    var setHelpMessage = function(msg=''){
        if (msg!='') {
            $msg_output.text(msg);
            $msg_output.removeClass('hidden');
        } else {
            $msg_output.text('');
            if(!$msg_output.hasClass('hidden')){ $msg_output.addClass('hidden'); }
        }
    };

    var cardElementsReport = function(e){
        if (e.error) {
            setHelpMessage(e.error.message);
        } else {
            setHelpMessage('');
        }
    };
    
    var options = {
        style: {
            base: {
                fontSize: '18px',
                color: '#3B4856',
                fontSmoothing: 'antialiased'
            },
            invalid: {
              color: '#FF3C26',
              ':focus': {
                color: '#FF3C26',
              },
            }
        }
    };

    $container.removeClass('mid');
    $container.addClass('top');

    $button.on( 'click', function(e) {
        stripe.handleCardPayment( client_secret, card_element, {
            payment_method_data: {
                billing_details: {
                    name: $name.val(),
                    email: $email.val()
                }
            },
            receipt_email: $email.val()
        }).then( function(result) {
            if (result.error) {
                setHelpMessage(result.error);
            } else {
                setHelpMessage('');
                redirect(redir_url);
            }
        });
    });
    
    var card_number_element = elements.create('cardNumber', options);
    card_number_element.mount('#card-number-element');
    card_number_element.on( 'change', cardElementsReport);
    
    var card_expiry_element = elements.create('cardExpiry', options);
    card_expiry_element.mount('#card-expiry-element');
    card_expiry_element.on( 'change', cardElementsReport);
    
    var card_cvc_element = elements.create('cardCvc', options);
    card_cvc_element.mount('#card-expiry-element');
    card_cvc_element.on( 'change', cardElementsReport);

})();