$(document).ready(function(){

    if(window.PUBLISHABLE_KEY_ERROR==true){
        $('#pay-carousel-bystep').hide();
        $('.alert').show();
        return;
    }

    var pkey = window.STRIPE_PUBLISHABLE_KEY;
    var amount = window.STRIPE_AMOUNT_VALUE;

    var carouselStepTo = function(step){
        $('.carousel').carousel(step);
    };

    $('form[name="pay-choice-form"]').on('submit',function(e){
        e.preventDefault();
        carouselStepTo(1);
    });

    $('a.step-btn').on('click',function(){
        e.preventDefault();
        var step = parseInt($(this).data('step'));
        if(step<2){ carouselStepTo(step); }
    });

});