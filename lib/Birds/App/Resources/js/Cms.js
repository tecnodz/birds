/*! Bird CMS version v0.1 | (c) 2013 Capile Tecnodesign <ti@tecnodz.com> */
(function(bird){

function fly()
{
	console.log('Bird flying!');
}



bird.onReady.push(fly);
})(window.bird);