$(document).ready(function(){
    $('.generated-link .clickable').on('click',function(e){
        e.preventDefault();
        var $input = $(this).closest('input');
        $input[0].select();
        document.execCommand('copy');
        $(this).tooltip('show',{
            title: 'copié !!'
        });
        return false;
    })
});