(function(){

    var $container = $('.form-container');
    var $msg_output = $('.help-text');
    var $name = $('input[name="name"]');
    var $email = $('input[name="email"]');
    var $button = $('#card-button');
    var client_secret = $button.attr('data-secret');
    var redir_url = $('input[name="redir-url"]').val();

    var stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
    var elements = stripe.elements();

    var redirect = function(url){
        window.location.href = url;
    };

    var setHelpMessage = function(msg=''){
        if (msg!='') {
            $msg_output.html(msg);
            $msg_output.removeClass('hidden');
        } else {
            $msg_output.html('');
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
        var _name = $name.val()!='', _email = ($email.val()).match(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/);
        if(_name && _email){
            $(this).attr('disabled','disabled');
            stripe.handleCardPayment(
                client_secret,
                card_number_element, {
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
                    $(this).removeAttr('disabled');
                } else {
                    setHelpMessage('');
                    redirect(redir_url);
                }
            });
        }else{
            var message = [];
            if(!_name){ message.push('Vous avez oublié votre nom !? '); }
            if(!_email){ message.push('Vous avez oublié votre e-mail !? '); }
            setHelpMessage(message.join('<br>'));
        }
    });
    
    var card_number_element = elements.create('cardNumber', options);
    card_number_element.mount('#card-number-element');
    card_number_element.on( 'change', cardElementsReport);
    
    var card_expiry_element = elements.create('cardExpiry', options);
    card_expiry_element.mount('#card-expiry-element');
    card_expiry_element.on( 'change', cardElementsReport);
    
    var card_cvc_element = elements.create('cardCvc', options);
    card_cvc_element.mount('#card-cvc-element');
    card_cvc_element.on( 'change', cardElementsReport);

})();