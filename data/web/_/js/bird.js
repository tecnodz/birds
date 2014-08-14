/*! Birds v0.2 | (c) 2014 Capile Tecnodesign <ti@tecnodz.com> */
window.requestAnimFrame = (function(){
  return  window.requestAnimationFrame       ||
          window.webkitRequestAnimationFrame ||
          window.mozRequestAnimationFrame    ||
          function( callback ){
            return window.setTimeout(callback, 1000 / 60);
          };
})();

(function(window) {

var _got=0, _langs={};

window.bird = {
    version: '1.0',
    created: new Date(),
    online: navigator.onLine,
    root: document.getElementsByTagName('body')[0],
    base: '/__',
    plugins: [],
    onReady: [],
    css: '',
    isReady: false,
    get:function(q, o, i)
    {
        var r;
        if(!o) { // non-contextual
            if(typeof i ==='undefined') return document.querySelectorAll(q);
            r=document.querySelectorAll(q);
        } else if('length' in o) {
            r=[];
            for(var oi=0;oi<o.length;oi++) {
                var ro=bird.get(q, o[oi]);
                console.log('ro:', ro);
                if(ro.length>0) {
                    for(var roi=0;roi<ro.length;roi++) {
                        r.push(ro[roi]);
                        if(typeof i !=='undefined' && i in r) return r[i];
                    }
                }
            }
        } else if(q.search(/^[#\.]?[^\.\s\[\]\:]+$/)) {
            if(q.substr(0,1)=='#') r=[o.getElementById(q.substr(1))];
            else if(q.substr(0,1)=='.') r=o.getElementsByClassName(q.substr(1));
            else r=o.getElementsByTagName(q);
        } else {
            var id=o.getAttribute('id');
            if(!id) {
                id='_n'+(_got++);
                o.setAttribute('id', id);
                r = document.querySelectorAll('#'+id+' '+q);
                o.removeAttribute('id');
            } else {
                r = document.querySelectorAll('#'+id+' '+q);
            }
        }
        if(typeof i !=='undefined') return (r.length>i)?(r[i]):(false);
        return r;
    },

    setReady:function(fn)
    {
        bird.isReady = false;
        // Mozilla, Opera, Webkit 
        if (document.addEventListener) {
          document.addEventListener( "DOMContentLoaded", function(){
            document.removeEventListener( "DOMContentLoaded", arguments.callee, false);
            bird.isReady = true;
            fn();
          }, false );

        // If IE event model is used
        } else if ( document.attachEvent ) {
          // ensure firing before onload
          document.attachEvent("onreadystatechange", function(){
            if ( document.readyState === "complete" ) {
              document.detachEvent( "onreadystatechange", arguments.callee );
              bird.isReady = true;
              fn();
            }
          });
        }
    },
    //Returns true if it is a DOM node
    isNode: function(o){
        return (
            typeof Node === "object" ? o instanceof Node : 
            o && typeof o === "object" && typeof o.nodeType === "number" && typeof o.nodeName==="string"
        );
    },
    //Returns true if it is a DOM element    
    isElement: function(o){
      return (
            typeof HTMLElement === "object" ? o instanceof HTMLElement : //DOM2
            o && typeof o === "object" && o !== null && o.nodeType === 1 && typeof o.nodeName==="string"
        );
    },
    ready: function(fn)
    {
        if(arguments.length>0) {
            if(!bird.isReady) bird.setReady(bird.ready);
            bird.onReady.push(fn);
        }
        if(bird.isReady) {
            while(bird.onReady.length>0) {
                (bird.onReady.shift())(bird);
            }
        }
    },

    load: function()
    {
        bird.isReady = true;// fix this
        var i=arguments.length;
        while(i-- >0) {
            if(arguments[i].indexOf('.css')>-1) bird.element.call(document.getElementsByTagName('head')[0], {e:'link',a:{rel:'stylesheet',type:'text/css',href:arguments[i]}});
            else if(arguments[i].indexOf('.js')>-1) bird.element.call(document.body, {e:'script',p:{async:true,src:arguments[i]}});
            else {
                var p=arguments[i].indexOf('?'),f=(p>0)?(arguments[i].substr(0,p)):(arguments[i]), qs=(p>0)?(arguments[i].substr(p)):('');
                bird.load(bird.base+'/'+f+'.css'+qs, bird.base+'/'+f+'.js'+qs);
                delete(p);
                delete(f);
                delete(qs);
            }
        }
        delete(i);
    },

    error: function() 
    {
        console.log('[ERROR]'+JSON.stringify(arguments));
        bird.alert(bird.t('serverError'));
    },
    log: function() 
    {
        console.log('[INFO]'+JSON.stringify(arguments));
    },

    test: function() 
    { 
        console.log('Birds running, version: '+ bird.version);
    },
 
    removeItem: function(arr) {
        var what, a = arguments, L = a.length, ax;
        while (L > 1 && arr.length) {
            what = a[--L];
            while ((ax= arr.indexOf(what)) !== -1) {
                arr.splice(ax, 1);
            }
        }
        return arr;
    },
    encodeHtml: function (s) {
        return s.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/'/g, '&apos;')
                .replace(/"/g, '&quot;');
    },
    decodeHtml: function (s) {
        return s.replace(/&quot;/g, '"')
                .replace(/&apos;/g, '\'')
                .replace(/&gt;/g, '>')
                .replace(/&lt;/g, '<')
                .replace(/&amp;/g, '&');
    },
    cookie:function(name, value, expires, path, domain, secure) {
        if(arguments.length>1) {
            document.cookie = name + "=" + escape(value)
                + ((arguments.length>2 && expires != null)?("; expires=" + expires.toGMTString()):(''))
                + ((arguments.length>3 && path)?("; path=" + path):(''))
                + ((arguments.length>4 && domain)?("; domain=" + domain):(''))
                + ((arguments.length>5 && secure)?("; secure"):(''));
        } else {
            var a = name + "=", i = 0;
            while (i < document.cookie.length) {
                var j = i + a.length;
                if (document.cookie.substring(i, j) == a) return bird.cookiev(j);
                i = document.cookie.indexOf(" ", i) + 1;
                if (i == 0) break;
            }
            return null;

        }
        return value;
    },
    cookiev:function(offset) {
        var endstr = document.cookie.indexOf (";", offset);
        if (endstr == -1) { endstr = document.cookie.length; }
        return unescape(document.cookie.substring(offset, endstr));
    },
    lang: function(s)
    {
        if(s) bird.language=s;
        return bird.language;
    },
    langw:function(ctx,before,after)
    {
        var h=bird.get('link[rel="alternate"][hreflang],meta[name="language"]');
        if(h.length>1) {
            var r={e:'span',a:{'class':'lang'},c:[]},l='';
            for(var hi=0;hi<h.length;hi++) {
                if(h[hi].nodeName.toLowerCase()=='meta') {
                    l=h[hi].getAttribute('content');
                    _langs[l]=true;
                    r.c.push({e:'a',a:{'class':l+' selected'},c:l});
                } else {
                    l=h[hi].getAttribute('hreflang');
                    _langs[l]=false;
                    r.c.push({e:'a',a:{'class':l,'data-lang':l,href:'#'+l},c:l,t:{trigger:_setLanguage}});
                }
            }
            if(ctx) return bird.element.call(((typeof ctx) == 'string')?(bird.get(ctx,null,0)):(ctx),r,before,after);
            else return bird.element(r,before,after)
        }
        return false;
    },
    click: function(c)
    {
        var t = c.getAttribute('onclick') || c.getAttribute('onmousedown');
        if(t && typeof(t)=='string') {
            //console.log(t);
            return (new Function(t))();
        } else if(c.click) {
            return c.click();
        } else {
            var e=document.createEvent('HTMLEvents');
            e.initEvent('click', true, true);
            return c.dispatchEvent(e);
        }
    },

    hash: function(s) {
        return bird.hashCode(s).toString(36);
    },
    noEvent: function(e) {
        //console.log('noEvents!!!', e)
        e.stopPropagation();
        e.preventDefault();
        return false;
    },
    hashCode: function(s) {
        var h = 0, i, c, l;
        if (s.length == 0) return h;
        for (i = 0, l = s.length; i < l; i++) {
            c  = s.charCodeAt(i);
            h  = ((h<<5)-h)+c;
            h |= 0; // Convert to 32bit integer
        }
        return h;
    },

    b64Blob: function(b64Data, contentType, sliceSize) {
        if(b64Data.substr(0,5)=='data:') {
            var p=b64Data.indexOf(',');
            if(p) {
                contentType = b64Data.substr(5,p-5).replace(/\;.*/, '');
                b64Data = b64Data.substr(p+1);
            }
        }


        contentType = contentType || '';
        sliceSize = sliceSize || 1024;

        var byteCharacters = atob(b64Data);
        var byteArrays = [];

        for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
            var slice = byteCharacters.slice(offset, offset + sliceSize);
            var byteNumbers = Array.prototype.map.call(slice, charCodeFromCharacter);
            var byteArray = new Uint8Array(byteNumbers);

            byteArrays.push(byteArray);
        }

        var blob = new Blob(byteArrays, {type: contentType});
        return blob;
    },

    t: function(s) {
        //console.log('t: ', s, bird.t[s]);
        return bird.t[s] || s;
        if(s in bird.t) return bird.t[s];
        else return s;
    },
    reRemote: /^https?\:\/\//,
    reStorable: /^https?\:\/\/[^\?]+\.(jpg|png|gif|html|svg|xml|json)$/,
    reStorableText: /^https?\:\/\/[^\?]+\.(html|svg|xml|json)$/,
    wget: function(url, success, error, dataType, context) {
        var qs='';
        //console.log('wget: '+url);
        if(window.location.protocol=='file:' && url.search(bird.reRemote)<0) {
            if(url.indexOf('?')>1) url = url.substr(0,url.indexOf('?'));
            if(!bird.base) bird.base = window.location.href.replace(/[^\/\\]+$/, '');
            url = bird.base+url;

            //console.log('wget (local): '+url);
        } else if(url.search(bird.reStorableText)>-1) {
            // external storable files
            // ok, they might be stored, is it enabled?
            if(bird.Storage && bird.Storage.enabled) {
                if(bird.Storage.get(url, success, error, dataType, context)) return;
            }
        } else if(url.search(bird.reRemote)<0) { // force POST not to cache (in case URL was repeated)
            qs = true;
        }
        return ajax(url, qs, success, error, dataType, context);
    },
    post: function(url, data, success, error, dataType, context) {
        return ajax(url, data, success, error, dataType, context);
    },
    pad: function (n) {return n<10 ? '0'+n : n },
    isNumber: function(n) {
      return !isNaN(parseFloat(n)) && isFinite(n);
    },
    addHtml: function(o, s)
    {
        if('MSApp' in window) {
            MSApp.execUnsafeLocalFunction(function() {o.innerHTML=s;o=null;s=null});
        } else {
            o.innerHTML = s;
        }
    },
    element: function(o,before,after) {
        var r,n;
        if(typeof(o)=='string') {
            r=document.createTextNode(o);
        } else if(o.e) {
            r=document.createElement(o.e);
            if(o.p) {
                for(n in o.p) {
                    r[n]=o.p[n];
                    delete(o.p[n]);
                    delete(n);
                }
            }
            delete(o.p);
            if(o.a) {
                for(n in o.a) {
                    r.setAttribute(n,o.a[n]);
                    delete(o.a[n]);
                    delete(n);
                }
            }
            delete(o.a);
            if(o.t) {
                for(n in o.t) {
                    if(n=='trigger' || n=='fastTrigger') bird[n](r,o.t[n]);
                    else bird.addEvent(r,n,o.t[n]);
                    delete(o.t[n]);
                    delete(n);
                }
            }
            delete(o.t);
        } else {
            if(o instanceof Array) o={c:o};
            r=document.createDocumentFragment();
        }
        if(o.c) {
            if(typeof(o.c)=='string') {
                r.appendChild(document.createTextNode(o.c));
            } else {
                var t=o.c.length,i=0;
                while(i < t) {
                    if(typeof(o.c[i])=='string') r.appendChild(document.createTextNode(o.c[i]));
                    else bird.element.call(r,o.c[i]);
                    i++;
                }
                delete(i);
                delete(t);
            }
        }
        delete(o.c);
        delete(o);

        if(before) return before.parentNode.insertBefore(r,before);
        else if(after) after.parentNode.insertBefore(r,after.nextSibling);
        else if(this.appendChild) return this.appendChild(r);
        else return r;
    },
    createElement: function(p, el, id, cn, c, fn)
    {
        var a=document.createElement(el);
        if(id) a.id=id;
        if(cn) a.className=cn;
        if(p)  p.appendChild(a);
        if(fn) {
            bird.fastTrigger(a, fn);
            bird.trigger(a, bird.stopEvent);
        }
        return a;
    },
    addEvent:  function(o, tg, fn) {
        if (o.addEventListener) {
            o.addEventListener(tg, fn, false);
        } else if (o.attachEvent) {
            o.attachEvent('on'+tg, fn);
        } else {
            o['on'+tg] = fn;
        }
    },
    fastTrigger: function(o,fn){
        if(o.addEventListener) {
            o.addEventListener('touchstart', fn, false);
            o.addEventListener('mousedown', fn, false);
        } else if(o.attachEvent) {
            o.attachEvent('onclick', fn);
        }
    },
    trigger: function(o,fn){
        if(o.addEventListener) {
            o.addEventListener('tap', fn, false);
            o.addEventListener('click', fn, false);
        } else if(o.attachEvent) {
            o.attachEvent('onclick', fn);
        }
    },
    stopEvent:function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    },
    date: function (d) {
      return d.getUTCFullYear()+'-'
          + bird.pad(d.getUTCMonth()+1)+'-'
          + bird.pad(d.getUTCDate())+'T'
          + bird.pad(d.getUTCHours())+':'
          + bird.pad(d.getUTCMinutes())+':'
          + bird.pad(d.getUTCSeconds())+'Z'
    }
};


function charCodeFromCharacter(c) {
    return c.charCodeAt(0);
}

function _setLanguage(l)
{
    if(typeof l != 'string') {
        if('stopPropagation' in l) {
            l.stopPropagation();
            l.preventDefault();
        }
        l=this.getAttribute('data-lang');
    }
    if(!(l in _langs)) return false;
    bird.cookie('lang', l, null, '/');
    window.location.reload();
    return false;
}

/*
document.addEventListener('online',  updateOnlineStatus);
document.addEventListener('offline', updateOnlineStatus);
function updateOnlineStatus(event) {
    //console.log('updated online status from: '+bird.online+' to: '+navigator.onLine);
    bird.online = navigator.onLine;//var condition = navigator.onLine ? "online" : "offline";
}
*/

if([].reduce) window.bird.hashCode=function(s){return s.split('').reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);}

if(!console) {
    window.console={log:function(){}};

}
if(window.bird.root.style.transition != undefined) {
    bird.transitionEnd = 'transitionend';
} else if(window.bird.root.style.WebkitTransition != undefined) {
    bird.transitionEnd = 'webkitTransitionEnd';
} else if(window.bird.root.style.OTransition != undefined) {
    bird.transitionEnd = 'oTransitionEnd';
} else {
    bird.transitionEnd = null;
}
bird.language = bird.cookie('lang') || document.querySelector('meta[name="language"]').getAttribute('content');

var _t={};
bird.timeoutQueue=_t;
bird.timeout=function(fn, timeout, context, params)
{
    //console.log('bird.timeout for '+fn+' '+timeout);
    var t=new Date().getTime(), clearEnd=((typeof fn!='function' && typeof fn!='string') || (typeof timeout!='undefined' && timeout<0)), n, same;
    if(!fn) fn=false;
    if(!context) context=false;
    if(!params) params=false;
    for(n in _t) {
        same=(fn==_t[n][0] && context==_t[n][1]);
        if(!same && t<_t[n][3]) continue;
        clearTimeout(n);
        if(_t[n][4]) {
            _t[n][1].removeEventListener(bird.transitionEnd, bird.timeout);
        }
        if(_t[n].length>5) continue;
        _t[n][5]=true;
        if(same) { // don't run if the same function and context were requested twice

        } else if(_t[n][1] && _t[n][2]) {
            if(typeof(_t[n][0])=='string') bird[_t[n][0]].apply(_t[n][1], _t[n][2]);
            else _t[n][0].apply(_t[n][1], _t[n][2]);
            _t[n][2]=null;
            _t[n][1]=null;
        } else if(_t[n][1]) {
            if(typeof(_t[n][0])=='string') bird[_t[n][0]].call(_t[n][1]);
            else _t[n][0].call(_t[n][1]);
            _t[n][1]=null;
        } else {
            if(typeof(_t[n][0])=='string') bird[_t[n][0]]();
            else _t[n][0]();
        }
        _t[n][0]=null;
        _t[n]=null;
        delete _t[n];
    }
    if(!clearEnd) {
        var te=false;
        if(!timeout) {
            timeout=3000;
            if(context && bird.transitionEnd) {
                //console.log('adding transition...');
                context.addEventListener(bird.transitionEnd, bird.timeout, false);
                te=true;
                t+=100;
            }
        } else {
            t+=timeout;
        }
        _t[setTimeout(bird.timeout, timeout)]=[fn,context,params,t,te];
    }
    //console.log('timeout done for '+fn);
}

var _ajax={};
function ajax(url, data, success, error, dataType, context) {

    if( typeof error == 'undefined' || !error ) error = this.error;
    if(!context) context = this;
    if (!window.XMLHttpRequest  || (url in _ajax)) {
        // no support for ajax
        error.apply(context);
        return false;
    }

    _ajax[url] = { r: new XMLHttpRequest(),
        success: success,
        error: error,
        context: context,
        type: dataType
    };
    var qs = (data===true)?(((url.indexOf('?')>-1)?('&'):('?'))+(new Date().getTime())):(''),
        m = (data && data!==true)?('post'):('get');

    // make post!!!

    _ajax[url].r.onreadystatechange = ajaxProbe;
    _ajax[url].r.open(m, url+qs, true);
    _ajax[url].r.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    _ajax[url].r.send();
}

function ajaxProbe()
{
    var u;
    for(u in _ajax) {
        if(_ajax[u].r.readyState==4) {
            console.log('status for '+u+': '+_ajax[u].r.status);
            console.log(_ajax[u].r.responseText);
            var d;
            if(_ajax[u].type=='xml' && _ajax[u].r.responseXML) d=_ajax[u].r.responseXML;
            else d=_ajax[u].r.responseText;
            if(_ajax[u].r.status==200) {
                _ajax[u].success.apply(_ajax[u].context, [ d, _ajax[u].r.status, u ]);
            } else {
                _ajax[u].error.apply(_ajax[u].context, [ d, _ajax[u].r.status, u ]);
            }
            delete(d);
            delete(_ajax[u].r);
            delete(_ajax[u]);
        }
    }
}

var _pop={};
bird.pop=function(url, o)
{
    if(url in _pop) return bird.popClose(url);
    _pop[url] = 'bp'+bird.hash(url);
    bird.wget(url, _popSuccess, bird.popError, null, o);    
}

function _popSuccess(d, status, url)
{
    var w=document.getElementById(_pop[url]);
    if(!w) {
        var p=bird.createElement(null, 'div', null, bird.css+'pop-box')
        p.setAttribute('data-url', url);
        w=bird.createElement(p, 'div', _pop[url], bird.css+'pop');
        bird.createElement(p, 'a', null, bird.css+'icon-round '+bird.css+'icon-close', null, _popClose);
        w.innerHTML = d;
        document.body.appendChild(p);
        delete(p);
    } else {
        w.innerHTML = d;
    }
    delete(w);

}
function _popError(d, status, url)
{
    console.log('popError', arguments.length, arguments[0], this);
    _popClose(url);
}

function _popClose(url)
{
    if(typeof url != "string") url=this.parentNode.getAttribute('data-url');
    if(!(url in _pop)) return false;
    var w=document.getElementById(_pop[url]);
    if(w) {
        w.parentNode.parentNode.removeChild(w.parentNode);
        delete(w);
    }
    delete(_pop[url]);
    return false;
}

if (!Object.create) {
    Object.create = (function(){
        function F(){}

        return function(o){
            if (arguments.length != 1) {
                throw new Error('Object.create implementation only accepts one parameter.');
            }
            F.prototype = o
            return new F()
        }
    })()
}

if (typeof document.getElementsByClassName!='function') {
    document.getElementsByClassName = function() {
        var elms = document.getElementsByTagName('*');
        var ei = new Array();
        for (i=0;i<elms.length;i++) {
            if (elms[i].getAttribute('class')) {
                ecl = elms[i].getAttribute('class').split(' ');
                for (j=0;j<ecl.length;j++) {
                    if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
                        ei.push(elms[i]);
                    }
                }
            } else if (elms[i].className) {
                ecl = elms[i].className.split(' ');
                for (j=0;j<ecl.length;j++) {
                    if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
                        ei.push(elms[i]);
                    }
                }
            }
        }
        return ei;
    }
};
if (!document.querySelectorAll) {
    document.querySelectorAll = function(selector) {
        var doc = document,
        head = doc.documentElement.firstChild,
        styleTag = doc.createElement('STYLE');
        head.appendChild(styleTag);
        doc.__qsaels = [];
         
        styleTag.styleSheet.cssText = selector + "{x:expression(document.__qsaels.push(this))}";
        window.scrollBy(0, 0);
         
        return doc.__qsaels;
    }    
};
[].indexOf||(Array.prototype.indexOf=function(a,b,c){for(c=this.length,b=(c+~~b)%c;b<c&&(!(b in this)||this[b]!==a);b++);return b^c?b:-1;});
//window.Sizzle = window.jQuery.find;
if('cordova' in window) bird.plugins.push('cordova');

window.birds = window.bird;

})(window);