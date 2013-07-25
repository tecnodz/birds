<?php
/**
 * Route validation and forwarding
 *
 * This package implements a controller for the MVC environment
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Route validation and forwarding
 *
 * This package implements a controller for the MVC environment
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé <capile@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Route
{
	protected static $base; // folders where to look for routes
    public $format,
        $layout,
        $formats,
        $credentials,
        $multiviews,
        $shell;


    /**
     * Route builder
     *
     * An App\Route is a pointer that directs contents to specific slots in an App\Layout.
     *
     * Each route must be written in Yaml within one of the $app->config['routes-dir'] in the 
     * same folder as referred by the URL. For example, the URL /project/login must have its 
     * corresponding route at $routes-dir/project/login.yml.
     * 
     * The route's first stream must follow this sintax:
     *
     *   ---
     *   layout: ~              # the route's default layout configuration
     *   credentials: ~         # route credentials. If fails, it should return a 403 response.
     *   formats: [text/html]   # list of available formats for displaying this route. 
     *   meta:                  # metadata to be added to the layout. Some examples:
     *     title: ~             # page title
     *     @language: pt-BR     # <meta name="language" content="pt-BR" />
     *     script: ~            # javascript files to be loaded. The layout may put those at the end of the file.
     *     stylesheet: ~
     *     @viewport: ~
     *   content:               # content to be added to the layout
     *     $slotname:           # contents should be grouped by $slotname. Available slots are defined at the Layout.
     *       -
     *         class: $cn       # class name where this object is defined
     *         uid: $id         # primary key from $className to search for. If not defined, $method will be called statically.
     *         method: render   # method to use for rendering
     *         credentials: ~   # content credentials. If fails, the contents is not shown.
     *       -
     *         $className: $id  # alternative syntax, using the default values for method and credentials.
     *       -
     *         $id              # alternative syntax for html content at BIRD_VAR/content/$id
     * 
     * @param array $o      Route's representation, as explained above.
     * @param bool  $save   Key to save this route in cache.
     */
    public function __construct($o, $save=false)
    {
        if(!is_array($o)) throw new Exception('This is no route object.');

        // check credentials, if set

        $app = \Birds\bird::app();
        // create or set the layout and add $o['content'] and $o['meta'] to it.
        $this->layout = Layout::find((isset($o['layout']))?($o['layout']):(false));
        if(isset($o['meta'])) {
            $this->layout->addMeta($o['meta']);
        }
        if(isset($o['content'])) {
            $this->layout->addContent($o['content']);
        }
        // set available formats for layout
        if(isset($o['formats']) && is_array($o['formats'])) {
            $this->layout->formats=$o['formats'];
        }

        if(isset($o['options']) && is_array($o['options'])) {
            foreach($o['options'] as $n=>$v) {
                if($n=='meta' || $n=='formats' || $n=='content' || $n=='layout') continue;
                $n = \Birds\bird::camelize($n);
                if(property_exists($this, $n)) $this->$n = $v;
                unset($n, $v);
            }
        }
    }

    public function render($format=null)
    {
        if(is_null($format) && !is_null($this->format)) $format=$this->format;
        $r = $this->layout->render($format);
        if(is_string($r)) echo $r;
    }

    public function setFormat($format)
    {
        if(in_array($format, $this->layout->formats)) {
            $this->format=$format;
            return true;
        } else {
            return false;
        }
    }

	public static function setBase($d)
	{
		if(is_array($d)) {
			self::$base = array_values($d);
		} else if(!is_null(self::$base)) {
			array_unshift(self::$base, $d);
		} else {
			self::$base = array($d);
		}
		return self::$base;
	}

    /**
     * Route checker
     *
     * This method searches for valid routes for the given URL and returns 
     * the valid Birds\Route object
     *
     * @param string $route Request URL
     *
     * @return Birds\Route object or false if no matches are found.
     */
	public static function find($route, $updateScriptName=false)
	{
        if(is_null(self::$base)) return false;
        $cn = get_called_class();

        // transform any non-slug characters (except /_.) into lower-case ascii
        if(substr($route,0,1)!='/') $route = '/'.$route;
		$route = urldecode($route);
        if($updateScriptName) \Birds\bird::scriptName($route, false, true);
        if($route=='') $route='/';
        $routes = array();

        // is this necessary?
        $pi = pathinfo($route);
        if(substr($pi['dirname'],0,1)=='.') $pi['dirname'] = substr($pi['dirname'],1);
        $dir = $pi['dirname'].(($pi['filename'])?('/'.$pi['filename']):(''));
        $ext = (isset($pi['extension']))?(self::mimeType($pi['extension'])):('');
        unset($pi);

        // search for the route configuration file
        foreach(self::$base as $b) {
            $r = self::validateRoute($b.$dir.'.yml', $ext);
            if($r) return $r;
            unset($b, $r);
        }
        if($dir!='/') {
            $pd=explode('/', substr($dir,1));
            while(isset($pd[0])) {
                array_pop($pd);
                foreach(self::$base as $b) {
                    $r = self::validateRoute($b.'/'.implode('/',$pd).'.yml', $ext, true);
                    if($r && $r->multiviews) {
                        if($updateScriptName) \Birds\bird::scriptName('/'.implode('/',$pd));
                        return $r;
                    }
                    unset($b, $r);
                }
            }
            unset($pd);
        }
        return false;
	}

    /**
     * Route loader and checker
     *
     * Reads yaml configuration and returns only valid routes
     *
     * @param   string $f    Yaml filename
     * @param   string $ext  format to be checked
     *
     * @return Bird\Route or false if route is not valid
     */
	public static function validateRoute($f, $ext=false, $multiviews=false)
	{
        if(!file_exists($f)) {
            return false;
        }
        try {
            $r = \Birds\Yaml::read($f, 3600, array('language'=>\Birds\bird::$lang));
            if($r) {
                if($ext && isset($r['formats']) && !in_array($ext, $r['formats']) && !in_array('*', $r['formats'])) $r=false;
                else if($ext) $ext = '';
                if($multiviews && (!isset($r['options']['multiviews']) || $r['options']['multiviews']==false)) $r=false;
                else if(BIRD_CLI && (!isset($r['options']['shell']) || $r['options']['shell']==false)) $r=false;
                else if(!BIRD_CLI && ((isset($r['options']['http']) && $r['options']['http']==false) || (isset($r['options']['shell']) && $r['options']['shell']==true))) $r=false;
            }
            if($r) {
                if(!is_object($r)) $r = new Route($r, $f);
                if($ext && !$r->setFormat($ext)) {
                    unset($r);
                    $r=false;
                }
            }
        } catch(Exception $e) {
            \Birds\bird::log($e->getMessage());
            $r = false;
        }
        return $r;
	}

    /**
     * Supported mime-types
     * 
     * Returns the supported format for a given extension. Only formats supported by Birds\App should be listed.
     * 
     * @param string $ext lowercase file extension
     *
     * @return string mime-type supported or an empty string if not found
     */
    public static function mimeType($ext)
    {
        $m = array(
            // text
            'txt'   => 'text/plain',
            'html'  => 'text/html',
            'xhtml' => 'text/html',
            'htm'   => 'text/html',
            'xml'   => 'text/xml',
            'php'   => 'text/html',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'jpg'   => 'image/jpeg',
            'css'   => 'text/css',
            'less'  => 'text/css',
            'pdf'   => 'application/pdf',
            'js'    => 'application/javascript',
            'json'  => 'application/json',
            'svg'   => 'image/svg+xml',
            'otf'   => 'font/opentype',
            'ttf'   => 'application/x-font-truetype',
            'woff'  => 'application/font-woff',
            'eot'   => 'application/vnd.ms-fontobject',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        );

        if(strpos($ext, '/')) return array_search($ext, $m);
        return (isset($m[$ext]))?($m[$ext]):('application/octet-stream');

    }
}
