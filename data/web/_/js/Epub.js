Bird.Epub = function(id) {

    // Bird applications should be created using new Bird(id) -- otherwise, we should force it
    if(this==window) {
        return new Bird.epub(id);
    }

    this.init(id);
};

Bird.Epub.prototype = {
    id: '',
    pages: 0,
    spine: [],
    content: {},
    data: '',
    root: null,
    contentData: 'OEBPS',
    version: 0.1,

    init: function(id) {
        if(window.app) {
            if('root' in window.app) {
                this.data = window.app.root;
            }
            if('items' in window.app) {
                if(id in window.app.items) {
                    this.id = window.app.items[id];
                } else {
                }
            }
        }
        if(this.id) {
            this.wget( this.data + this.id + '/META-INF/container.xml', this.loadContainer, this.loadPackage );
        }
        // load ui
    },


    loadContainer: function(data) {
        this.loadPackage($('rootfile', data).attr('full-path'));
    },
    loadPackage: function(data) {
        var c = (typeof(data)=='string' && data)?(data):('OEBPS/content.opf');
        if(c.substr(0,c.indexOf('/'))!=this.contentData) {
            this.contentData = c.substr(0,c.indexOf('/'));
        }
        this.wget( this.data + this.id + '/' + c, this.loadEdition );
    },

    loadEdition: function(data) {
        var d=$(data), m=$('manifest item',d),i=m.length, o, id, p, s='';

        while(i--) {
            o=$(m[i]);
            this.content[o.attr('id')]={ src: o.attr('href'), type: o.attr('media-type'), properties: o.attr('properties') || '' };
        }

        m=$('spine itemref',d);
        this.pages=m.length;
        i=0;
        while(i++<this.pages) {
            o=$(m[i-1]);
            this.spine.push(id=o.attr('idref'));
            s+='<div class="page" id="'+id+'"></div>';
            if(p=o.attr('properties')) {
                this.content[id].properties=p;
            }
        }
        s = '<div class="edition" id="'+this.id+'">'+s+'</div>';
        this.add(s);
        this.ready('loadPages');
    },

    add: function(d, c) {
        var z=this;
        ready.done(function(){ z._add(d, c); });
    },

    _add: function(d, c) {
        if(!this.root) {
            this.root = $(Z.rootElement);
        }
        if( typeof c !== 'undefined' ) {
            $(c, this.root).append(d);
        } else {
            this.root.append(d);
        }
    },

    ready: function(fn) {
        var z=this;
        ready.done(function(){ z[fn](); });
    },

    loadPages: function()
    {
        console.log('loadPages!', $('>.edition>.page:visible',this.root));
    },

    error: function() {
        console.log('[ERROR]', arguments);
    },

    created: new Date()

};