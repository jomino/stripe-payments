$(document).ready(function(){
    
    var check_url = $('input[name="check-url"]').val();
    var $loading_el = $('.check-loader');
    var $hiden_el = $('#title-checked');
    var $text_el = $('#text-checked');

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
            $text_el.text(response);
            $hiden_el.toggleClass('hidden visible');
            overlayLoader('hide',{});
        }else{
            start();
        }
    };

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options);
        }
    };

    var start = function(){
        window.setTimeout(function(){
            if(check_url){
                checkUrl(check_url,onChecked);
            }
        },1000);
    };

    overlayLoader('show',defaultLoaderOptions);
    start();

});