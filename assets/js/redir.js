$(document).ready(function(){
    
    var redir_url = $('input[name="redir-url"]').val();
    var $loading_el = $('.signup-form');

    var defaultLoaderOptions = {
        background  : 'rgba(99, 115, 138, 0.5)'
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
            overlayLoader('text','Opération impossible ...');
        }
    },3000);

});