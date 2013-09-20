/*!Mobile*/
(function(bird){
console.log('UA: '+navigator.userAgent);
bird.mobile = navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry)/);
document.getElementsByTagName('html').item(0).className += (bird.mobile)?(' mobile'):(' desktop');

bird.s = 'wvga-p';
bird.onReady.push(function(bird) {
    document.addEventListener('backbutton', bird.land, false);
});
})(window.bird);


