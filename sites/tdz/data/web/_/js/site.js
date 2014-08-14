/*! Tecnodesign */
(function(){
    if(!('_tdz' in window)) window._tdz = {};

    function toggleBox()
    {
        var el=bird.get('.box', this), i=el.length;
        if(i==0) return false;
        while(i-- > 0) {
            if(el[i].className.search(/\binactive\b/)>-1) el[i].className = el[i].className.replace(/\s*\binactive\b/, '');
            else el[i].className += (el[i].className)?(' inactive'):('inactive');
        }
        delete(el);
        if(_toT) clearTimeout(_toT);
        _toT = setTimeout(topmostColor, 700);
        return true;
    }

    function init()
    {
        var h=bird.get('.heading'), hi=h.length;
        while(hi-- > 0) {
            if(toggleBox.call(h[hi])) bird.addEvent(h[hi], 'click', toggleBox);
        }
     
        bird.langw('#header');

        delete(h);
        delete(hi);
    }

    var _toColor = bird.get('*[data-color]'), _toT=0, _toL=0;

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
            o=_toColor[i];
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
        window.onscroll=topmostColor;
        _toT = setTimeout(topmostColor, 1000);
    }

    bird.ready(init);
})();
