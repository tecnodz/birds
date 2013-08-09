/*!Feed*/
(function(bird){

var feedCheck = function(app)
{
    // checking for app-specific keywords, triggered by any attribute data-app
    var da=Sizzle('*[data-feed]', app),d,attr,i,j;
    if(da && (i=da.length)) {
        while(i>0) {
            i--;
            attr = da[i].getAttribute('data-feed');
            if(attr) {
                this.data.feed=''+attr;
                if(this.data.feed.substr(0,1)=='/' && bird.r) {
                    this.data.feed = bird.r + this.data.feed;
                    da[i].setAttribute('data-feed', this.data.feed);
                }
                _feeds[this.data.feed] = false;
                j=da[i].attributes.length;
                while(j>0) {
                    j--;
                    if(da[i].attributes[j].specified && da[i].attributes[j].name.substr(0,10)=='data-feed-') {
                        if(da[i].attributes[j].value.search(/^[0-9]+$/)>-1)
                            this.data[da[i].attributes[j].name.substr(10)]=parseInt(da[i].attributes[j].value);
                        else
                            this.data[da[i].attributes[j].name.substr(10)]=da[i].attributes[j].value;
                    }
                }
                if((this.id in bird.Apps) && bird.Apps[this.id].Feed) {
                    this.run = bird.Apps[this.id].Feed;
                } else {
                    this.run = bird.Feed.prototype.run;
                }
                this.data.context = da[i].getAttribute('id');
                if(!('count' in this.data) || !this.data.count) {
                    // add infinite scrolling
                    $(da[i]).bind('scroll', feedCheckScroll);
                }
            }

        }
    }
};

var _feeds={}, _T={};

function feedCheckScroll(e)
{
    console.log('feedCheckScroll', e.target.scrollHeight);
    var u=e.target.getAttribute('data-feed');
    if(_feeds[u] || e.target.scrollHeight<=50) return;
    var o=$(e.target);
    if(e.target.scrollHeight <= 50 + o.scrollTop() + o.height() ) {
        _feeds[u]=true;
        var app=bird.a[o.parents('.app').eq(0).attr('id')], p=[], l=o.find('.entry:last').attr('data-published'), t=o.find('.entry:first').attr('data-published');
        if(l && t) {
            p.push('t='+t);
            p.push('l='+l);
            u += (u.indexOf('?')>-1)?('&'):('?');
            u += p.join('&');
            console.log('request url: '+u)
            bird.wget(u, app.run, bird.error, 'json', app);

        }
    }
    /*
    if ($(e.target).scrollTop() >= $(document).height() - $(window).height() - 20) {
    if(scrollLoad && currentPage < totalPages)
    {
      $.mobile.showPageLoadingMsg();
      ShowMoreThings();
      scrollLoad = false; //Is set to true on ajax success in ShowMoreThings().
    }
  }
    */
}

function sortEntry(a,b)
{
    var da = ('getAttribute' in a)?(new Date(a.getAttribute('data-published'))):(new Date(a.published));
    var db = ('getAttribute' in b)?(new Date(b.getAttribute('data-published'))):(new Date(b.published));
    if (da > db)
        return -1;
    else if(da < db)
        return 1;
    else
        return 0;
}

function uniqueEntries(a,b)
{

    var c=[];
    if(!b || b.length==0) c=a;
    else if(!a || a.length==0) c=b;
    else c=a.concat(b)
    var r=[], d={}, i=c.length, id, n=false;
    while(i-- > 0) {
        if(!c[i]) continue;
        n = ('getAttribute' in c[i]);
        id = (n)?(c[i].getAttribute('id')):(c[i].id);
        if(id in d) {
            if(!n && d[id][0]!=c[i].published) {
                // replace HTML node with updated element
                r[d[id][1]]=c[i];
                d[id][0]=c[i].published;
            }
            continue;
        }
        d[c[i].id]=[(n)?(c[i].getAttribute('data-published')):(c[i].published),r.length-1];
        r.push(c[i]);
    }
    c=null;
    d=null;
    return r.sort(sortEntry);
}

bird.onDownload.push(feedCheck);
bird.Feed = function(id) 
{
    //console.log('this is a feed!', this, this.prototype);
    bird.App.call(this, id);
};
bird.Feed.prototype = Object.create(bird.App.prototype);
bird.Feed.prototype.constructor = bird.Feed;
bird.Feed.prototype.type = 'Feed';
bird.Feed.prototype.run = function(data)
{
    if(!bird.dispatchEvent(this, 'run') || !('feed' in this.data)) {
        return false;
    }
    var u = this.data.feed, p=[];
    _feeds[u]=false;

    if(data) { //feed
        console.log(arguments);
        // process feed in s
        var c = document.getElementById(this.data.context), s, se, firstRun=false, w=false, r=false, next = this.data.interval || 0, changed=false;
        if(!('feedStart' in this.data) || this.data.feedStart!=bird.created.getTime()) {
            //console.log('first run, is it from cache?');
            firstRun = true;
            this.data.feedStart = bird.created.getTime();
            this.data.o = data;
            next = (next)?(next*0.5):(0);
            if(!('item' in data)) {
                if('cache' in data && data.cache) data.updated = false; // some bug, shouldn't have 0 items if cached
                this.data.o.item=[];
            }
            if(!('cache' in data) || !data.cache) {
                console.log('not from cache, please store');
                w = true;
            }
            if('content' in data) {
                c.innerHTML = data.content; 
            } else if(this.data.o.item.length>0) {
                c.innerHTML = '';
            }
        } else if('reset' in data && data.reset) {
            console.log('received a reset request!!!');
            next = 0.01;
            r = true;
            this.data.t = false;
            this.data.o.item=[];
            if('content' in data) {
                c.innerHTML = data.content; 
            } else if(this.data.o.item.length>0) {
                c.innerHTML = '';
            }
            c = null;
        } else if('updated' in data) {
            console.log('was it updated? '+data.updated);
            this.data.t = data.updated;
            if(this.data.o.updated != data.updated) {
                this.data.o.updated = data.updated;
                console.log('was updated, should write the cache');
                w = true;
            } 
        }

        if(c) {
            var l=[], i=0, cc, ids={}, id, pi=this.data.o.item.length;
            if('item' in data && length in data.item) {
                this.data.o.item = uniqueEntries(this.data.o.item, data.item);
                i=this.data.o.item.length;
                if(pi!=i) w = true;
                l = uniqueEntries(this.data.o.item,Sizzle('>.entry',c));
                if(i>0 && 'count' in this.data && this.data.count && i>this.data.count) {
                    this.data.o.item.splice(this.data.count,i - this.data.count);
                    i=this.data.count;
                    var no;
                    while(i<l.length) {
                        no = l.pop();
                        if('getAttribute' in no) {
                            c.removeChild(no);
                        }
                        no = null;
                    }
                }
            }
            if(i>0) {
                fn = (this.id in bird.Apps && 'renderEntry' in bird.Apps[this.id])?(bird.Apps[this.id].renderEntry):(bird.renderEntry);
                var ref, n, replace=false;
                i=l.length;
                while( i-- > 0 ) {
                    n = ('getAttribute' in l[i]);
                    if(!n) {
                        changed = true;
                        s = fn.call(c, l[i]);
                        if(typeof(s)=='string') {
                            se = document.createElement('div');
                            se.className = 'entry';
                            se.innerHTML = s;
                        } else {
                            se = s;
                            if(se.className.search(/\bentry\b/)<0){
                                se.className+=' entry';
                            }
                        }
                        // depending on s/t, data should be appended or prepended
                        if(ref) { // insert or replace before
                            ref = c.insertBefore(se,ref);
                        } else  { // append
                            ref = c.appendChild(se);
                        }
                    } else {
                        ref = l[i];
                    }
                }
                ref = null;
                fn = null;

            } else {
                w = false;
            }
            /*
            if(w) { // check for duplicates before splicing the cache
                ids={};
                var id, ni=[];
                console.log('remove duplicates');
                for(i=0;i<this.data.o.item.length;i++) {
                    id = this.data.o.item[i].id;
                    console.log('check id '+id);
                    if(!(id in ids)) {
                        ni.push(this.data.o.item[i]);
                    } else {

                    }
                    ids[id]=true;
                }
                this.data.o.item = ni;
                ids=null;
                id = null;
            }

            // limit to data-app-count items (if greater than 0)
            if(l.length>0 && 'count' in this.data && this.data.count) {
                if(!firstRun && this.data.o.item.length > this.data.count) {
                    this.data.o.item.splice(this.data.count, this.data.o.item.length - this.data.count);
                    w = true;
                }
                cc = Sizzle('>div',c);
                if(cc.length>this.data.count) {
                    i=cc.length;
                    while( i-- > this.data.count ) {
                        c.removeChild(cc[i]);
                        cc[i]=null;
                    }
                }
                cc=null;
            }
            */

            // set ready for animations
            if(window.getComputedStyle) {
                window.getComputedStyle(c).getPropertyValue('top');
                cc = Sizzle('>div:not(.entry-ready)',c);
                if(cc) {
                    ids={};
                    i=-1;
                    var tor=[];
                    while( ++i < cc.length ) {
                        if(id in cc[i] && cc[i].id in ids) {
                            tor.push(cc[i]);                            
                        } else {
                            cc[i].className+=' entry-ready';
                            ids[cc[i].id]=true;
                        }
                    }
                    while(tor.length>0) {
                        c.removeChild(tor.pop());
                    }
                }
            }
            if(changed && !('count' in this.data) || !this.data.count) {
                cc = Sizzle('>.entry:last-child',c);
                if(cc && c.scrollHeight <= c.offsetHeight && cc[0].offsetTop<c.offsetHeight) { // considerar o total de entradas 
                    feedCheckScroll({target: c, type: 'check' });
                }
            }
            c = null;
        }

        // store content
        if((w || r) && this.data.feed.search(bird.reStorableText)>-1) {
            // external storable files
            // ok, they might be stored, is it enabled?
            if(bird.Storage && bird.Storage.enabled) {
                if(r) {
                    this.data.feedStart = false;
                    this.data.t = false;
                    bird.Storage.remove(this.data.feed);
                } else {
                    if('limit' in this.data && this.data.limit) this.data.o.item = this.data.o.item.splice(0, this.data.limit);
                    this.data.o.cache  = true;
                    bird.Storage.put(this.data.feed, this.data.o, null, null, 'json', this);
                    this.data.o.cache  = false;
                }
            }
        }

        if(next) {
            if(u in _T) clearTimeout(_T[u]);
            _T[u] = setTimeout(bird.runApp, next*1000);
        }
    } else {
        if('t' in this.data && this.data.t) {
            p.push('t='+this.data.t);
        }
        if(p.length>0) {
            u += (u.indexOf('?')>-1)?('&'):('?');
            u += p.join('&');
        }
        //console.log('request url: '+u)
        bird.wget(u, this.run, bird.error, 'json', this);
    }
}

bird.renderEntry = function(e)
{
    var s='', c=document.createElement('div');
    c.className = 'entry';
    if(e.id) c.id = e.id.replace(/[^a-z0-9\-]/gi, '-');
    if(e.published) {
        c.setAttribute('data-published', e.published);
    }
    if('rank' in e) c.className += ' rank'+e.rank;

    if(e.media) {
        s += '<div class="media">';
        var i=0, img='';
        while(i<e.media.length) {
            img = e.media[i++];
            if(img.indexOf('?')<0 && bird.s && bird.r && img.substr(0, bird.r.length)==bird.r) {
                img = img.replace(/\.(jpe?g|png|gif|tiff?)$/i, '.'+bird.s+'.$1');
            }
            s += '<img src="'+img+'" alt="" title="" />';
        }
        s += '</div>';
    }
    s += '<div class="entry-content">'
        + ((e.title)?('<h3 class="title">'+bird.encodeHtml(e.title)+'</h3>'):(''))
        + ((e.summary)?('<p class="summary">'+bird.encodeHtml(e.summary)+'</p>'):(''))
        + '</div>'
    ;

    c.innerHTML = s;
    return c;
}


})(window.bird);
