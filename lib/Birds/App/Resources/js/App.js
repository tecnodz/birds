(function(bird){
var birdStatus = ['init', 'beforeLoad', 'load', 'afterLoad', 'run', 'beforeClose', 'close', 'afterClose'];
var _birdS = [];
var _birdT = null;
var _ids = 0, _menu={}, _menuToggle={}, _m=[-1,-1], _mv=[0,0], _menuTarget={};
var _ft={};
var _hover={};
var _user=null;
var _first=true;
bird.keepHistory = false;
bird.Apps={}; // used for extending current apps by id
bird.apps = [];
bird.a = {};
bird.b = [];
bird.r = '';
bird.flying = false;
bird.u = null;
bird.auth = '';
bird.onDownload=[];
bird.onUserInfo=[];
bird.t.error403 = 'You don\'t have the credentials required to preview this resource.';
bird.t.cancelButton = 'Cancel';
bird.t.ExitMessage = 'Do you want to exit?';
bird.t.ExitTitle = 'Game Over';
bird.t.ExitButtons = 'No,Yes';

bird.toLoad=[];

bird.dispatchEvent = function(a, ev)
{
    //console.log('dispatchEvent', a, ev);
    var r=true;
    if(!a || !('id' in a)) r=false;
    else if((a.id in bird.Apps) && (ev in bird.Apps[a.id])) {
        r = bird.Apps[a.id][ev].call(a);
    }
    return (r!==false);
}

bird.runApp=function()
{
    //console.log('runApp', bird.flying, this);
    if(!bird.flying) {
        bird.timeout('runApp', 10*1000);
        return;
    }

    //console.log('runApp', this);
    var os=[], i=0, id;
    if('getAttribute' in this) {
        //this.removeEventListener(bird.transitionEnd, bird.runApp);
        os.push(this);
        checkAppStatus();
    } else {
        os=document.getElementsByClassName('app-active');
    }
    for(i=os.length -1; i>=0; i--) {
        //console.log('runApp', os[i]);
        id=os[i].getAttribute('id');
        if(id && id in bird.a) {
            if(os[i].className.search(/\s*\bapp-run\b/)<0) {
                os[i].className += ' app-run';
            }
            //console.log('run!',bird.a[id], bird.a[id].run.toString());
            bird.a[id].run.call(bird.a[id]);
        }
    }
    if(bird.toLoad.length>0) bird.fly();
}

bird.fly = function(id, pid) 
{
    if(typeof(id)!='string' || !id) {
        id='';
        if(bird.toLoad.length>0) {
            id = bird.toLoad.shift();
            if(bird.b.length>0 && bird.b[bird.b.length-1] in bird.a) {
                var la=bird.a[bird.b[bird.b.length-1]];
                if(la.apps.indexOf(id)<0) {
                    return false; // app does not exist within its parent
                }
            } else {
                id='';
            }
        }
        if(!id) {
            if(bird.b.length>0) {
                id=bird.b[bird.b.length-1];
            } else if(bird.apps.length>0) {
                id=bird.apps[bird.apps.length-1];
            } else {
                id='app';
            }
        }
    }
    if(id in bird.a) {
        bird.a[id].fly(pid);
    } else {
        if(_first) {
            _first = false;
            bird.flying = true;
            if(window.location.hash) {
                bird.toLoad = window.location.hash.replace(/^#\/?/, '').split('/');
            }
            if(bird.onReady.length>0) {
                var i=bird.onReady.length;
                while(i-- > 0) {
                    if(bird.onReady[i]==bird.fly) {
                        bird.onReady.splice(i, 1);
                        break;
                    }
                }
                bird.ready();
            }
            _healthStatus = setInterval(checkHealth, 5000);
        }
        if(pid) {
            bird.wget(id+'.json', bird.startApp, bird.error, 'json', document.getElementById(pid));
        } else {
            bird.wget(id+'.json', bird.startApp, bird.error, 'json');
        }
    }
    //window.app = new bird.App();
};
bird.onReady.push(bird.fly);

bird.land = function(id)
{
    //console.log('bird.land: '+id, bird.b);
    if(typeof(id)!='string' || !id) {
        id=bird.b[bird.b.length -1]; // exit if there's no parent
    }
    //console.log('bird.land: '+id);
    if(document.getElementsByClassName('app-overlay').length>0) {
        bird.closeOverlay();
    } else if(bird.b.length==1) {
        bird.confirm(bird.t('ExitMessage'), bird.exit, bird.t('ExitTitle'), bird.t('ExitButtons'));
    } else {
        bird.appStatus(bird.a[id], 'run');
    }
}

bird.confirm = function(m, fn, title, btn){
    if(navigator && 'notification' in navigator && 'confirm' in navigator.notification) {
        navigator.notification.confirm(m, fn, title, btn);
    } else {
        if(window.confirm(m)) fn();
    }
}

bird.exit = function(){
    var id='';
    if(bird.b.length>0) {
        id=bird.b[0];
        bird.appStatus(bird.a[bird.b[0]], 'run');
    }
    for(var n in bird.timeoutQueue) {
        clearTimeout(n);
        delete(bird.timeoutQueue[n]);
    }
    clearInterval(_healthStatus);
    _healthStatus=null;
    _first = true;
    bird.flying = false;
    if(_w) {
        _w.close();
        _w = null;
    }
    var c=document.createElement('div');
    c.innerHTML=bird.t('Bye');//+'<button onclick="bird.removeElement(document.getElementsByClassName(\'app-exit\')[0]);bird.fly(\''+id+'\');return false;">'+bird.t('Restart')+'</button>';
    c.className='app-exit';
    document.getElementsByTagName('body')[0].appendChild(c);
    c=null;

    var apps=document.getElementsByClassName('app'), i=apps.length;
    while(i-- > 0) {
        bird.removeElement(apps[i]);
    }
    if('device' in navigator && 'exitApp' in navigator.device) navigator.device.exitApp();
    if('app' in navigator && 'exitApp' in navigator.app) navigator.app.exitApp();
};

bird.appStatus = function(app, status)
{
    //console.log('appStatus', arguments, this);
    app.status(status);
};

bird.startApp = function(a)
{
    var app = bird.addApp.call(this,a);
    if(bird.toLoad.length>0) {
        //console.log('applist: ', bird.toLoad);
        bird.fly();
    } else {
        app.fly();
    }
    app = null;
};

bird.addApp = function(a)
{
    var n = (('type' in a) && (a.type in bird))?(a.type):('App'),
        args = ('args' in a)?(a.args):(null);
    var app = new bird[n](a), id = app.id;
    if('apps' in this) this.apps.push(id);
    if(!('version' in this)) app.parent=this.id;
    bird.a[id]=app;
    n=null;
    args=null;
    app = null;
    return bird.a[id];
}

bird.downloadApp = function(d)
{
    var i=d.indexOf('<body'), h=d.substr(0,i).replace(/\s*\n\s*/g, ''), s='<div'+d.substr(i+5,d.indexOf('</body>')-i-5)+'</div>',a=[], b='', base=this.uri.replace(/[^\/]+$/, '');
    i=2;
    if(base)
        s = s.replace(/(<img [^\>]*src=\")([^\/][^\"]+)(\"[^\>]*>)/gi, '$1'+base+'$2$3');
    while(a=h.match(/(.*)<link\s([^\>]*\s?type=\"text\/css\"[^\>]*)\/?>/)) {
        h=h.substr(a[0].length);
        if(a=a[2].match(/href=\"([^\"]+)\"/)) {
            b=a[1];
            if(base && b.substr(0,1)!='/')
                b=base+b;
            s+='<style type="text/css">@import "'+b+'";</style>';
        }
        if(i--<0) break;
    }
    var app=document.getElementById('data-'+this.id);
    if(!app) {
        a = document.getElementById(this.id).getElementsByClassName('app-container');
        if(a && a.length>0) app = a[0];
        a=null;
    }
    if(!app) {
        app = document.createElement('div')
        app.id = 'data-'+this.id;
        app.className = 'app-data app-container';
        app.innerHTML = s;
        app = document.getElementById(this.id).appendChild(app);
    } else {
        if(app.className.search(/\bapp-data\b/)<0) app.className+=' app-data';
        app.id = 'data-'+this.id;
        app.innerHTML = s;
    }
    if(window.getComputedStyle) {
        window.getComputedStyle(app).getPropertyValue('top');
    }
    a=app.getElementsByTagName('a');
    i=a.length;
    while(i-- > 0) {
        if(!a[i].getAttribute('target') && a[i].getAttribute('href')) {
            a[i].addEventListener('click', bird.inAppPreview);
        }
    }
    a=app.getElementsByTagName('script');
    i=-1;
    while(++i < a.length) {
        (new Function(a[i].innerHTML))();
    }

    if(bird.onDownload.length>0) {
        i=0;
        while(i<bird.onDownload.length) {
            bird.onDownload[i].call(this, app);
            i++;
        }
    }
    app=null;
}

bird.createOverlay = function(s, id, cn)
{
    var c, update=false;
    if(id) {
        c=document.getElementById(id);
        if(c) update=true;
    }
    if(!c) {
        c = document.createElement('div');
        if(id) c.id = id;
        c.className = (cn)?('app-overlay '+cn):('app-overlay');
    }
    c.innerHTML = '<div class="app-overlay-container app-centered">'+s+'</div>';
    if(!update) {
        c = document.body.appendChild(c);
    }
    if(window.getComputedStyle) {
        window.getComputedStyle(c).getPropertyValue('top');
    }
    c.className+=' app-loaded';
    // check cancel buttons
    var b=c.getElementsByClassName('app-overlay-cancel'), i=b.length;
    if(b.length==0) {
        b=[];
        b[0] = document.createElement('button');
        b[0].className='app-overlay-cancel';
        b[0].innerHTML = bird.t('cancelButton');
        b[0] = c.children[0].appendChild(b[0]);
        i=1;
    }
    while(i-- > 0) {
        b[i].addEventListener('click', bird.closeOverlay);
        b[i]=null;
    }
    b=c.getElementsByTagName('a');
    i=b.length;
    while(i-- > 0) {
        if(!b[i].getAttribute('target') && b[i].getAttribute('href')) {
            b[i].addEventListener('click', bird.inAppPreview);
        }
        b[i]=null;
    }
    //bird.flying = false;
    document.getElementsByTagName('html')[0].className += ' overlay';
    return c;
}

bird.alertTimeout=3000;
bird.alert=function(d, cn)
{
    //console.log('alert: ', d, cn);
    if(d) {
        var s, c='app-message'+((cn)?(' '+cn):(''));
        if(typeof d=='string'){
            s = d;
        } else if('error' in d) {
            s = d.error;
            c += ' app-error';
        }
        s = '<div class="'+c+'">'+s+'</div>';
        bird.createOverlay(s, 'app-alert');
        bird.timeout('alert', bird.alertTimeout);
    } else {
        bird.closeOverlay('app-alert');
    }
};

bird.removeElement=function(e)
{
    if(typeof(e)=='string') {
        var o=document.getElementById(e);
        if(o) bird.removeElement(o);
        return;
    }
    var co = Sizzle('video',e), j=co.length;
    if(j>0) {
        while(j-- > 0) {
            co[j].pause();
            co[j].src='';
            co[j]=null;
        }
        if(bird.stream) {
            bird.stream.stop();
            bird.stream=null;
        }
    }
    e.parentNode.removeChild(e);
    e=null;
}


bird.closeOverlay = function(e)
{
    //console.log('closeOverlay:', e);
    var removed=false;
    if(typeof(e)=='string') {
        var r=document.getElementById(e);
        if(r && r.className.search(/\bapp-overlay\b/)>-1) {
            bird.removeElement(r);
        }
        removed = true;
    }
    var o=document.getElementsByClassName('app-overlay'), i=o.length;
    if(i>0) {
        if(!removed) {
            while(i-- > 0) {
                bird.removeElement(o[i]);
                o[i]=null;
                removed = true;
                break;
            }
        }
    }
    if(i==0) {
        //bird.flying = true;
        var h=document.getElementsByTagName('html')[0];
        h.className = h.className.replace(/\s*\boverlay(\-[^\s]+)?\b/g, '');
        if(bird.toLoad.length>0 && typeof e=='object' && e.target && e.target.className.search(/\bapp-overlay-cancel\b/)>-1) {
            //console.log('should this be removed????');
            bird.toLoad=[];
        } else {
            bird.runApp();
        }
    }
    /*
    if(bird.toLoad.length>0 && i==0) {
        console.log('closeOverlay should fly??', i);
        bird.fly();
    }
    */
    return false;
}

var _w = null;
var _we;

bird.inAppPreview = function(e)
{
    e.preventDefault();
    _w = window.open(this.getAttribute('href'), '_blank', 'location=yes');
    if(bird.plugins.indexOf('cordova')>-1) {
        _w.addEventListener('loadstop', inAppLoaded);
        _w.addEventListener('exit', inAppClose);
    } else {
        _w.addEventListener('load', inAppLoaded);
        _w.addEventListener('close', inAppClose);
    }
    bird.onReady.push(inAppClose);
    var we;
    if(we=this.getAttribute('data-endpoint')) {
        _we = we.split(',');
        _we.push(window.location.href);
    } else {
        _we = [window.location.href];
    }

    //console.log('inAppPreview', e, this);
}

function inAppClose()
{
    //console.log('inAppClose');
    if(_w) _w.close();
    _w = null;
    //bird.runApp();
    refreshUserInfo();
    bird.fly();
}

function inAppLoaded(e)
{
    //console.log('inAppLoaded', e, this);
    var url;
    if(e.type && e.type=='load') { // iframe
        //console.log(this.location.href);
        url=this.location.href;
    } else if('url' in e) {
        url = e.url;
    }
    //console.log(url, _we, _we.indexOf(url));
    if(url && _we.indexOf(url)>-1) {
        // close inAppWindow
        inAppClose();
    }
}

bird.App = function(id) {

    // Bird applications should be created using new Bird(id) -- otherwise, we should force it
    if(this==window) {
        return new App(id);
    }
    this.init(id);
};
bird.App.prototype = {
    /**
     * These properties might be set for each App using the json object
     * additionally, each app might receive some additional [args] passed as an array 
     */
    id: "",         // required, how it'll be internally called
    type: "App",    // defined which app instance is to be created
    uri: "",        // where to fetch app
    thumbnails: {}, // list of thumbnails: 0=>icon, 1=>image, 2=>big image -- those will be used from greatest to smallest in given context
    label: "",
    overlay: "",
    content: null,
    loaded: false,
    enableDownload: false,
    automaticDownload: false,
    enableStorage: false,
    persistentStorage: false,
    toggleApps: false,
    internalStatus: null,
    credentials: null,
    parent: null,
    menu: null,
    data: {},

    apps: [],

    init: function(a) {
        this.id=a.id;
        if(document.getElementById(this.id)) {
            this.id='app-'+this.id;
        }
        if('uri' in a) this.uri = a.uri;
        if('label' in a) this.label = a.label;
        if(bird.apps.length==0) { // main app
            if('repository' in a) bird.r = a.repository;
            if('auth' in a) {
                bird.auth = (a.auth.substr(0,1)=='/')?(bird.r+a.auth):(a.auth);
                getUserInfo.call(this);
                //bird.wget(bird.auth, getUserInfo, bird.error, 'json', this);
            }

            if('enableDownload' in a && a.enableDownload) bird.App.prototype.enableDownload = true;
            if('automaticDownload' in a && a.automaticDownload) bird.App.prototype.automaticDownload = true;
            if('enableStorage' in a && a.enableStorage) bird.App.prototype.enableStorage = true;
            if('persistentStorage' in a && a.persistentStorage) bird.App.prototype.persistentStorage = true;
        } else {
            if('enableDownload' in a && a.enableDownload) this.enableDownload = true;
            if('automaticDownload' in a && a.automaticDownload) this.automaticDownload = true;
            if('enableStorage' in a && a.enableStorage) this.enableStorage = true;
            if('persistentStorage' in a && a.persistentStorage) this.persistentStorage = true;
        }
        if('credentials' in a) this.credentials=a.credentials;
        this.toggleApps = ('toggleApps' in a)?(a.toggleApps):(false);

        this.data={};
        if('apps' in a) {
            if('menu' in a) this.menu = a.menu;
            var i=0, l=a.apps.length;
            this.apps=[];
            while(i<l) {
                bird.addApp.call(this, a.apps[i]);
                i++;
            }
        }


        //this.status('init');
        // this should set the above parameters that are on json to the this context
    },

    fly: function(pid)
    {
        //console.log('fly: ', this.id, this.internalStatus, this.apps);
        if(!checkAuth.call(this)) {
            return false;
        }
        if(bird.b[bird.b.length -1]==this.id) {
            if(pid && (pid in bird.a) && bird.a[pid].toggleApps && this.status()=='run') {
                this.status('run');
            } else {
                if(this.internalStatus=='run') return this.run();
                return true;
            }
        }
        if(pid) this.parent = pid;
        if(this.beforeLoad) {
            this.beforeLoad();
        } else {
            this.load();
        }
        return false;
    },

    status: function(s)
    {
        //console.log('-->status: '+s+' <-- '+this.id);
        if(!(typeof(s)=='string')) {
            s=this.internalStatus;
        }
        if(s && s+'End' in this) {
            this[s+'End']();
        }
        if(s && this.id in bird.Apps && s+'End' in bird.Apps[this.id]) {
            bird.Apps[this.id][s+'End'].call(this);
        }
        if(s) {
            var i=$.inArray(s, birdStatus) +1;
            if(i<birdStatus.length && this[birdStatus[i]]) {
                this.internalStatus=birdStatus[i];
                this[this.internalStatus]();
            }
            i=null;
        }
    },

    beforeLoad: function()
    {
        var app, ev='beforeLoad';
        if(!bird.dispatchEvent(this, ev)) {
            return false;
        }
        if(!(app=document.getElementById(this.id))) {
            app = document.createElement('div')
            app.id = this.id;
            app.className = 'app app-'+this.type+' app-unloaded';
            app.addEventListener('appLoad', this.afterLoad, true);
            this.internalStatus=ev;
            var papp;
            if(this.parent && (papp=document.getElementById(this.parent))) {
                papp.appendChild(app);
            } else {
                bird.root.appendChild(app);
            }

            this.internalStatus=ev;
            // set ready for animations
            if(window.getComputedStyle) {
                window.getComputedStyle(app).getPropertyValue('top');
                this.status(ev); // direct status update
            } else {
                birdStatusUpdate(this);
            }
            app=null;
            // this enables the dom to be refreshed, and animations launched
        } else {
            if(app.className.search(/\bapp-unloaded\b/)<0) {
                app.className = app.className.replace(/\s*\bapp-loaded\b/g, ' app-unloaded');
            }
            // force style refresh
            if(window.getComputedStyle) {
                window.getComputedStyle(app).getPropertyValue('top');
            }
            app = null;
            this.status(ev); // direct status update
            // alternatives
            //this.internalStatus='beforeLoad';this.status();
            //bird.appStatus(this, 'beforeLoad');
        }
    },

    load: function()
    {
        // add some content
        var ev='load';
        if(!bird.dispatchEvent(this, ev)) {
            return false;
        }
        var al=this.apps.length,i, s='<div class="app-container">'+((this.content)?(this.content):(''))+'</div>';
        if(al) {
            s+='<div class="apps app-thumbs">';
            i=0;
            while(i<al){
                s += '<a class="app-thumb app-thumb-'+this.apps[i]+' thumb-'+bird.a[this.apps[i]].type+'" onclick="return bird.fly(\''+this.apps[i]+'\', \''+this.id+'\')">'
                   +   ((bird.a[this.apps[i]].label)?('<span class="app-thumb-title">'+bird.a[this.apps[i]].label+'</span>'):(''))
                   + '</a>';
                i++;
            }
            s+='<a class="app-thumb app-thumb-back" onclick="return bird.land();"></a>';
            s+='</div>';
        }
        al=null;
        i=null;
        if(bird.b.length>0) {
            s+='<a class="app-thumb app-close" onclick="return bird.land(\''+this.id+'\');"></a>';
        }
        i=bird.b.indexOf(this.id);
        if(i>-1) {
            if(i<bird.b.length-1) {
                var r=bird.b.splice(i+1, bird.b.length -1 -i);
                i=r.length;
                while(i-- > 0){
                    if(r[i] in bird.a) {
                        bird.a[r[i]].status('run'); // close
                    }
                }
            }
        } else {
            bird.b.push(this.id); // breadcrumb
        }

        var h=window.location.hash+'/', m=h.indexOf('/'+this.id+'/');
        if(m>-1) {
            // close apps in h
            window.location.hash = window.location.hash.substr(0,m)+'/'+this.id;
        } else {
            window.location.hash+='/'+this.id;
        }
        m = h = null;

        var app=document.getElementById(this.id);
        if(s) {
            app.innerHTML=s;
        }

        if(this.uri) {
            bird.wget(this.uri, bird.downloadApp, bird.error, null, this);
        }
        this.loaded = true;
        this.status.call(this, ev); // direct status update
    },

    afterLoad: function()
    {
        var app, ev='afterLoad';
        if(!bird.dispatchEvent(this, ev) || !this.loaded || !(app=document.getElementById(this.id))) {
            return false;
        }
        if(this.apps.length>0) { //  && 'ontouchmove' in app
            var t=app.getElementsByClassName('app-thumbs')[0];
            if(this.menu) {
                makeMenu.call(t, this.menu);
                //t.ontouchmove = bird.noEvent;
            }
        }
        if(app.className.search(/\bapp-loaded\b/)<0) {
            app.className = 'app app-'+this.type+' app-loaded';
        }
        bird.timeout('runApp', 0, app);
        /*
        if(bird.transitionEnd) {
            app.addEventListener(bird.transitionEnd, bird.runApp, false);
        } else {
            setTimeout(bird.runApp, 1000);
        }
        */



        app = null;
        //this.status(ev); // direct status update
        return true;
    },

    run: function(){
        if(!bird.dispatchEvent(this, 'run')) {
            return false;
        }
    },

    beforeClose: function()
    {
        if(!bird.dispatchEvent(this, 'beforeClose')) {
            return false;
        }
        var app=document.getElementById(this.id);
        app.className = 'app app-'+this.type+' app-closed';
        bird.timeout(closeApp, 0, app);
        /*
        if(bird.transitionEnd) {
            app.addEventListener(bird.transitionEnd, closeApp, false);
        } else {
            setTimeout(closeApp, 1000);
        }
        */
        app=null;

        var h=window.location.hash+'/', m=h.indexOf('/'+this.id+'/');
        if(m>-1) {
            h=window.location.hash.substr(m+this.id.length+1);
            // close apps in h
            window.location.hash = window.location.hash.substr(0,m);
        }
        bird.removeItem(bird.b, this.id);
        //this.status('beforeClose');
    },
    close: function()
    {
        var ev='close';
        if(!bird.dispatchEvent(this, ev)) {
            return false;
        }
        this.status(ev);
    },
    afterClose: function()
    {
        var ev='afterClose';
        if(!bird.dispatchEvent(this, ev)) {
            return false;
        }
        var app=document.getElementById(this.id);
        if(app) {
            app.parentNode.removeChild(app);
            app=null;
        }
        this.status(ev);
    }
};
bird.Epub = bird.App;

function checkAuth()
{
    if(this.credentials) {
        // check if app can run, or if authentication overlay is needed
        var auth=false;
        if(_user && 'name' in _user) {
            if(typeof this.credentials == 'object') { // should be a list of credentials required -- if the user has at least one, it'll pass
                if('credentials' in _user) {
                    var ui=_user.credentials.length;
                    while(ui-- > 0) {
                        if(this.credentials.indexOf(_user.credentials[ui])>-1) {
                            auth=true;
                            break;
                        }
                    }
                }
            } else {
                auth=true;
            }
        }
        if(!auth) {
            // add this app to the list of apps to run and pop up the getUserInfo content
            //console.log('checkAuth: '+this.id+ ' needs auth', this.credentials, _user);
            bird.toLoad.unshift(this.id);
            getUserInfo();
            return false;
        }
    }
    return true;
}

bird.user = function()
{
    if(_user && _user.uid) {
        bird.user.id=_user.id;
        bird.user.name=_user.name;
        bird.user.favorites=('favorites' in _user)?(_user.favorites):([]);
        return {id: _user.uid, name: _user.name, favorites: bird.user.fav };
    } else {
        bird.user.id=null;
        bird.user.name=null;
        bird.user.favorites=[];
        return false;
    }
}
bird.user.id=null;
bird.user.name=null;
bird.user.favorites=[];

function getUserInfo(d)
{
    //console.log('getUserInfo', d);
    var c=document.getElementById('app-user-auth');
    if(d) {
        if(bird.Storage && bird.Storage.enabled && !('cache' in d && d.cache)) {
            d.cache=true;
            bird.Storage.put(bird.auth, d, null, null, 'json', this);
            d.cache=null;
            delete d.cache;
        }
        if(bird.onUserInfo.length>0) {
            var i=0;
            while(i<bird.onUserInfo.length) {
                bird.onUserInfo[i].call(bird, d);
                i++;
            }
        }
        //console.log('Got user info!', d);
        _user = d;
        bird.user();
        document.getElementsByTagName('html')[0].className = document.getElementsByTagName('html')[0].className
            .replace(/\s*\b(authenticated|anonymous)\b/g, ('name' in _user)?(' authenticated'):(' anonymous'));
        if(!('content' in _user) || !_user.content) _user.content = '<p class="error">'+bird.t.error403+'</p>';
        if(c && bird.toLoad.length>0) {
            bird.closeOverlay('app-user-auth');
            bird.fly();
            return;
        }
    }
    if(!_user) {
        return bird.wget(bird.auth, getUserInfo, bird.error, 'json', this);
    } else if(d && 'cache' in d && d.cache) {
        return refreshUserInfo.call(this);
    }
    if(c || !d) {
        c = bird.createOverlay(_user.content, 'app-user-auth');
        bird.onReady.push(refreshUserInfo);
    }
}
bird.login=function(){
    getUserInfo();
}

function refreshUserInfo()
{
    //console.log('refresh user info!!!');
    bird.wget(bird.auth+'?t='+(new Date().getTime()), getUserInfo, bird.error, 'json', this);
}

function birdStatusUpdate()
{
    if(arguments.length>0 && typeof(arguments[0])=='object') {
        var i=0, l=arguments.length;
        while(i<l) {
            _birdS.push(arguments[i]);
            i++;
        }
        i=null;
        l=null;
        if(!_birdT) {
            _birdT=setTimeout(birdStatusUpdate, 10); // can't use requestAnimationFrame, yet!
        }
        return;
    }
    if(_birdT) {
        _birdT=null;
    }
        app=null;
    var app;
    while(_birdS.length>0) {
        app = _birdS.shift();
        app.status(app.internalStatus);
    }
}

function getId(o)
{
    var id=o.getAttribute('id');
    if(!id) {
        id = 'b'+(_ids++);
        o.setAttribute('id', id);
    }
    return id;
} 

function getMenuItem(id, e)
{
    var c;
    //console.log('getMenuItem: '+id, _m);
    if(_m[0]>=0 && _m[1]>=0 && id in _menu) {
        var f=_menu[id], i=f.length, el=(typeof(e)=='string')?(document.getElementById(id)):(e.target);
        var x = _m[0], y=_m[1];
        if(id in _menuTarget) {
            if(el.offsetWidth==_menuTarget[id][2] && el.offsetHeight==_menuTarget[id][3]) {
                x -= el.offsetLeft - _menuTarget[id][0];
                y -= el.offsetTop - _menuTarget[id][1];
            } else {
                makeMenu.call(el);
            }
        }
        el=null;
        while(i-- > 0) {
            if(f[i][0] <= x && x <= f[i][2] && f[i][1] <= y && y <= f[i][3]) {
                c=document.getElementById(f[i][4]);
                break;
            }
        }
    }
    return c;
}

function makeMenu(slide)
{
    //console.log('makeMenu on '+slide, this);
    var id=getId(this), c=Sizzle('>.app-thumb',this),i=c.length,f=[];
    if(!this.getBoundingClientRect) {
        var x=0, y=0, p;
        p = this;
        while(p && !isNaN( p.offsetLeft ) && !isNaN( p.offsetTop )) {
            x+= p.offsetLeft - p.scrollLeft;
            y+= p.offsetTop - p.scrollTop;
            p=p.offsetParent;
        }
        while(i-- > 0) {
            f.push([x+c[i].offsetX, y+c[i].offsetY, x+c[i].offsetX+c[i].offsetWidth, y+c[i].offsetY+c[i].offsetHeight, getId(c[i]),0]);
        }
    } else {
        var p;
        while(i-- > 0) {
            p=c[i].getBoundingClientRect();
            f.push([p.left, p.top, p.right, p.bottom, getId(c[i]),0]);
        }
    }
    if(!(id in _menu)) {
        // this.onmousedown = 
        this.onmousemove = this.onmouseup = menuHover;
            this.ontouchstart = 
            this.ontouchmove = this.ontouchend = menuHover;
    }
    if(slide && slide.search(/^(top|left|right|bottom)$/)>-1) {
        _menuToggle[id] = slide;
    }
    _menu[id] = f;
    _menuTarget[id]=[this.offsetLeft, this.offsetTop, this.offsetWidth, this.offsetHeight];
    //console.log('made menu', _menu, _menuTarget, this.offsetWidth, this.offsetHeight);
}
function getPointer(e) {
    var id, x=_m[0], y=_m[1];

    if(e.type.substr(0,5)=='touch') {
        if(e.touches && e.touches.length>0) {
            _m=[e.touches[0].clientX, e.touches[0].clientY ];
        }
        id=e.target.getAttribute('id');
    } else {
        id=e.target.getAttribute('id');
        _m=[e.pageX, e.pageY];
    }
    if(e.target && id && id in _menuToggle && x > 0 && y > 0) {
        x = _m[0] - x;
        y = _m[1] - y;
        _mv[0] = (x == 0 || (_mv[0] <= 0 && x < 0)||(_mv[0] >= 0 && x > 0))?(_mv[0]+x):(0);
        _mv[1] = (y == 0 || (_mv[1] <= 0 && y < 0)||(_mv[1] >= 0 && y > 0))?(_mv[1]+y):(0);
        var d=_menuToggle[id], prop=(e.target.getBoundingClientRect)?(e.target.getBoundingClientRect()):({w:e.target.offsetwidth,h:e.target.offsetHeight});
        if(!('w' in prop)) {
            prop.w = prop.right - prop.left;
            prop.h = prop.bottom - prop.top;
        }

        if((d=='left' || d=='right') && Math.abs(_mv[1]) <10 && Math.abs(_mv[0]) > prop.w*0.3) {
            if((d=='left' && _mv[0] > 0)||(d=='right' && _mv[0]<0)) {
                if(e.target.className.search(/\bactive\b/)<0) e.target.className+=' active';
            } else if(e.target.className.search(/\bactive\b/)>-1) {
                e.target.className=e.target.className.replace(/\s*\bactive\b/, '');
            }
        } else if((d=='top' || d=='bottom') && Math.abs(_mv[0]) <10 && Math.abs(_mv[1]) > prop.h*0.3) {
            if((d=='bottom' && _mv[0] > 0)||(d=='top' && _mv[0]<0)) {
                if(e.target.className.search(/\bactive\b/)<0) e.target.className+=' active';
            } else if(e.target.className.search(/\bactive\b/)>-1) {
                e.target.className=e.target.className.replace(/\s*\bactive\b/, '');
            }
        }
    }


    return id;
}

function menuHover(e) {
    if(!e) {
        _m = [-1,-1];
        for(var n in _menu) {
            if(n) menuHover(n);
        }
        return;
    }
    var id;
    if(typeof(e)=='string') {
        id=e;
    } else {
        id=getPointer(e);
        if(e.type=='touchstart') e.preventDefault();
        //if(!e.touches || e.touches.length==0) {
        //}
    }
    if(!id || !(id in _menu)) {
        return;
    }
    var c=getMenuItem(id, e), end=(c && typeof(e)!='string' && (e.type=='mouseup' || e.type=='touchend')), el;
    if(!c || c.className.search(/\bhover\b/)<0) {
        var r;
        if(typeof(e)!='string' & e.target) r=e.target.getElementsByClassName('hover');
        else if(c) r=c.parentNode.getElementsByClassName('hover');
        else {
            if(!el) el=document.getElementById(id);
            if(el) r = el.getElementsByClassName('hover');
            el = null;
        }
        i=(r)?(r.length):(0);
        while(i-- > 0){
            r[i].className = r[i].className.replace(/\s*\bhover\b/, '');
        }
        if(c) c.className += ' hover';
    }
    if(_ft[id]) {
        clearTimeout(_ft[id]);
        delete(_ft[id]);
    }
    if(end) {
        bird.click(c);
        _mv=[-1,-1];
        _ft[id] = setTimeout(menuHover, 500);
    } else {
        _ft[id] = setTimeout(menuHover, 2000);
    }
    return false;
}

function closeApp(e)
{
    //console.log('closeApp', this);
    var os=[], i=0, id;
    if('getAttribute' in this) {
        //this.removeEventListener(bird.transitionEnd, closeApp);
        os.push(this);
    } else {
        os=document.getElementsByClassName('app-closed');
    }
    for(i=os.length -1; i>=0; i--) {
        id=os[i].getAttribute('id');
        if(id && id in bird.a) {
            bird.a[id].close();
        }
    }
    checkAppStatus();
}


function checkAppStatus()
{
    //console.log('checkAppStatus!', bird.b);
    if(bird.b.length>0) {
        var ra = document.getElementById(bird.b[0]), ar=document.getElementsByClassName('app-active'), i=0, id='', cid=bird.b[bird.b.length -1], cs='';
        //console.log(cid+' should be active!');
        if(ra) {
            if(bird.b.length>1) {
                cs = ra.className.replace(/\s*app-active-[^\s]+/g, ' ').replace(/\s{2,}|\s*$/g, ' ')+'app-active-'+cid;
                if(ra.className!=cs)ra.className=cs;
                ra = null;
                ra = document.getElementById(cid);
                if(ra) {
                    cs = ra.className.replace(/\s*\bapp-(in)?active\b/g, ' ').replace(/\s{2,}|\s*$/g, ' ')+'app-active';
                    if(ra.className!=cs)ra.className=cs;
                } else {
                    //console.log('could not find '+cid);
                }
            } else {
                cs = ra.className.replace(/\s*\b(app-active-[^\s]+|app-inactive)/g, ' ').replace(/\s{2,}|\s*$/g, ' ')+'app-active-'+cid+' app-active';
                if(ra.className!=cs)ra.className=cs;
            }
        }
        if(ar) {
            for(i=ar.length -1; i>=0; i--) {
                id=ar[i].getAttribute('id');
                if(id && id in bird.a && cid!=id) {
                    cs = ar[i].className.replace(/\s*\bapp-active\b/, ' app-inactive');
                    if(ar[i].className!=cs) ar[i].className=cs;
                }
            }
        }
        //console.log(cid+' --> ', bird.a[cid].toggleApps, bird.a[cid]);
        if(!bird.keepHistory && (!(cid in bird.a) || !bird.a[cid].toggleApps) && bird.b.length>1) {
            if(window.getComputedStyle) {
                window.getComputedStyle(document.getElementById(bird.b[0])).getPropertyValue('top');
            }
            var b = bird.b;
            bird.b = [];
            i=b.length;
            while(i-- > 1) {
                if(b[i]==cid) {
                    bird.b.unshift(cid);
                    continue;
                }
                ra = document.getElementById(b[i]);
                if(ra && ra.className.search(/\bapp-active\b/)<0) {
                    ar = ra.getElementsByClassName('app-active');
                    if(!ar || ar.length==0) {
                        //console.log('closed '+ra.id);
                        bird.a[ra.id].close();
                        continue;
                    }
                }
                if(ra) {
                    bird.b.unshift(ra.id);
                }
            }
            bird.b.unshift(b[0]);
            window.location.hash = '/'+bird.b.join('/');

        }        
    }
}



var _healthStatus=null;
function checkHealth()
{
    // conditions to check if app is running -- if any of these tests pass, then bird.runApp()  
    if(!bird.online || !bird.flying || bird.b.length==0 || _w) {
        return false;
    }
    for(var n in bird.timeoutQueue) {
        return false;
    }
    if(document.getElementsByClassName('app-overlay').length>0) {
        return false;
    }
    console.log('checkHealth... ressurect');
    bird.runApp();
    return true;
}
})(window.bird);


