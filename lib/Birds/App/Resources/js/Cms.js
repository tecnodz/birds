/*! Bird CMS version v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
(function(bird, B){
bird.css = B.css;
bird.cms={
    iconInactivePage:   bird.css+"icon "+bird.css+"icon-smiley "+bird.css+"inactive",
    iconActivePage:     bird.css+"icon "+bird.css+"icon-grin   "+bird.css+"active",
    iconInactiveContent:bird.css+"icon-round "+bird.css+"icon-pen  "+bird.css+"active",
    iconActiveContent:  bird.css+"icon-round "+bird.css+"icon-pen  "+bird.css+"active",
    buttonElement: "a",
    id:"bird-cms"
}

var _Q;
function omnibox(e)
{
    //e = e || window.event;
    if(_Q) return;
    var c = String.fromCharCode((window.event)?(e.keyCode):(e.which));
    console.log('omnibox: '+c);
    _Q = true;
}

function fly()
{
    if(!cms.enabled) return;

    // enable triggers for omnibox
    bird.addEvent(document, 'keypress', omnibox);
    return true;
}

function land(){
    if(cms.data.birdActive>0) return;
    b=document.querySelectorAll('.bird-b-content');
    var i=b.length;
    while(i-- > 0) {
        b[i].parentNode.className=b[i].parentNode.className.replace(/\s*\bbird-content\b/, '');
        b[i].parentNode.removeChild(b[i]);
        b[i]=null;
        delete b[i];
    }
    delete b;
}

function toggleCms()
{
    var a=(!this.getAttribute('id')!=bird.cms.id)?(document.getElementById(bird.cms.id)):(this);
    if(cms.data.birdActive>0) {
        // make inactive
        cms.data.birdActive=0;
        a.className=bird.cms.iconInactivePage;
        land();
    } else {
        // make active
        cms.data.birdActive=1;
        a.className=bird.cms.iconActivePage;
        fly();
    }
    delete(a);
}

function toggleContent(e)
{
    console.log('toggleContent', e);
    bird.stopEvent(e);

    var t=this.parentNode.getAttribute('itemid');
    if(!t) return false;

    bird.pop(t, this);
    return false;
}

var cms={
    ready:true,
    enabled: (window.location.href.indexOf(B.cms)!==0),
    data:(enableLocalStorage())?(window.localStorage):({}),
    base: B.cms,
};

function enableLocalStorage() {
  try {
    return 'localStorage' in window && window['localStorage'] !== null;
  } catch (e) {
    return false;
  }
}


bird.onReady.push(fly);
})(window.bird, window.Bird);