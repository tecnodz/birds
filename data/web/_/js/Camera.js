/*!Camera*/
(function(bird){

window.URL = window.URL || window.webkitURL;
navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
var _cameraEnabled = !!(navigator.getUserMedia); // should be tested
var _cameraNative  = false;
var _pictureSource;   // picture source
var _destinationType; // sets the format of returned value 

var t = {cameraUpload: 'Upload image', cameraCapture: 'Capture photo', cameraCaptureEdit: 'Capture editable photo', cameraGallery: 'Choose from gallery', cameraDisk: 'Choose from disk', cameraUploadButton: 'Upload', cameraShotButton: 'Take shot', cameraCancelButton: 'Cancel' },n;
for(n in t) {
    if(!(n in bird.t)) bird.t[n] = t[n];
    n=null;
}
t = null;

bird.Camera = function(d) 
{
    if(navigator.camera) {
        _cameraEnabled = true;
        _cameraNative  = true;
        _pictureSource = navigator.camera.PictureSourceType;
        _destinationType = navigator.camera.DestinationType;
    }

    bird.App.call(this, d);
    if('upload' in d) {
        if(d.upload.substr(0,1)=='/' && bird.r) {
            this.upload = bird.r + d.upload;
        } else {
            this.upload = d.upload;
        }
    }
    //if(_cameraEnabled) console.log('we have a camera!', this);
};
bird.stream = null;
var _cameraApp = null;
var _toUpload = [];
var _Files = {length:0};
bird.Camera.prototype = Object.create(bird.App.prototype);
bird.Camera.prototype.constructor = bird.Camera;
bird.Camera.prototype.type = 'Camera';
bird.Camera.prototype.upload = null;
bird.Camera.prototype.loadEnd = function()
{
    console.log('camera load end on '+this.id);

    var s='<h3>'+bird.t.cameraUpload+'</h3>';
    if(_cameraEnabled && _cameraNative) {
        s  += '<a class="app-camera-thumb app-camera-capture" onclick="return bird.Camera.capturePhoto();"><span class="app-camera-thumb-title">'+bird.t.cameraCapture+'</span></a>'
            //+ '<a class="app-camera-thumb app-camera-capture-edit" onclick="return bird.Camera.capturePhotoEdit();"><span class="app-camera-thumb-title">'+bird.t.cameraCaptureEdit+'</span></a>'
            + '<a class="app-camera-thumb app-camera-gallery" onclick="return bird.Camera.getPhotoFromGallery();"><span class="app-camera-thumb-title">'+bird.t.cameraGallery+'</span></a>'
            //+ '<a class="app-camera-thumb app-camera-disk" onclick="return bird.Camera.getPhotoFromDisk();"><span class="app-camera-thumb-title">'+bird.t.cameraDisk+'</span></a>'
            ;
    } else {
        if(_cameraEnabled) {
            s  += '<a class="app-camera-thumb app-camera-capture" onclick="return bird.Camera.captureVideoPhoto();"><span class="app-camera-thumb-title">'+bird.t.cameraCapture+'</span></a>';
        }
        s += '<form name="form-'+this.id+'" id="form-'+this.id+'" action="#">'
            + '<span class="app-camera-thumb app-camera-disk"><input type="file" name="f[]" id="f" accept="image/*;capture=camera" onchange="return bird.Camera.fileSelect(this);" /><span class="app-camera-thumb-title">'+bird.t.cameraDisk+'</span></span>'
            + '</form>';
    }
    //s  += '<div class="app-buttons">'
    //    +   '<a class="app-button submit">'+bird.t.cameraUploadButton+'</a>'
    //    +   '<a class="app-button cancel" onclick="return bird.land();">'+bird.t.cameraCancelButton+'</a>'
    //    + '</div>'
    s += ''
        + '<div class="camera-output">'
        + '</div>';
    var e=document.getElementById(this.id);
    _cameraApp = this.id;
    if(e) {
        var c=e.getElementsByClassName('app-container');
        if(c && c.length>0) {
            var p=document.createElement('div');
            p.className='app-camera-thumbs';
            bird.addHtml(p, s);
            c[0].appendChild(p);
        }
    }
}
var _renderEntry = bird.renderEntry;

// Called when a photo is successfully retrieved
function onPhotoSuccess(s, p) {
    console.log('onPhotoSuccess: '+s);
    var d, f, size;
    if(!p) p={};
    if(typeof(s)!='string' && s && 'target' in s) { // s is an event, try to find 'File' on target
        d = s.target.result;
        if('CameraFile' in s.target) {
            f = _Files[s.target.CameraFile];
            p.name = f.name;
            size = p.size = f.size;
            p.type = f.type;
            p.modified = bird.date(new Date(f.lastModifiedDate));
            _Files[s.target.CameraFile] = null; // do not remove to prevent messing with indexes

        }
    } else { // d = base64 data
        d = s;
        size = d.length;
    }
    var app = bird.a[_cameraApp];
    console.log('onPhotoSuccess: '+app.id, size, (app.id in bird.Apps), 'renderEntry' in bird.Apps[app.id]);
    var o = Sizzle('#'+app.id+' .camera-output')[0], cn='app-camera-img', uo, u;
    p.id='f'+(new Date().getTime());
    if(app.id in bird.Apps && 'renderEntry' in bird.Apps[app.id]) _renderEntry = bird.Apps[app.id].renderEntry;
    var fn = _renderEntry;
    var entry = fn.call(app, {id:p.id,media:[d]}), se;
    if(typeof(entry)=='string') {
        se = document.createElement('div');
        se.className = 'entry '+cn;
        bird.addHtml(se, entry);
    } else {
        se = entry;
        if(se.className.search(/\bentry\b/)<0){
            se.className+=' entry '+cn;
        }
    }
    var so = Sizzle('#'+app.id+' .camera-output > div:first');
    //console.log(se, so, o);
    if(so && so.length>0) {
        se = o.insertBefore(se, so[0]);
        so[0]=null;
    } else {
        se = o.appendChild(se);
    }
    if(app.upload) {
        // try cordova FileTransfer, or fallback to jQuery $.ajax with either FormData or targeting a iframe
        if(d.substr(0,5)!='data:' && _cameraNative) {
            uo = new FileUploadOptions();
            if('name' in p) uo.fileName = p.name;
            if('type' in p) uo.mimeType = p.type;
            if('key' in p) uo.fileKey   = p.key;
            else uo.fileKey = 'media';
            uo.params = p;
            u = new FileTransfer();
            u.upload(d, encodeURI(app.upload), photoUploadSuccess, photoUploadError, uo, true);
            uo = null;
            se.className+= ' app-uploading';
        } else if(_cameraEnabled) { // ajax with FormData
            console.log('uploading with FormData!');
            uo = new FormData();
            if(!('name' in p)) p.name = 'image.jpg';
            for(var n in p) {
                uo.append(n, p[n]);
            }
            if(!('key' in p)) p.key = 'media';
            if(d.substr(0,5)=='data:') {
                uo.append(p.key, bird.b64Blob(d), p.name);
            }
            $.ajax({
                url: app.upload,
                type: 'POST',
                data: uo,
                processData: false,
                contentType: false,
                success: photoUploadSuccess,
                error: photoUploadError,
                progress: photoUploadProgress,
                context: se
            });
            uo = null;
            se.className+= ' app-uploading';
        }
    }
    if(window.getComputedStyle) {
        window.getComputedStyle(se).getPropertyValue('top');
    }
    se.className+=' entry-ready';
    se = null;
    app = null;
    img = null;
    o = null;

    bird.closeOverlay();
    /*
    // Get image handle
    var smallImage = document.getElementById('smallImage');

    // Unhide image elements
    smallImage.style.display = 'block';

    // Show the captured photo
    // The inline CSS rules are used to resize the image
    smallImage.src = "data:image/jpeg;base64," + imageData;
    */
}

function photoUploadProgress(e)
{
    console.log('photoUploadProgress');
    console.log(e, this);
}


function photoUploadSuccess(r) {
    console.log('photoUploadSuccess', r);
    //console.log(this);
    if(typeof(r)!='string' && ('response' in r)) {
        //console.log("Code = " + r.responseCode);
        //console.log("Response = " + r.response);
        //console.log("Sent = " + r.bytesSent);
        r = JSON.parse(r.response);
    } else if(typeof(r)=='string') {
        r = JSON.parse(r);
    }

    var app = bird.a[_cameraApp];
    var o = Sizzle('#'+app.id+' .camera-output')[0];
    var entry = _renderEntry.call(app, r), se;
    if(typeof(entry)=='string') {
        se = document.createElement('div');
        se.className = 'entry entry-ready';
        bird.addHtml(se, entry);
    } else {
        se = entry;
        if(se.className.search(/\bentry\b/)<0){
            se.className+=' entry';
        }
        if(se.className.search(/\bentry-ready\b/)<0){
            se.className+=' entry-ready';
        }
    }
    var so = ('getAttribute' in this)?(Sizzle('#'+app.id+' .camera-output > #'+this.getAttribute('id'))):(Sizzle('.app-uploading', se));
    if(so && so.length>0) {
        se = o.insertBefore(se, so[0]);
        o.removeChild(so[0]);
        so[0]=null;
    } else {
        se = o.appendChild(se);
    }
    // must add the title to the image -- this is just a shortcut
    bird.land('camera');
    bird.alert('Foto enviada com sucesso.');
}
function photoUploadError(e) {
    console.log('photoUploadError', e);
    console.log("An error has occurred: Code = " + e.code);
    console.log("upload error source " + e.source);
    console.log("upload error target " + e.target);
    bird.alert('Houve um erro ao enviar a sua foto.');
}

// Browser implementation of camera using <video>
bird.Camera.captureVideoPhoto = function() {
    var c=document.getElementById('bird-camera');
    if(!c) {
        c = bird.createOverlay(
              '<div class="camera-video-container">'
            +   '<video id="bird-camera-video" width="640" height="480" autoplay="true"></video>'
            +   '<canvas id="bird-camera-canvas" width="640" height="480"></canvas>'
            +   '<button id="bird-camera-snap" class="submit">'+bird.t.cameraShotButton+'</button>'
            +   '<button id="bird-camera-cancel" class="cancel app-overlay-cancel">'+bird.t.cameraCancelButton+'</button>'
            + '</div>',
            'bird-camera', 'app-overlay-camera'
        );
        document.getElementById('bird-camera-snap').addEventListener('click', bird.Camera.captureVideoPhoto);
        //document.getElementById('bird-camera-cancel').addEventListener('click', bird.closeOverlay);
        navigator.getUserMedia({audio: false, video: true}, captureVideoEnable, bird.closeOverlay );
    } else if(bird.stream) {
        var canvas = document.getElementById('bird-camera-canvas'), ctx=canvas.getContext('2d'), video = document.getElementById('bird-camera-video');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        onPhotoSuccess(canvas.toDataURL('image/jpeg', 0.8));
        canvas=null;
        video=null;
        ctx = null;
        bird.closeOverlay();
    }

}

function captureVideoEnable(stream)
{
    var video = document.getElementById('bird-camera-video'), canvas = document.getElementById('bird-camera-canvas');
    if(video) {
        video.src = window.URL.createObjectURL(stream);
        video.addEventListener('click', bird.Camera.captureVideoPhoto, false);
        bird.stream = stream;
    }
}

bird.Camera.fileSelect = function (o) {
    console.log('fileSelect', o);
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        var files = o.files;
 
        var result = '';
        var file;
        for (var i = 0; file = files[i]; i++) {
            // if the file is not an image, continue
            if (!file.type.match('image.*')) {
                continue;
            }
            console.log(file);
 
            reader = new FileReader();
            reader.onload = onPhotoSuccess;
            reader.CameraFile = _Files.length++;
            _Files[reader.CameraFile] = file;
            reader.readAsDataURL(file);
        }
    }
}

// A button will call this function
bird.Camera.capturePhoto = function() {
    // Take picture using device camera and retrieve image as base64-encoded string
    navigator.camera.getPicture(onPhotoSuccess, bird.error, { quality: 60, destinationType: _destinationType.FILE_URI });
}

// A button will call this function
bird.Camera.capturePhotoEdit = function() {
    // Take picture using device camera, allow edit, and retrieve image as URL 
    navigator.camera.getPicture(onPhotoSuccess, bird.error, { quality: 60, allowEdit: true, destinationType: _destinationType.FILE_URI });
}

// A button will call this function
bird.Camera.getPhoto = function(source) {
    // Retrieve image file location from specified source
    navigator.camera.getPicture(onPhotoSuccess, bird.error, { quality: 60, destinationType: _destinationType.FILE_URI, sourceType: source });
}
bird.Camera.getPhotoFromGallery = function() { return bird.Camera.getPhoto(_pictureSource.PHOTOLIBRARY); }
bird.Camera.getPhotoFromDisk = function() { return bird.Camera.getPhoto(_pictureSource.SAVEDPHOTOALBUM); }



})(window.bird);
