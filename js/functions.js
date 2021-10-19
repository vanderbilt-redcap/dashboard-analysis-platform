function allfieldsSelecter(){
    var errMsg = [];
    $('#errMsgContainerModal').empty();
    if($('#question').val() == "" || $('#question').val() == undefined){
        errMsg.push("Please select a value for the <strong>Question</strong>.");
    }
    if($('#study').val() == "" || $('#study').val() == undefined){
        errMsg.push("Please select a value for the <strong>Study</strong>.");
    }

    if (errMsg.length > 0) {
        $.each(errMsg, function (i, e) {
            $('#errMsgContainerModal').append('<div>' + e + '</div>');
        });
        $('#errMsgContainerModal').show();
        return false;
    }
    return true;
}
function loadTable(url){
    if(allfieldsSelecter()) {
        var question = "&question=" + $('#question option:selected').val();
        var study = "&study=" + $('#study option:selected').val();

        if($('#daterange').val() == "Select a date range..."){
            daterange = "&daterange=";
        }else{
            var daterange = "&daterange=" + $('#daterange').val();
        }
        var data = question + study + daterange;

        $('#loadTablebtn').prop('disabled', true);
        $.ajax({
            type: "POST",
            url: url,
            data: data,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                paramValue = jQuery.parseJSON(result);
                var Newulr = getParamUrl(window.location.href, paramValue);
                window.location.href = Newulr;
            }
        });
    }
}

function getParamUrl(url, newParam){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }

    if(url.match(/(&dash=)/)){
        var oldParam = url.split("&dash=")[1];
        url = url.replace( oldParam, newParam );
    }else{
        url = url + "&dash="+newParam;
    }
    return url;
}

function selectOnlyOneGroup(element){
    var selectedName = $(element).attr('name');
    if($('#filter input[type=checkbox]:checked').length > 1) {
        $('#filter input[type=checkbox]:checked').each(function () {
            if(selectedName != $(this).attr('name')){
                $(element).prop('checked',false);
            }
        });
    }
}

function etnicity_change_icon(id){
    if($('#'+id).attr('symbol') == 0){
        $('#'+id).attr('symbol',1);
        $('#'+id).removeClass('fa-plus-circle');
        $('#'+id).addClass('fa-minus-circle');
        $('[etnicity=1]').removeClass('hide');
    }else{
        $('#'+id).attr('symbol',0);
        $('#'+id).removeClass('fa-minus-circle');
        $('#'+id).addClass('fa-plus-circle');
        $("[etnicity=1]").addClass('hide');
    }
}

function isItResponseRates(selector,url){
    $.ajax({
        type: "POST",
        url: url,
        data:"&selector="+selector,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            paramValue = jQuery.parseJSON(result)
            console.log(paramValue)
            $('#study').html(paramValue);
        }
    });
}