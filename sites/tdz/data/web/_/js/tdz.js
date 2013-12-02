/*! Tecnodesign */
$(function(){
    if(!('_tdz' in window)) window._tdz = {};
    /*

    function toggleBox(){
        $('.box', this).toggle(300);
        if(_toT) clearTimeout(_toT);
        _toT = setTimeout(topmostColor, 300);
    }
    $('.heading > .box').hide(100).parent('.heading').click(toggleBox);
    */
    function toggleBox(){
        $('.box', this).toggleClass('inactive');
        if(_toT) clearTimeout(_toT);
        _toT = setTimeout(topmostColor, 700);
    }
    $('.heading > .box').addClass('inactive').parent('.heading').click(toggleBox);


    var _toColor = $('*[data-color]'), _toT=0, _toL=0;

    function topmostColor()
    {
        var t=new Date().getTime();
        if(_toT) clearTimeout(_toT);
        if(_toL+200 < t) {
            _toL = t;
        } else {
            _toT = setTimeout(topmostColor, 100);
            return;
        }
        var i=0,r,o;
        while(i<_toColor.length) {
            o=_toColor.get(i);
            r=o.getBoundingClientRect();
            if(r && r.top>=0 && r.height>20) break;
            else o=null;
            i++;
        }
        var h=document.getElementsByTagName('html')[0];
        if(o) {
            var c=o.getAttribute('data-color');
            if(h.className!=c) h.className=c;
            c=null;
            o=null;
        } else {
            if(h.className) h.className='';
        }
        h=null;
        r=null;
    }

    if(_toColor.length>0) {
        $(window).scroll(topmostColor);
        _toT = setTimeout(topmostColor, 1000);
    }

});
