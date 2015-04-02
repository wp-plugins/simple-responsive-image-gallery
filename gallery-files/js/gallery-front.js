jQuery(function ($) {
    var mobile = (obj.closebutton == "true") ? true : false;
    var loop = (obj.loopatend == "1") ? true : false;
    //alert(obj.loopatend);
    $('.swipebox').swipebox({
        initialIndexOnArray: Number(obj.imagenumber),
        hideCloseButtonOnMobile: mobile,
        hideBarsDelay: Number(obj.bardelay),
        loopAtEnd: loop
    });
});