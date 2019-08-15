$(document).ready(function(){
    $('.generated-link .clickable').on('click',function(e){
        e.preventDefault();
        var _this = this;
        var $input = $('input[type="text"]',$(this).parent());
        $input[0].select();
        document.execCommand('copy');
        $(this).tooltip({
            trigger: 'manual',
            container: 'body',
            title: 'copié !!'
        });
        $(this).tooltip('show');
        window.setTimeout(function(){
            $(_this).tooltip('hide');
        },1500);
        return false;
    })
});