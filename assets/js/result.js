$(document).ready(function(){
    
    var check_url = $('input[name="check-url"]').val();
    var $loading_el = $('.loader-container');
    var $hiden_el = $('.result-text');

    var defaultLoaderOptions = {
        background : false,
        minSize: false
    };

    var checkUrl = function(url,callback){
        window.fetch( url, {
            credentials: 'same-origin'
        }).then(function(response){
            if(response.ok) {
                response.json().then(function(obj){
                    callback(obj.status);
                });
            }
        });
    };

    var onChecked = function(response){
        if(response!=''){
            $hiden_el.toggleClass('hidden visible').text(response);
            overlayLoader('hide',{});
            $loading_el.remove();
        }else{
            start();
        }
    };

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options);
        }
    };

    var launch = function(){
        if(check_url){
            checkUrl(check_url,onChecked);
        }
    };

    var start = function(){
        window.setTimeout(launch,1000);
    };

    overlayLoader('show',defaultLoaderOptions);
    start();

});