/**
 * MyCitySelector
 * @author Konstantin Kutsevalov
 * @version 2.0.0
 */

jQuery(function($){

    // TODO нужно заменить $.get на $.ajax и проверять ответ. В ответе могу прийти ошибки и нужно об этом уведомить.
    // отправлять c сервера json вида {"status":"success", "message":""} - для успешных операций и
    // {"status":"error", "message":"описание ошибки"} - для ошибок

    function deleteHandler(button) {
        var id = $(button).attr('id');
        $.get('index.php?option=com_mycityselector&controller=fields&task=DeleteFieldValue&id='+id, function(){
            $(button).parent().parent().parent().remove();
        })
    }

    $('#addform').click(function(){
        $.get('index.php?option=com_mycityselector&controller=fields&task=getform&format=raw',function(data){
            $(data).insertBefore('#addform');
            $(".chzn-done").chosen(choosen_opt);
            tinymce.init({
                selector: 'textarea'
            });
            $('.delete-field-value').click(function() {
                deleteHandler(this);
            });
        })
    });

    $('.delete-field-value').click(function() {
        deleteHandler(this);
    });

    // for editor popup window
    console.log("hi");

});