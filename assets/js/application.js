$(document).ready(function(){
    
    var $main_el = $('.container.main');
    var $loading_el = $('.container.loader');

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options || {});
        }
    };

    if($loading_el.length){
        window.setTimeout(function(){
            overlayLoader('hide');
            $loading_el.remove();
            $main_el.show();
        },3000);
        overlayLoader('show');
    }else{
        $main_el.show();
    }

});