/*! Bird CMS version v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
(function(){
var c=0;
bird.e = function()
{
    console.log('omnibox search!!', this, this.value);
    return false;
}
var _Q;
function omnibox(e)
{
    e = e || window.event;
    if(e.target && e.target.nodeName.search(/input|textarea|select|button/i)>-1) return;
    if(e.keyCode==27) return disableSpace();
    if(_Q) return;
    var c = String.fromCharCode((window.event)?(e.keyCode):(e.which));
    console.log('omnibox: '+c, e.keyCode);
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
    omniboxFocus();
}

function omniboxFocus()
{
    if(!omnibox.o) omnibox.o = document.getElementById('e_omnibox');
    omnibox.o.focus();
}

function enableSpace()
{
    if(cms.space.className.indexOf('e-active')<0) cms.space.className+=' e-active';
    cms.active = true;
}

function disableSpace()
{
    if(cms.space.className.indexOf('e-active')>-1) cms.space.className=cms.space.className.replace(/\s*\be-active\b/, '');
    cms.active = false;
}


function fly()
{
    if(!cms.enabled) return;

    // enable triggers for omnibox
    setTimeout(toolbar,200); // delay to load language file
    bird.addEvent(document, 'keypress', omnibox);
    return true;
}

function toolbar()
{
    if(cms.space) return;
    console.log('olá!!!');
    cms.space=bird.element.call(document.body,{e:'div',a:{id:'e-space','class':'e-studio e-animated'},c:[
        {e:'span',a:{'class':'e-icon-e'}},
        {e:'form',a:{id:'f-omnibox','class':'e-inline',action:'#',onsubmit:'return bird.e.apply(this)'},c:[
            {e:'input',a:{id:'e_omnibox',name:'e_omnibox',type:'text',placeholder:bird.t('omniboxPlaceholder'),autocomplete:'off',autofocus:'on'}}
        ]},
        {e:'span',a:{'class':'e-icon-sign-out e-right e-button'},t:{'click':disableSpace}}
    ]});
}

function search()
{
    console.log('omnibox search!!', this, this.value);
    return false;
}

function land(){
}
if(!bird.cms) bird.cms = { url: '/_e' };
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


bird.ready(fly);
})();