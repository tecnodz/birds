/*! Bird CMS version v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
(function(bird, B){

bird.cms={
    iconInactivePage:   "fa fa-toggle-right bird-inactive",
    iconActivePage:     "fa fa-toggle-right bird-active",
    iconInactiveContent:"fa fa-toggle-left  bird-inactive",
    iconActiveContent:  "fa fa-toggle-left  bird-active",
    buttonElement: "a",
    id:"bird-cms"
}

function fly()
{
    var b = Sizzle('*[itemtype="http://schema.org/WebPage"][itemscope][itemid]');
    if(b.length==0) {
        delete(b);
        return;
    }
    var ba=Sizzle('#'+bird.cms.id,b[0]);
    if(ba.length==0) {
        bird.createElement(
            b[0], 
            bird.cms.buttonElement, 
            bird.cms.id, 
            (cms.data.birdActive>0)?(bird.cms.iconActivePage):(bird.cms.iconInactivePage),
            false,
            toggleCms
        );
    } else {
        ba[0].className=(cms.data.birdActive>0)?(bird.cms.iconActivePage):(bird.cms.iconInactivePage);
    }
    delete(ba);
    b=null;

    if(cms.data.birdActive<=0) return;

    //console.log('Bird flying! at '+B.cms);
    b=Sizzle('*[itemscope][itemid]');
    var i=b.length;
    while(i-- > 0) {
        bird.createElement(
            b[i], 
            bird.cms.buttonElement, 
            false, 
            bird.cms.iconInactiveContent+' bird-b-content',
            false,
            toggleContent
        );
        if(b[i].className.search(/\bbird-content\b/)<0) b[i].className+=' bird-content';
        //b[i].parentNode.insertBefore(a, b[i].nextSibling);
        b[i]=null;
        delete b[i];
    }
    delete b;
}

function land(){
    if(cms.data.birdActive>0) return;
    b=Sizzle('.bird-b-content');
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

function toggleContent()
{
    var t=this.parentNode.getAttribute('itemid');
    if(!t) return false;

    console.log('content '+t+'!!!');
}

var cms={
    ready:true,
    data:(enableLocalStorage())?(window.localStorage):({})
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