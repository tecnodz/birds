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

    protected $config;
    protected static $request, $response, $instance, $router='Birds\\App\\Route', $running=false;

	public function __construct()
	{
        $cfg=self::configFiles();
        array_unshift($cfg, bird::env());
        $this->config = bird::recursiveReplace(
            array('$BIRD_ROOT', '$BIRD_APP_ROOT', '$BIRD_VAR', '$BIRD_VERSION', '$BIRD_SITE_ROOT', '$BIRD_TIME'),
            array(  BIRD_ROOT,    BIRD_APP_ROOT,    BIRD_VAR,    BIRD_VERSION,    BIRD_SITE_ROOT,    BIRD_TIME),
            call_user_func_array('Birds\\bird::config', $cfg)
        );
        unset($cfg);
        foreach($this->config['Birds'] as $k=>$v) {
            if(substr($k, -4)=='-dir' || $k=='document-root') {
                if(strpos($v, ':')!==false) {
                    $pv=explode(':', $v);
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
                } else {
                    $v = (substr($v, 0, 1)!='/')?(realpath(BIRD_APP_ROOT.'/'.$v)):(realpath($v));
                    if(BIRD_OS=='windows'){
                        $v = str_replace('\\', '/', $v);
                    }
                }
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
            $sep = (isset($_SERVER['WINDIR']))?(';'):(':');
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
                    $cn = 'Birds\\'.ucfirst(tdz::camelize($cn));
                    if(!class_exists($cn)) {
                        return false;
                    }
                }
                foreach($toExport as $k=>$v) {
                    $cn::$$k=$v;
                }
            }
        }
    }
    
	public function fly($format=null)
	{
        self::$running = true;
        if(!isset($this->config['Birds']['routes-dir'])) {
            bird::log('Don\t know where to go -- please set Birds->routes-dir');
            $this->error(404);
        }
        self::$response=&bird::$vars;
        self::$response+=array('headers'=>array(),'variables'=>array());
        if(isset($this->config['Birds']['response'])) {
            self::$response += $this->config['Birds']['response'];
        }
        if(isset($this->config['Birds']['language'])) bird::$lang=$this->config['Birds']['language'];
        else if(isset($this->config['Birds']['languages'])) bird::$lang=self::language($this->config['Birds']['languages']);

        //set_error_handler(array('bird', 'log'));

        self::$request=null;
        $req = self::request();
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

    public static function end($exception=true)
    {
        if(!is_null(bird::$session)) {
            // store session
            Cache::set('session/'.Session::$id, bird::$session, Session::$expires);
            //bird::log('closing session: session/'.Session::$id.' '.Session::name(), var_export(bird::$session, true));
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
                    if(strpos($_SERVER['REQUEST_URI'], '?')!==false) {
                        $ui['path']=substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
                        $ui['query']=substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?')+1);
                    } else {
                        $ui['path']=$_SERVER['REQUEST_URI'];
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
            self::$request['script-name']=$ui['path'];
            if (preg_match('/\.('.implode('|', $removeExtensions).')$/i', $ui['path'], $m)) {
                self::$request['self']=substr($ui['path'],0,strlen($ui['path'])-strlen($m[0]));
                self::$request['extension']=substr($m[0],1);
            } else {
                self::$request['self']=$ui['path'];
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
    public static function response()
    {
        $a = func_get_args();
        $an = count($a);
        if ($an==2 && !is_array($a[0])) {
            self::$response[$a[0]]=$a[1];
        } else if($an==1 && is_array($a[0])) {
            self::$response = bird::mergeRecursive($a[0], self::$response);
        }
        return self::$response;
    }

    /**
     * Output handler: this should manage if the output should be buffered or not.
     */
    public static function output($s)
    {
        echo $s;
    }

    /**
     * Output handler: this should manage if the output should be buffered or not.
     */
    public static function outputFile($s)
    {
        echo file_get_contents($s);
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
        if(BIRD_APP_ROOT!=BIRD_ROOT) {
            if($name!='bird' && file_exists($f=BIRD_APP_ROOT.'/config/bird.yml')) {
                $cfg[]=$f;
            }
            if(file_exists($f=BIRD_APP_ROOT.'/config/'.$name.'@'.$server.'.yml')) {
                $cfg[]=$f;
            }
            if(file_exists($f=BIRD_APP_ROOT.'/config/'.$name.'.yml')) {
                $cfg[]=$f;
            }
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
        if(is_array($l) && count($l)<2) {
            $lang = $l[0];
        } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept = preg_split('/(;q=[0-9\.]+|\,)\s*/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], null, PREG_SPLIT_NO_EMPTY);
            foreach ($accept as $lang) {
                if (isset($l[0]) && in_array($lang, $l)) {
                    break;
                }
                unset($lang);
            }
            unset($accept);
        }
        if(!isset($lang)) {
            $lang = bird::$lang;
        }
        return bird::$lang;
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

