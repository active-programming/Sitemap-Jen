/**
 * MyCitySelector
 * @author Konstantin Kutsevalov
 * @version 2.0.0
 */

jQuery(function($){

    function deleteContentFieldHandler() {
        $.ajax({
            "url": "index.php",
            "type": "get",
            "dataType": "json",
            "data": {
                "option": "com_mycityselector",
                "controller": "fields",
                "task": "DeleteFieldValue",
                "id": $(this).attr('id'),
                "_$btn": $(this)
            }
        }).done(function(json) {
            if (json && json.status == "200") {
                this._$btn.closest(".field-value").remove();
            } else {
                alert("Произошла ошибка :( не смог удалить поле.")
            }
        }).fail(function(xhr, err) {
            alert("Произошла ошибка :(\n" + err);
        });
    }

    $('#addform').on("click", function() {
        $.get("index.php?option=com_mycityselector&controller=fields&task=getform&format=raw", function(data) {
            var $form = $(data);
            $form.insertBefore("#addform");
            $(".chzn-done").chosen(choosen_opt);

            // TODO может использоваться другой редактор, нужно это как-то проверять
            // или еще проще, это возвращать код инициализации в ответе с сервера
            tinymce.init({"selector": "textarea", "height" : "200", "width": "100%"});

            $('.delete-field-value', $form).on("click", deleteContentFieldHandler);
        })
    });

    $('.delete-field-value').on("click", deleteContentFieldHandler);

    // for editor popup window
    if ($("#fast-search-content").length > 0) {
        var $queryString = $("#query_string"),
            fsXHR = null;
        $queryString.on("keyup", function() {
            var query = $queryString.val(),
                lastQuery = $queryString.data("last_value");
            if (query != lastQuery) {
                if (fsXHR) fsXHR.abort();
                $.ajax({
                    "url": "index.php",
                    "type": "get",
                    "dataType": "json",
                    "data": {
                        "option": "com_mycityselector",
                        "controller": "fields",
                        "task": "Popup",
                        "query": query
                    }
                }).done(function(json) {

                    console.log(json);

                    if (json && json.status == "200") {



                    } else {
                        alert("Произошла ошибка :(")
                    }
                }).fail(function(xhr, err) {
                    alert("Произошла ошибка :(\n" + err);
                });
            }
        });
    }

});