/*!Feed*/
(function(bird){



var _feeds={};


var _sort='published';


bird.onDownload.push(feedCheck);
bird.Feed = function(d) 
{
    //console.log('bird.Feed');
    if(!d) d={};
    bird.App.call(this, d);
    if('data' in d) this.data = d.data;
    else if(!this.data) this.data={};
    if('feed' in d) this.data.feed=d.feed;
    if(this.data.feed.substr(0,1)=='/' && bird.r) {
        this.data.feed = bird.r + this.data.feed;
    }
    _feeds[this.data.feed] = false;
    if(!this.data.context) this.data.context = this.id;
    if('callback' in d) {
        if(typeof d.callback == 'string') {
            if(this.id in bird.Apps && d.callback in bird.Apps[this.id]) 
                this.data.callback = bird.Apps[this.id][d.callback];
            else if(d.callback in bird.Feed)
                this.data.callback = bird.Feed[d.callback];
            else
                this.data.callback = null;
        }
    }
};
var _tryAgain=100;
bird.Feed.thumbnailSize = null;
bird.Feed.prototype = Object.create(bird.App.prototype);
bird.Feed.prototype.constructor = bird.Feed;
bird.Feed.prototype.type = 'Feed';
bird.Feed.prototype.run = function(data)
{
    //console.log('bird.Feed.prototype.run: '+this.id+':'+this.data.context, data);
    if(!('feed' in this.data && this.data.feed)) {
        return false;
    }
    var u = this.data.feed, p=[], firstRun=true, now=new Date().getTime();
    _feeds[u]=false;
    if(data) { //feed
        // process feed in s
        firstRun=false; // must check
        var c = document.getElementById(this.data.context), s, se, w=false, r=false, next = this.data.interval || 0, changed=false;
        if(this.data.context==this.id || (!c && this.data.context==this.id+'-container')) {
            if(this.data.context==this.id) this.data.context=this.id+'-container';
            if(!c) {
                c=document.getElementById(this.id);
            }
            c=c.children[0];
            c.setAttribute('id', this.data.context);
            //console.log('feed in ', c);
            c.setAttribute('data-feed', this.data.feed);
            if(!('count' in this.data) || !this.data.count) {
                // add infinite scrolling
                c.addEventListener('scroll', feedCheckScroll);
            }
            firstRun=true;
        } else if(!c) {
            window.getComputedStyle(document.getElementById(this.id)).getPropertyValue('top');
            c=document.getElementById(this.data.context);
            if(!c) {
                //console.log('oh oh', this.data.context, document.getElementById(this.data.context));
                bird.timeout(bird.Feed.prototype.run, _tryAgain, this, arguments);
                _tryAgain *= 2;
                return;
            }
        }
        _tryAgain=100;
        if(firstRun || !('feedStart' in this.data) || this.data.feedStart!=bird.created.getTime()) {
            //console.log('first run, is it from cache?', this.data);
            firstRun = true;
            this.data.feedStart = bird.created.getTime();
            if(!('o' in this.data)) this.data.o = data;
            if(!('item' in data)) {
                if('cache' in data && data.cache) data.updated = false; // some bug, shouldn't have 0 items if cached
                this.data.o.item=[];
            }
            if(!('cache' in data) || !data.cache) {
                //console.log('not from cache, please store');
                w = true;
            } else {
                next = (next)?(next*0.5):(1);
            }
            if('content' in data) {
                c.innerHTML = data.content; 
            } else if(this.data.o.item.length>0) {
                c.innerHTML = '';
            }
            if('published' in data) {
                this.data.p = data.published;
            }
            if('modified' in data) {
                this.data.m = data.modified;
            }
        } else if('reset' in data && data.reset) {
            //console.log('received a reset request!!!');
            next = 0.5;
            r = true;
            this.data.p = false;
            this.data.o.item=[];
            if('content' in data) {
                c.innerHTML = data.content; 
            } else if(this.data.o.item.length>0) {
                c.innerHTML = '';
            }
            c = null;
        } else {
            if('published' in data) {
                this.data.p = data.published;
                if(this.data.o.published != data.published) {
                    //console.log('was recently published, should write the cache: '+this.data.o.published+' != '+data.published);
                    this.data.o.published = data.published;
                    w = true;
                } 
            }
            if('modified' in data) {
                this.data.m = data.modified;
                if(this.data.o.modified != data.modified) {
                    //console.log('was recently modified, should write the cache'+this.data.o.modified+' != '+data.modified);
                    this.data.o.modified = data.modified;
                    w = true;
                } 
            }
        }
        if('callback' in this.data && typeof this.data.callback == 'string') {
            if(this.id in bird.Apps && this.data.callback in bird.Apps[this.id]) 
                this.data.callback = bird.Apps[this.id][this.data.callback];
            else if(this.data.callback in bird.Feed)
                this.data.callback = bird.Feed[this.data.callback];
            else
                this.data.callback = null;
        }

        if(c) {
            var l=[], i=0, cc, ids={}, id, pi=this.data.o.item.length;
            if('item' in data && 'length' in data.item) {
                this.data.o.item = uniqueEntries(data.item, this.data.o.item);
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
                        s = fn.call(this, l[i]);
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
                        //replace=document.getElementById(l[i].id);
                        // depending on s/t, data should be appended or prepended
                        if(ref) { // insert or replace before
                            ref = c.insertBefore(se,ref);
                        } else  { // append
                            ref = c.appendChild(se);
                        }
                        /*
                        if(replace) {
                            //console.log('replacing: ', replace, replace.parentNode==c);
                            c.removeChild(replace);
                            replace=null;
                        }
                        */
                        if(this.data.callback) {
                            ref.onclick = this.data.callback;
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

            // set ready for animations
            if(window.getComputedStyle) {
                window.getComputedStyle(c).getPropertyValue('top');
            }
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
            if(changed && !('count' in this.data) || !this.data.count) {
                cc = Sizzle('>.entry:last-child',c);
                if(cc && cc.length>0 && cc[0].offsetTop<c.offsetHeight) { // considerar o total de entradas 
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
                    this.data.p = false;
                    this.data.m = false;
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
            if('last' in this.data) {
                if(this.data.last + (next*1000) < now) {
                    this.data.last = now;
                } else {
                    next -= (now - this.data.last)*0.001;
                }
            } else {
                this.data.last = now;
            }



            //console.log('next request for '+this.id+' in '+next+' seconds');
            bird.timeout('runApp', next*1000);
        }
    } else {
        if('p' in this.data && this.data.p) {
            p.push('p='+this.data.p);
        }
        if('m' in this.data && this.data.m) {
            p.push('m='+this.data.m);
        }
        if(p.length>0) {
            u += (u.indexOf('?')>-1)?('&'):('?');
            u += p.join('&');
        } else if(!firstRun) {
            u += (u.indexOf('?')>-1)?('&'):('?');
            u +=new Date().getTime();
        }
        //console.log('request url for '+this.id+': '+u);
        bird.wget(u, this.run, bird.error, 'json', this);
    }
}


bird.Feed.preview = function(e)
{
    //console.log('bird.Feed.preview', e, this);
    var c=(typeof(e)=='string')?(document.getElementById(e)):(this);
    var id=c.getAttribute('id');
    if(document.getElementById('preview-'+id)) return false;
    var s=c.innerHTML.replace(/\.[a-z0-9]+\.(jpg|png)/ig, '.$1');
    var cn='entry-preview';
    bird.createOverlay(s, 'preview'-c.getAttribute('id'), cn);
    return false;
}

bird.Feed.link = function(e)
{
    //console.log('bird.Feed.link', e, this);
    return false;
}



bird.Entry = function(d) 
{
    bird.App.call(this, d);
    d.id=this.id;
    var c=('Entry' in bird.Apps && 'render' in bird.Apps.Entry)?(bird.Apps.Entry.render(d)):(bird.renderEntry(d));
    if(typeof c=='string') this.content=c;
    else this.content = c.innerHTML;
    c=null;
};
bird.Entry.prototype = Object.create(bird.App.prototype);
bird.Entry.prototype.constructor = bird.Entry;
bird.Entry.prototype.type = 'Entry';




bird.renderEntry = function(e)
{
    console.log('bird.renderEntry');
    var s='', c=document.createElement('div');
    c.className = 'entry';
    if(e.id) c.id = e.id.replace(/[^a-z0-9\-]/gi, '-');
    if(e.published) {
        c.setAttribute('data-published', e.published);
    }
    if(e.modified) {
        c.setAttribute('data-modified', e.modified);
    }
    if(e.link) {
        c.setAttribute('data-link', e.link);
    }

    if(e.media) {
        s += '<div class="media">';
        var i=0, img='', is=(bird.Feed.thumbnailSize)?(bird.Feed.thumbnailSize.call(this,e)):(bird.s);
        c.className += ' media-'+is;
        while(i<e.media.length) {
            img = e.media[i++];

            if(img.indexOf('?')<0 && bird.s && bird.r && img.substr(0, bird.r.length)==bird.r) {
                img = img.replace(/\.(jpe?g|png|gif|tiff?)$/i, '.'+is+'.$1');
            }
            s += '<img src="'+img+'" alt="" title="" />';
        }
        s += '</div>';
    }
    s += '<div class="entry-content">'
        + ((e.title)?('<h3 class="title">'+bird.encodeHtml(e.title)+'</h3>'):(''))
        + ((e.summary)?('<p class="summary">'+bird.encodeHtml(e.summary)+'</p>'):(''))
        + ((e.content)?('<div class="content">'+e.content+'</div>'):(''))
        + '</div>'
    ;

    c.innerHTML = s;
    return c;
}

function feedCheckScroll(e)
{
    //console.log('feedCheckScroll');
    var u=e.target.getAttribute('data-feed');
    if(!u || _feeds[u] || e.target.scrollHeight<=50 || e.target.children.length<1) return;
    var el=e.target;
    //console.log('feedCheckScroll: '+el.scrollHeight+' - '+el.scrollTop+' - '+el.offsetHeight+' < '+el.children[el.children.length-1].offsetHeight, el.scrollHeight - el.scrollTop - el.offsetHeight < el.children[el.children.length-1].offsetHeight, e.target);
    if(el.scrollHeight - el.scrollTop - el.offsetHeight < el.children[el.children.length-1].offsetHeight) {
        _feeds[u]=true;
        var pe=el.parentNode;
        while(pe.nodeName!='BODY' && pe.className.search(/\bapp-active\b/)<0) {
            pe = pe.parentNode;
        }
        var id;
        if(!pe || !(id=pe.getAttribute('id')) || !(id in bird.a)) return;
        var app=bird.a[id], p=[], l=el.children[el.children.length-1].getAttribute('data-published'), t=el.children[0].getAttribute('data-published');
        if('data' in app && 'o' in app.data && app.data.o.count<=app.data.o.item.length) return false;
        if(l && t) {
            p.push('p='+t);
            if(app.data.m) p.push('m='+app.data.m);
            p.push('l='+l);
            u += (u.indexOf('?')>-1)?('&'):('?');
            u += p.join('&');
            //console.log('request url: '+u)
            bird.wget(u, app.run, bird.error, 'json', app);

        }
    }
}
function feedCheck(app)
{
    //console.log('feedCheck', this);
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
                    da[i].addEventListener('scroll', feedCheckScroll);
                }
                if(this.data.callback) {
                    if(typeof this.data.callback == 'string') {
                        if(this.id in bird.Apps && this.data.callback in bird.Apps[this.id]) 
                            this.data.callback = bird.Apps[this.id][this.data.callback];
                        else if(this.data.callback in bird.Feed)
                            this.data.callback = bird.Feed[this.data.callback];
                        else
                            this.data.callback = null;

                        if(!this.data.callback)
                            return c;
                    }
                }
                if(this.internalStatus=='run') this.run(); // already ran
            }

        }
    }
};
function sortEntry(a,b)
{
    var da = ('getAttribute' in a)?(new Date(a.getAttribute('data-'+_sort))):(new Date(a[_sort]));
    var db = ('getAttribute' in b)?(new Date(b.getAttribute('data-'+_sort))):(new Date(b[_sort]));
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
    else c=a.concat(b);
    var r=[], d={}, i=c.length, id, n=false;
    while(i-- > 0) {
        if(!c[i]) continue;
        n = ('getAttribute' in c[i]);
        id = (n)?(c[i].getAttribute('id')):(c[i].id);
        if(id in d) {
            if(!n && (d[id][0]!=c[i].published || d[id][1]!=c[i].modified)) {
                // replace HTML node with updated element
                //console.log('replacing '+id, d[id], r, r[d[id][2]], c[i]);
                if('getAttribute' in r[d[id][2]]) {
                    bird.removeElement(r[d[id][2]]);
                }
                r[d[id][2]]=c[i];
                d[id][0]=c[i].published;
                d[id][1]=c[i].modified;
            }
            continue;
        }
        d[c[i].id]=[(n)?(c[i].getAttribute('data-published')):(c[i].published),(n)?(c[i].getAttribute('data-modified')):(c[i].modified),r.length];
        r.push(c[i]);
    }
    c=null;
    d=null;
    return r.sort(sortEntry);
}

})(window.bird);
