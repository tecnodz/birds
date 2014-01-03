/*! Birds */
var _b=(new Date().getTime());Modernizr.load([{test:window.jQuery,nope:"/_/js/jquery.js"},{test:("bird" in window),nope:"/bird-cms.js?"+_b,complete:function(){Modernizr.load([{test:window.Bird,yep:"/bird-cms/bird.js?Cms",complete:function(){bird.ready()}}])}}]);
