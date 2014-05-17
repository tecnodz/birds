/*! Bird CMS version v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
(function(bird, B){
bird.css = B.css;
bird.cms={
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
    //_Q = true;
}

function fly()
{
    if(!cms.enabled) return;

    // enable triggers for omnibox
    bird.addEvent(document, 'keypress', omnibox);
    return true;
}

function land(){
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