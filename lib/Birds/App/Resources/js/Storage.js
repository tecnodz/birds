/*!Storage*/
(function(bird){

function enableLocalStorage() {
  try {
    return 'localStorage' in window && window['localStorage'] !== null;
  } catch (e) {
    return false;
  }
}
bird.Storage = function() {
};
bird.Storage.enabled = bird.Storage.enableLocalStorage = enableLocalStorage();
bird.Storage.enableFileSystem = false;
bird.Storage.root = null;

var gotFS = function(fs) {
    console.log('got filesystem at:');
    console.log(fs.name);
    console.log(fs.root.name);
    console.log(fs.root.fullPath);
    bird.Storage.root = fs;
    bird.Storage.enabled = true;
    bird.Storage.enableFileSystem = true;
}

var fsError = function(e) {
    console.log('fsError!!!');
    if(console) console.log(e.target.error.code);
}

var onReady = function() {
    if(window.requestFileSystem)
        window.requestFileSystem(LocalFileSystem.PERSISTENT, 0, gotFS, fsError);
}
bird.onReady.push(onReady);

bird.Storage.put = function(url, data, success, error, dataType, context)
{
    var h, s, dt = ( typeof dataType !== 'undefined' && dataType )?(dataType):('text');
    if(!context) context=this;
    //if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1 && (h=bird.Storage.hash.call(context, url))) {
    if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1) {
        if(dt=='json') s = JSON.stringify(data);//return success.call(context, $.parseJSON(s));
        else if(dt=='xml') s = (typeof(XMLSerializer)!=='undefined')?((new XMLSerializer()).serializeToString(data)):(data.xml);//return success.call(context, $.parseXML(s));
        else if(dt=='html') s = (typeof(XMLSerializer)!=='undefined')?((new XMLSerializer()).serializeToString(data)):(data.outerHTML);//return success.call(context, $.parseHTML(s));
        else s = ''+data;//return success.call(context, s);
        localStorage[url] = s;
        //console.log('bird.Storage.put '+url);
        if(typeof(success)!=='undefined' && success) return success.call(context, url);
        return h;
    }
    if(typeof(error)!=='undefined' && error) return error.call(context, url);
    return false;


    //ls = (dataType && dataType.search(/json|html|text|xml/)>-1) || url.search(/\.)
    // should check if url is cached, if it is, replace ajax url and run
    //$.ajax(ajax);
    //return false;
}

bird.Storage.remove = function(url, success, error, context)
{
    var h, s;
    if(!context) context=this;
    //if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1 && (h=bird.Storage.hash.call(context, url)) && (h in localStorage)) {
    if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1 && (url in localStorage)) {
        localStorage.removeItem(url);
        //console.log('bird.Storage.remove '+url);
        if(typeof(success)!=='undefined' && success) return success.call(context, url);
        return url;
    }
    if(typeof(error)!=='undefined' && error) return error.call(context, url);
    return false;


    //ls = (dataType && dataType.search(/json|html|text|xml/)>-1) || url.search(/\.)
    // should check if url is cached, if it is, replace ajax url and run
    //$.ajax(ajax);
    //return false;
}


bird.Storage.hash = function(url)
{
    if (url.search(bird.reStorable)>-1) {
        var i=url.lastIndexOf('.');
        return bird.hash(url.substr(0,i)).replace(/^\-/, '')+url.substr(i);
    } else {
        return bird.hash(url).replace(/^\-/, '');
    }
}

bird.Storage.Files={};
bird.Storage.get = function(url, success, error, dataType, context)
{
    var h=url, s, dt = ( typeof dataType !== 'undefined' && dataType )?(dataType):('text');
    if(!context) context=this;
    //console.log('check localStorage: '+url, bird.Storage.enableLocalStorage, url.search(bird.reStorableText)>-1, (h=bird.Storage.hash.call(context, url))!='', h, (h in localStorage), (s=localStorage[h]));
    //if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1 && (h=bird.Storage.hash.call(context, url))!='' && (h in localStorage) && (s=localStorage[h])) {
    if(bird.Storage.enableLocalStorage && url.search(bird.reStorableText)>-1 && (url in localStorage) && (s=localStorage[url])) {
        //console.log('bird.Storage.get '+url);
        if(dt=='json') success.call(context, $.parseJSON(s));
        else if(dt=='xml') success.call(context, $.parseXML(s));
        else if(dt=='html') success.call(context, $.parseHTML(s));
        else success.call(context, s);
        return true;
    }
    /*
    if(false && bird.Storage.enableFileSystem && (h || (h=bird.Storage.hash.call(context, url)))) {
        bird.Storage.Files[h]={
            method: 'GET',
            url: url,
            success: success,
            error: ( typeof error !== 'undefined' && error )?( error ):( this.error ),
            dataType: ( typeof dataType !== 'undefined' && dataType )?( dataType ):( null ),
            context: context
        };
        return bird.Storage.root.getFile(h, {create: false, exclusive: false }, loadFile, errorFile)
    }
    */

    //console.log('ajax again....');
    $.ajax({
        method: 'GET',
        url: url,
        success: success,
        error: ( typeof error !== 'undefined' && error )?( error ):( this.error ),
        dataType: ( typeof dataType !== 'undefined' && dataType )?( dataType ):( null ),
        context: context
    });
    context = null;
    return true;
}

function loadFile(f)
{
    console.log('loadFile: '+f.name);
}

function errorFile(e)
{
    console.log('errorFile: '+f.code);
}


})(window.bird);


