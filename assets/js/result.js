$(document).ready(function(){

    var max_retry = 3;
    
    var check_url = $('input[name="check-url"]').val();
    var $loading_el = $('.loader-container');
    var $hiden_el = $('.result-text');
    var $print_btn = $('.btn-print');
    var $print_container = $('.print-container');

    var defaultLoaderOptions = {
        background : false,
        minSize: false
    };

    var setMessage = function(message){
        $hiden_el.toggleClass('hidden visible').text(message);
        overlayLoader('hide');
        $loading_el.toggleClass('hidden');
    };

    var getUrl = function(url,callback){
        window.fetch( url, {
            credentials: 'same-origin'
        }).then(function(response){
            if(response.ok) {
                response.json().then(function(o){
                    callback(o);
                });
            }
        });
    };

    var onPrintFinished = function(){
        $print_container.html('');
    };

    var onPrintLoaded = function(response){
        var html = window.atob(response.html);
        $print_container.html(html);
        if(printJS){
            printJS({
                printable: 'print-container',
                type: 'html',
                fallbackPrintable: onPrintFinished
            });
        }
    };

    var print = function(){
        if(check_url){
            getUrl('/print/'+check_url,onPrintLoaded);
        }
    };

    $print_btn.on('click',function(){
        print();
    });

    var onChecked = function(response){
        if(response.status && response.status!=''){
            setMessage(response.status);
        }else{
            if(--max_retry>0){
                start();
            }else{
                setMessage('Un e-mail vous a été envoyé');
            }
        }
    };

    var overlayLoader = function(show,options){
        if($.LoadingOverlay){
            $loading_el.LoadingOverlay(show,options);
        }
    };

    var launch = function(){
        if(check_url){
            getUrl('/check/' + check_url,onChecked);
        }
    };

    var start = function(){
        window.setTimeout(launch,2000);
    };

    overlayLoader('show',defaultLoaderOptions);
    start();

});