/*!Mobile*/
(function(bird){
bird.mobile = navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/);
document.getElementsByTagName('html')[0].className += (bird.mobile)?(' mobile'):(' desktop');

bird.s = 'wvga-p';


/*
function stopScrolling( touchEvent ) { touchEvent.preventDefault(); }
document.addEventListener( 'touchstart' , stopScrolling , false );
document.addEventListener( 'touchmove' , stopScrolling , false );
*/
})(window.bird);


