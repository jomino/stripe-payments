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
                callback(JSON.parse(response.text()));
            }
        }).catch(function(e){
            log(e);
        });
    };

    var onChecked = function(response){
        var status = response.status;
        overlayLoader('hide',{});
        log('STATUS:'+status);
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
    },1000);

    var log = function(content){
        console.log(content);
    };

});