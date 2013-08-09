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
    version: 0.1,
    created: new Date(),
    root: document.getElementsByTagName('body')[0],
    plugins: [],

    error: function() 
    {
        console.log('[ERROR]', arguments);
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

    hash: function(s) {
        return bird.hashCode(s).toString(36);
    },
    noEvent: function(e) {
        console.log('noEvents!!!', e)
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
    t: function(s) {
        if(s in bird.t) return bird.t[s];
        else return s;
    },
    reRemote: /^https?\:\/\//,
    reStorable: /^https?\:\/\/[^\?]+\.(jpg|png|gif|html|svg|xml|json)$/,
    reStorableText: /^https?\:\/\/[^\?]+\.(html|svg|xml|json)$/,
    wget: function(url, success, error, dataType, context) {
        var p='GET';
        if(url.search(bird.reStorableText)>-1) {
            // external storable files
            // ok, they might be stored, is it enabled?
            if(bird.Storage && bird.Storage.enabled) {
                if(bird.Storage.get(url, success, error, dataType, context))
                    return;
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
    pad: function (n) {return n<10 ? '0'+n : n },
    date: function (d) {
      return d.getUTCFullYear()+'-'
          + bird.pad(d.getUTCMonth()+1)+'-'
          + bird.pad(d.getUTCDate())+'T'
          + bird.pad(d.getUTCHours())+':'
          + bird.pad(d.getUTCMinutes())+':'
          + bird.pad(d.getUTCSeconds())+'Z'
    }
};
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

