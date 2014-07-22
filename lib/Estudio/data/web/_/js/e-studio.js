/*! 
 * Bird CMS version v0.1 | (c) 2014 Capile Tecnodesign <ti@tecnodz.com> 
 */
(function(){
var c=0;
var _Q;
function omnibox(e)
{
    e = e || window.event;
    if(e.keyCode==27) return disableSpace();
    else if(_Q || (e.target && e.target.nodeName.search(/input|textarea|select|button/i)>-1)) return;
    var c = String.fromCharCode((window.event)?(e.keyCode):(e.which));
    if(cms.active) {
        console.log('ue?', cms.space);
        //omniboxFocus(c);
        omniboxAdd(c);
        //e.preventDefault();
        //return false;
        return;
    } else if(c==':') {
        console.log('liga');
        enableSpace();
        setTimeout(omniboxFocus,200);
    }
    //_Q = true;
}

omnibox.q = '';
omnibox.terms = [];
omnibox.o = null;

omniboxAdd = function(c)
{
    if(!omnibox.o) omnibox.o = document.getElementById('e_omnibox');
    omnibox.q+=c;
    omnibox.o.value+=c;
}

function omniboxFocus()
{
    if(!omnibox.o) omnibox.o = document.getElementById('e_omnibox');
    omnibox.o.focus();
}

function omniboxBlur()
{
    if(!omnibox.o) omnibox.o = document.getElementById('e_omnibox');
    omnibox.o.blur();
}

function enableSpace()
{
    if(cms.space.className.indexOf('e-active')<0) cms.space.className+=' e-active';
    cms.active = true;
}

function disableSpace()
{
    if(cms.space.className.indexOf('e-active')>-1) cms.space.className=cms.space.className.replace(/\s*\be-active\b/, '');
    omniboxBlur();
    cms.active = false;
}


function fly()
{
    if(!cms.enabled) return;

    // enable triggers for omnibox
    setTimeout(toolbar,200); // delay to load language file
    bird.addEvent(window, 'keypress', omnibox);
    return true;
}

function toolbar()
{
    if(cms.space) return;
    console.log('olá!!!');
    cms.space=bird.element.call(document.body,{e:'div',a:{id:'e-space','class':'e-studio e-animated'},c:[
        {e:'span',a:{'class':'e-icon-e'}},
        {e:'form',a:{id:'f-omnibox','class':'e-inline',action:'#',onsubmit:'return bird.e.apply(this)'},c:[
            {e:'input',a:{id:'e_omnibox',name:'q',type:'text',placeholder:bird.t('omniboxPlaceholder'),autocomplete:'off',autofocus:'on'}}
        ]},
        {e:'span',a:{'class':'e-icon-sign-out e-right e-button'},t:{'click':disableSpace}}
    ]});
}

function search(s)
{
    if(!s && this.elements) s = this.elements.q.value;
    if(s) {
        bird.wget(cms.base+'/q?'+encodeURIComponent(s), searchResults, error, 'json', omnibox.o);
    }
    return false;
}

function searchResults(s)
{
    console.log('Search results!!!', arguments);
}
function error()
{
    console.log('error!!!', arguments);
}

function land(){
}
if(!bird.cms) bird.cms = { url: '/__' };
var cms={
    ready:true,
    enabled: true,
    active: false,
    data:(enableLocalStorage())?(window.localStorage):({}),
    base: bird.cms.url,
    space: null,
};

function enableLocalStorage() {
  try {
    return 'localStorage' in window && window['localStorage'] !== null;
  } catch (e) {
    return false;
  }
}

bird.e = search;


bird.ready(fly);
})();