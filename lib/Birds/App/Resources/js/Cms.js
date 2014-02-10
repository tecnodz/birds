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
function fly()
{
    if(!cms.enabled) return;
    var b = document.querySelector('[itemtype="http://schema.org/WebPage"][itemscope][itemid]');
    if(!b) {
        return;
    }
    var ba=document.getElementById(bird.cms.id);
    if(!ba) {
        bird.createElement(
            b, 
            bird.cms.buttonElement, 
            bird.cms.id, 
            (cms.data.birdActive>0)?(bird.cms.iconActivePage):(bird.cms.iconInactivePage),
            false,
            toggleCms
        );
    } else {
        ba.className=(cms.data.birdActive>0)?(bird.cms.iconActivePage):(bird.cms.iconInactivePage);
    }
    delete(ba);
    b=null;

    if(cms.data.birdActive<=0) return;

    //console.log('Bird flying! at '+B.cms);
    b=document.querySelectorAll('[itemscope][itemid]');
    var i=b.length;
    while(i-- > 0) {
        bird.createElement(
            b[i], 
            bird.cms.buttonElement, 
            false, 
            bird.cms.iconInactiveContent+' bird-b-content',
            false,
            toggleContent
        ).setAttribute('href', b[i].getAttribute('itemid'));
        if(b[i].className.search(/\bbird-content\b/)<0) b[i].className+=' bird-content';
        //b[i].parentNode.insertBefore(a, b[i].nextSibling);
        b[i]=null;
        delete b[i];
    }
    delete b;
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