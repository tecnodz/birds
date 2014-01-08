/*! Birdz v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
window.requestAnimFrame = (function(){
  return  window.requestAnimationFrame       ||
          window.webkitRequestAnimationFrame ||
          window.mozRequestAnimationFrame    ||
          function( callback ){
            return window.setTimeout(callback, 1000 / 60);
          };
})();

(function(window) {
window.bird = {
    version: 0.2,
    created: new Date(),
    online: navigator.onLine,
    root: document.getElementsByTagName('body')[0],
    base: null,
    plugins: [],
    onReady: [],

    ready: function()
    {
        while(bird.onReady.length>0) {
            (bird.onReady.shift())(bird);
        }
    },
    error: function() 
    {
        console.log('[ERROR]'+JSON.stringify(arguments));
        bird.alert(bird.t.serverError);
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
        if(s in bird.t) return bird.t[s];
        else return s;
    },
    reRemote: /^https?\:\/\//,
    reStorable: /^https?\:\/\/[^\?]+\.(jpg|png|gif|html|svg|xml|json)$/,
    reStorableText: /^https?\:\/\/[^\?]+\.(html|svg|xml|json)$/,
    wget: function(url, success, error, dataType, context) {
        var p='GET';
        console.log('wget: '+url);
        if(window.location.protocol=='file:' && url.search(bird.reRemote)<0) {
            if(url.indexOf('?')>1) url = url.substr(0,url.indexOf('?'));
            if(!bird.base) bird.base = window.location.href.replace(/[^\/\\]+$/, '');
            url = bird.base+url;

            console.log('wget (local): '+url);
        } else if(url.search(bird.reStorableText)>-1) {
            // external storable files
            // ok, they might be stored, is it enabled?
            if(bird.Storage && bird.Storage.enabled) {
                if(bird.Storage.get(url, success, error, dataType, context)) return;
            }
        } else if(url.search(bird.reRemote)<0) { // force POST not to cache (in case URL was repeated)
            p = 'POST';
        }
        $.ajax({
            method: 'GET',
            url: url,
            success: success,
            error: ( typeof error !== 'undefined' && error )?( error ):( this.error ),
            dataType: ( typeof dataType !== 'undefined' && dataType )?( dataType ):( null ),
            context: context || this
        });
    },
    post: function(url, data, success, error, dataType, context) {
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            success: ( typeof success !== 'undefined' && success )?( success ):( this.alert ),
            error: ( typeof error !== 'undefined' && error )?( error ):( this.error ),
            dataType: ( typeof dataType !== 'undefined' && dataType )?( dataType ):( null ),
            context: context || this
        });
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
    createElement: function(p, el, id, cn, c, fn)
    {
        var a=document.createElement(el);
        if(id) a.id=id;
        if(cn) a.className=cn;
        if(p)  p.appendChild(a);
        if(fn) bird.fastTrigger(a, fn);
        return a;
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
    date: function (d) {
      return d.getUTCFullYear()+'-'
          + bird.pad(d.getUTCMonth()+1)+'-'
          + bird.pad(d.getUTCDate())+'T'
          + bird.pad(d.getUTCHours())+':'
          + bird.pad(d.getUTCMinutes())+':'
          + bird.pad(d.getUTCSeconds())+'Z'
    }
};

bird.t.serverError = 'The was an error while processing your request. Please try again later.';

function charCodeFromCharacter(c) {
    return c.charCodeAt(0);
}

document.addEventListener('online',  updateOnlineStatus);
document.addEventListener('offline', updateOnlineStatus);
function updateOnlineStatus(event) {
    //console.log('updated online status from: '+bird.online+' to: '+navigator.onLine);
    bird.online = navigator.onLine;//var condition = navigator.onLine ? "online" : "offline";
}



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
[].indexOf||(Array.prototype.indexOf=function(a,b,c){for(c=this.length,b=(c+~~b)%c;b<c&&(!(b in this)||this[b]!==a);b++);return b^c?b:-1;});
window.Sizzle = window.jQuery.find;
if('cordova' in window) bird.plugins.push('cordova');

window.birds = window.bird;

})(window);

/*! Birdz v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */

