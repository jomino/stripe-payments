(function(){
    !window.PUBLISHABLE_KEY_ERROR ?
        console.log('publishable key value: ' + window.STRIPE_PUBLISHABLE_KEY):
        console.log('publishable key error: ' + window.KEY_ERROR_VALUE);
})();