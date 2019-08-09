$(document).ready(function(){
    
    var check_url = $('input[name="check-url"]').val();
    var $loading_el = $('.check-loader');
    var $hiden_el = $('#title-checked');
    var $text_el = $('#text-checked');

    var defaultLoaderOptions = {
        background  : 'rgba(99, 115, 138, 0.5)'
    };

    var checkUrl = function(url,callback){
        window.fetch( url, {
            credentials: 'same-origin'
        }).then(function(response){
            if(response.ok) {
                response.json().then(function(obj){
                    callback(obj.status);
                }).catch(function(e){
                    log(e);
                });
            }
        }).catch(function(e){
            log(e);
        });
    };

    var onChecked = function(response){
        overlayLoader('hide',{});
        log('STATUS:'+response);
    };

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options);
        }
    };

    overlayLoader('show',defaultLoaderOptions);

    window.setTimeout(function(){
        if(check_url){
            checkUrl(check_url,onChecked);
        }
    },3000);

    var log = function(content){
        console.log(content);
    };

});