$(document).ready(function(){
    
    var redir_url = $('input[name="redir-url"]').val();
    var $loading_el = $('.loader-container');

    var defaultLoaderOptions = {
        background  : 'rgba(255, 255, 255, 0)'
    };

    var redirectTo = function(url){
        window.location.href = url;
    };

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options);
        }
    };

    overlayLoader('show',defaultLoaderOptions);

    window.setTimeout(function(){
        if(redir_url){
            redirectTo(redir_url);
        }else{
            overlayLoader('text','...');
        }
    },3000);

});