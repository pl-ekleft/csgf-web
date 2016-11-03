if(START) {
    
    socket.on('coin_new', function(data) {
        data = JSON.parse(data);
        var audio = new Audio('/assets/sounds/coin/coinnew.ogg');
        if(sound_status) audio.play();
        $('#cointable').append($(coin_tpl(data)));
    }).on('coin_scroll', function(data) {
        data = JSON.parse(data);
        var audio = new Audio('/assets/sounds/coin/coinplay.ogg');
        if(sound_status) audio.play();
        $("#cointable #coin_" + data.id + " #second #user-name").text(data.name);
        $("#cointable #coin_" + data.id + " #second #user-ava").attr("src", data.ava);
        $("#cointable #coin_" + data.id + " #f1 .front #user-ava").attr("src", data.ava);
        $("#cointable #coin_" + data.id + " button").fadeOut();
        setTimeout(function(){
            $("#cointable #coin_" + data.id + " #f1").fadeIn(), setTimeout(function() {
                $("#cointable #coin_" + data.id + " #f1").addClass('flip');
            }, 500);
            setTimeout(function(){
                $("#cointable #coin_" + data.id + " #f2 .back #user-ava").attr("src", data.ava);
                $("#cointable #coin_" + data.id + " #f2").fadeIn(), setTimeout(function() {
                    $("#cointable #coin_" + data.id + " #f2").addClass('flip');
                }, 500);
                setTimeout(function(){
                    $("#cointable #coin_" + data.id + " #f3 .front #user-ava").attr("src", data.lava);
                    $("#cointable #coin_" + data.id + " #f3 .back #user-ava").attr("src", data.wava);
                    $("#cointable #coin_" + data.id + " #f3").fadeIn(), setTimeout(function() {
                        $("#cointable #coin_" + data.id + " #f3").addClass('flip');
                        setTimeout(function() {
                            if(data.wava == data.ava){
                                $("#cointable #coin_" + data.id + " #second #user-name").css('color', '#d1ff78');
                            } else {
                                $("#cointable #coin_" + data.id + " #first #user-name").css('color', '#d1ff78');
                            }
                        }, 5000);
                    }, 500);
                    setTimeout(function() {
                        $("#cointable #coin_" + data.id).fadeOut(), setTimeout(function() {
                            $("#cointable #coin_" + data.id).remove();
                            updateBalance();
                        }, 500);
                    }, 10000);
                }, 1000);
            }, 1000);
        }, 500);
    });
}
$(function () {
    window.coin_tpl = _.template($('#coin-template').html());
});

$(document).ready(function () {});

$(document).on('click', '#coin_bet', function () {
    $.post('/coin/nbet', {
        sum: $('#coin_sum').val()
    },
    function(data) {
        updateBalance();
        return $.notify(data.text, data.type);
    });
});

function coin_bet( id ) {
    $.post('/coin/bet', {
        id: id
    },
    function(data) {
        return $.notify(data.text, data.type);
    });
}