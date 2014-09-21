<?php
/**
 * Birds Application Server
 *
 * This package enable Birds routes and controller
 *
 * PHP version 5.3
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Tecnodesign Application Server
 *
 * This package enable Tecnodesign application management.
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class App
{
    public static $onStart=array(),$onEnd=array(), $response;
    protected $config;
    protected static $request, $instance, $running=false;

	public function __construct()
	{
        $cfg=self::configFiles();
        array_unshift($cfg, bird::env());
        $this->config = bird::recursiveReplace(
            array('$BIRD_ROOT', '$BIRD_APP_ROOT', '$BIRD_VAR', '$BIRD_VERSION', '$BIRD_SITE_ROOT', '$BIRD_TIME', '$BIRD_ENV'),
            array(  BIRD_ROOT,    BIRD_APP_ROOT,    BIRD_VAR,    BIRD_VERSION,    BIRD_SITE_ROOT,    BIRD_TIME,    bird::env()),
            call_user_func_array('Birds\\bird::config', $cfg)
        );
        unset($cfg);
        foreach($this->config['Birds'] as $k=>$v) {
            if(substr($k, -4)=='-dir' || $k=='document-root') {
                $w = strpos($v, '*');
                if(strpos($v, ':')!==false) {
                    $pv=explode(':', $v);
                } else {
                    $pv = array($v);
                }
                if($w!==false) {
                    $npv = array();
                    foreach($pv as $v) {
                        if(strpos($v, '*')!==false) {
                            $npv = array_merge($npv, glob($v, GLOB_ONLYDIR));
                        }
                        unset($v);
                    }
                    unset($pv);
                    $pv = $npv;
                    unset($npv);
                }
                $v=array();
                foreach($pv as $vk=>$vv) {
                    $vv = (substr($vv, 0, 1)!='/')?(realpath(BIRD_APP_ROOT.'/'.$vv)):(realpath($vv));
                    if(BIRD_OS=='windows'){
                        $vv = str_replace('\\', '/', $vv);
                    }
                    if($vv && !in_array($vv, $v)) {
                        $v[]=$vv;
                    }
                    unset($vk, $vv);
                }
                if(count($v)<=1) $v=implode('', $v);
                else $v=array_values($v);

                if($v) $this->config['Birds'][$k] = $v;
            } else if(is_string($v) && substr($v, 0, 1)=='[' && substr($v, -1)==']') {
                $this->config['Birds'][$k] = preg_split('#\s*,\s*#', trim(substr($v, 1, strlen($v)-2)), null, PREG_SPLIT_NO_EMPTY);
            }
            unset($k, $v);
        }
        $this->start();
	}

    public function __wakeup()
    {
        $this->start();
    }
    
    /**
     * Class initialization
     */
    public function start()
    {
        if(isset($this->config['Birds']['lib-dir'])) {
            $sep = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')?(';'):(':');
            if(!is_array($this->config['Birds']['lib-dir'])) {
                $this->config['Birds']['lib-dir'] = explode($sep, $this->config['Birds']['lib-dir']);
            }
            foreach ($this->config['Birds']['lib-dir'] as $dir) {
                if(!in_array($dir, bird::$lib)) {
                    bird::$lib[]=$dir;
                }
            }
            $libdir = ini_get('include_path').$sep.implode($sep, bird::$lib);
            @ini_set('include_path', $libdir);
        }
        if(isset($this->config['Birds']['language'])) {
            bird::$lang=$this->config['Birds']['language'];
        }
        if(isset($this->config['Birds']['document-root'])) {
            $_SERVER['DOCUMENT_ROOT'] = $this->config['Birds']['document-root'];
        }
        if(isset($this->config['Birds']['export'])) {
            foreach($this->config['Birds']['export'] as $cn=>$toExport) {
                if(!class_exists($cn)) {
                    $cn = 'Birds\\'.bird::camelize($cn, true);
                    if(!class_exists($cn)) {
                        return false;
                    }
                }
                foreach($toExport as $k=>$v) {
                    $cn::$$k=$v;
                }
            }
        }
        foreach(self::$onStart as $k=>$s) {
            App\Content::create($s,null,true);
            unset(self::$onStart[$k], $k, $s);
        }
    }
    
	public function fly($format=null)
	{
        @ob_clean();
        self::$running = true;
        if(!isset($this->config['Birds']['routes-dir'])) {
            bird::log('Don\t know where to go -- please set Birds->routes-dir');
            $this->error(404);
        }
        self::$response=null;
        self::$request=null;

        $req = self::request();

        if(isset($this->config['Birds']['language']) && $this->config['Birds']['language']) bird::$lang=$this->config['Birds']['language'];
        else if(isset($this->config['Birds']['languages'])) bird::$lang=self::language($this->config['Birds']['languages']);
        //set_error_handler(array('bird', 'log'));

        try {
            App\Route::setBase($this->config['Birds']['routes-dir']);
            $route = App\Route::find($req['script-name'], true);
            if($route) {
                $route->render($format);
                unset($route);
            } else {
                $this->error(404, $format);
            }
        } catch(App\HttpException $e) {
            if($e->getCode()<300) {
                return;
            }
            $this->error($e->getCode(), $format);
        } catch(\Exception $e) {
            bird::log(__METHOD__.', '.__LINE__.' '.$e->getMessage());
            $this->error(500, $format);
        }
        self::$running = false;
        self::end();
	}

	public function __toString()
	{
		return 'Bird Application: '.bird::name().'/'.bird::env();
	}

    public function error($no=500, $format=null)
    {
        self::$running = false;
        $err = App\Route::find('/error'.$no);
        if($err) {
            $err->render($format);
            unset($err);
        } else {
            bird::debug(__METHOD__.': '.$no.' for '.bird::scriptName(true));
        }
    }

    public static function end($exception=false)
    {
        foreach(self::$onEnd as $k=>$s) {
            App\Content::create($s,null,true);
            unset(self::$onEnd[$k], $k, $s);
        }
        if(!is_null(bird::$session)) {
            // store session
            Session::store();
            //Cache::set('session/'.Session::$id, bird::$session, Session::$expires);
            //bird::log('saving session: session/'.Session::$id.' '.Session::name(), var_export(bird::$session, true));
        }
        if(self::$running && $exception) {
            throw new App\HttpException(200);
        }
    }

    /**
     * Request builder
     * 
     * Might be replaced afterwards for a proper Tecnodesign_Request object
     * 
     * @return array request directives
     */
    public static function request($p=null)
    {
        if(is_null(self::$request)) {
            $removeExtensions=array('html', 'htm', 'php');
            self::$request['shell']=BIRD_CLI;
            self::$request['method']=(!self::$request['shell'])?(strtolower($_SERVER['REQUEST_METHOD'])):('get');
            self::$request['ajax']=(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest');
            if (!self::$request['shell']) {
                self::$request['hostname']=$_SERVER['HTTP_HOST'];
                self::$request['host']=((isset($_SERVER['HTTPS']))?('https://'):('http://')).self::$request['hostname'];
                $ui=@parse_url($_SERVER['REQUEST_URI']);
                if(!$ui) {
                    $ui=array();
                    $ui['path']=bird::safePath($_SERVER['REQUEST_URI']);
                    if(strpos($_SERVER['REQUEST_URI'], '?')!==false) {
                        $ui['query']=substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?')+1);
                    }
                }
            } else {
                $arg = $_SERVER['argv'];
                self::$request['shell'] = array_shift($arg);
                $ui = array_shift($arg);
                $ui=parse_url($ui);
                if(isset($ui['query'])) {
                    parse_str($ui['query'], $_GET);
                }
                self::$request['argv']=$arg;
                unset($arg);
            }
            self::$request['query-string']=(isset($ui['query']))?($ui['query']):('');
            self::$request['script-name']=bird::safePath($ui['path']);
            if (preg_match('/\.('.implode('|', $removeExtensions).')$/i', self::$request['script-name'], $m)) {
                self::$request['self']=substr(self::$request['script-name'],0,strlen(self::$request['script-name'])-strlen($m[0]));
                self::$request['extension']=substr($m[0],1);
            } else {
                self::$request['self']=self::$request['script-name'];
            }
            unset($ui);
            self::$request['get']=$_GET;
            self::$request['post']=$_POST+$_FILES;
        }
        if(!is_null($p)) {
            if(isset(self::$request[$p])) {
                return self::$request[$p];
            } else {
                return false;
            }
        }
        return self::$request;
    }

    /**
     * Response updater
     * 
     * Retrieves/Updates the response object.
     * 
     * @return bool
     */
    public static function response(&$r=null)
    {
        if(!is_null($r)) self::$response =& $r;
        return self::$response;
    }

    /**
     * Input handler: unreliable
     */
    public static function input()
    {
        exec("stty -icanon min 0 time 0");
        $stdin = fopen('php://stdin', 'r');
        $s = '';
        while(!$s) {
            $s = fgets($stdin);
        }
        fclose($stdin);
        unset($stdin);
        return $s;
    }

    /**
     * Output handler: this should manage if the output should be buffered or not.
     */
    public static function output($s, $end=false)
    {
        echo $s;
        if($end) throw new App\HttpException(200);
    }

    /**
     * Output handler: this should manage if the output should be buffered or not.
     */
    public static function outputFile($s, $end=false)
    {
        echo file_get_contents($s);
        if($end) throw new App\HttpException(200);
    }

    /**
     * Output handler: this should manage if the output should be buffered or not.
     */
    public static function header($s)
    {
        if(is_array($s)) {
            foreach($s as $h) {
                @header($h);
                unset($h);
            }
        } else {
            @header($s);
        }
    }


    /**
	 * App recovery: tries to get existing app from variable or memory (cache)
	 */
    public static function getInstance($name=false, $env=false, $expires=0)
    {
        if(!is_null(self::$instance)) {
            unset($name, $env, $expires);
            return self::$instance;
        }
        if (!$name) $name = bird::name();
        if (!$env)  $env  = bird::env();
        $ckey="Bird/{$env}";
        if(!($app=Cache::get($ckey, $expires))) {
            $app = new App($name, $env);
            Cache::set($ckey, $app);
        }
        self::$instance = $app;
        unset($app, $ckey, $name, $env, $expires);
        return self::$instance;
    }

    /**
     * Lists all config files for app/site
     */
    public static function configFiles()
    {
        $name = bird::name();
        list($server, $domain) = explode('.', bird::serverName(), 2);
        $cfg=array(BIRD_ROOT.'/config/bird.yml');
        if(BIRD_APP_ROOT!=BIRD_ROOT && $name!='bird' && file_exists($f=BIRD_APP_ROOT.'/config/bird.yml')) {
            $cfg[]=$f;
        }
        if(file_exists($f=BIRD_APP_ROOT.'/config/'.$name.'@'.$server.'.yml')) {
            $cfg[]=$f;
        }
        if(file_exists($f=BIRD_APP_ROOT.'/config/'.$name.'.yml')) {
            $cfg[]=$f;
        }
        if(defined('BIRD_SITE_ROOT') && BIRD_SITE_ROOT!=BIRD_APP_ROOT) {
            if(file_exists($f=BIRD_SITE_ROOT.'/config/'.$name.'@'.$server.'.yml')) {
                $cfg[]=$f;
            }
            if(file_exists($f=BIRD_SITE_ROOT.'/config/'.$name.'.yml')) {
                $cfg[]=$f;
            }
        }
        unset($f, $server, $domain, $name);
        return $cfg;
    }

    /**
     * Sets user language according to browser preferences
     */
    public function language($l=array())
    {
        if(!is_array($l)) {
            $lang = $l;
        } else if(count($l)<2) {
            $lang = $l[0];
        } else {
            if(substr(self::$request['query-string'],0,1)=='!' && in_array($lang=substr(self::$request['query-string'],1), $l)) {
                setcookie('lang',$lang,0,'/',false,false);
                \bird::redirect(bird::scriptName(true));
            }
            unset($lang);
            if(!(isset($_COOKIE['lang']) && ($lang=$_COOKIE['lang']) && (in_array($lang, $l) || (strlen($lang)>2 && in_array($lang=substr($lang,0,2), $l))))) {
                unset($lang);
            }
            if (!isset($lang) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $accept = preg_split('/(;q=[0-9\.]+|\,)\s*/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], null, PREG_SPLIT_NO_EMPTY);
                foreach ($accept as $lang) {
                    if (in_array($lang, $l) || (strlen($lang)>2 && in_array($lang=substr($lang,0,2), $l))) {
                        break;
                    }
                    unset($lang);
                }
                unset($accept);
            }
        }
        if(!isset($lang)) {
            $lang = bird::$lang;
        }
        return $lang;
    }

    /**
     * Magic setter. Searches for a set$Name method, and stores the value in $config
     * for later use.
     *
     * @param string $name  parameter name, should start with lowercase
     * @param mixed  $value value to be set
     *
     * @return void
     */
    public function  __set($name, $value)
    {
        $m='set'.ucfirst($name);
        if (method_exists($this, $m)) {
            $this->$m($value);
        }
        $this->config[$name]=$value;
    }

    /**
     * Magic getter. Searches for a get$Name method, or gets the stored value in
     * $config.
     *
     * @param string $name parameter name, should start with lowercase
     * 
     * @return mixed the stored value, or method results
     */
    public function  __get($name)
    {
        $m='get'.ucfirst($name);
        $ret = false;
        if (method_exists($this, $m)) {
            $ret = $this->$m();
        } else if (isset($this->config[$name])) {
            $ret = $this->config[$name];
        }
        return $ret;
    }

}
