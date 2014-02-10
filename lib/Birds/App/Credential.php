<?php
/**
 * Credential checking and validation
 *
 * All credentials are stored on bird::app()->Bird['credentials-dir']. For each check
 * this module will see if there's a corresponding credential file, or try its parents.
 * The main parent is always '.yml' file, which should provide at least basic read-level
 * access to the system.
 *
 * An instance of a class (specially models) has a configuration file expressed this way:
 *    $ModelName/$pk.yml
 *
 * The class itself has this configuration file:
 *    $ModelName.yml
 *
 * Routes might have the credentials set at the route itself or within:
 *    Birds.App.Route/$url.yml
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
 * Credential checking and validation
 *
 * @category  App
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\App;
class Credential 
{

    public static function validateCredential($f, $credential=-1)
    {
        if(!file_exists($f)) {
            return false;
        }
        try {
            $r = \Birds\Yaml::read($f, 3600);
            if($r && is_array($r['credential'])) {
                foreach($r['credential'] as $l=>$c) {
                    if(!isset($c['require']) || $l<$credential) {
                        continue;
                    }
                    if($c['require']=='~') {
                        $credential = $l;
                    } else {
                        $cany=false;
                        if($c['require']=='*') {
                            $cany=true;
                            $c['require']=array('ip', 'certificate', 'http', 'group', 'user');
                        } else if(!is_array($c['require'])) {
                            $c['require']=array($c['require']);
                        }
                        foreach($c['require'] as $rk=>$req) {
                            if(isset($c[$req])) {
                                $m = 'validate'.ucfirst($req);
                                if(self::$m($c[$req])) {
                                    if($cany) {
                                        unset($rk, $req, $m);
                                        $credential = $l;
                                        break;
                                    } else {
                                        unset($c['require'][$rk]);
                                    }
                                }
                            }
                            unset($rk, $req, $m);
                        }
                        if(!$cany && count($c['require'])==0) {
                            $credential=$l;
                        }
                    }
                    unset($l, $c, $cany);
                }
            }
            unset($r, $f);
        } catch(Exception $e) {
            \Birds\bird::log($e->getMessage());
            $r = false;
        }
        return $credential;
    }

    /**
     * Checks if user has the required credentials
     * 
     * Credentials can be set globally or per class name and primary key
     * 
     * Credential levels are:
     *
     * 1: Reader level, cannot commit any changes
     * 2: Contributor level, can make its own revisions, but they need to be approved before 
     *    they are implemented
     * 3: Editor level, may update the objects directly, can also see other contributor revisions
     *    and approve them
     * 4: Owner level, has full read/write access to the resource
     * 5: Administrator level, can assign other credentials
     *
     * Each level should be able to assign credentials one level lower than its own level, starting
     * on level 3 (Administrators may set other administrators as well)
     *
     * Credentials are stored on credentials-dir as Yaml files. Base credentials can be checked on .yml file
     *
     * @param string    $cn         class name to be checked
     * @param mixed     $id         primary key of the object to be checked
     * @param int       $level      credential level that needs to be checked, if user actual credentials are returned
     * @param bool      $exception  whether an exception should be returned if credential check fails
     *
     * @return mixed    true on success, actual credential level if fails (it should be a number from 1-4)
     */
    public static function check($cn=null, $id=null, $level=9, $exception=false)
    {
        if($level==0) return true;
        if(is_array($id)) {
            $id = implode('.', $id);
        }
        if($cn) {
            $p=str_replace('\\', '.', $cn);
            if($id) {
                $p .= '/'.$id;
            }
        } else {
            $p = '';
        }
        $c = \bird::$baseCredential; // starting level
        $cd = \bird::app()->Birds['credential-dir'];
        if(!is_array($cd)) $cd = array($cd);
        while(isset($p)) {
            // check if file exists
            foreach($cd as $d) {
                if(file_exists($f=$d.'/'.$p.'.yml') && $l=self::validateCredential($f, $c)) {
                    if($l>=$level) {
                        unset($cd, $d, $c, $p, $f, $l);
                        return true;
                    } else if($l>$c) {
                        $c = $l;
                    }
                    break;
                }
                unset($d, $f, $l);
            }
            if(strpos($p, '/')) {
                $p = substr($p, 0, strrpos($p, '/'));
            } else if($p) {
                $p='';
            } else {
                unset($p);
                break;
            }
        }
        unset($cd, $p);
        if($exception) {
            throw new HttpException(403);
        }
        return $c;
    }

    public static function validateIp($i)
    {
        static $ip;
        if(is_null($ip)) {
            if(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if(isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip = false;
            }
        }
        return ((!is_array($i) &&$ip==$i) || in_array($ip, $i));
    }

    public static function validateCertificate()
    {
        return false;
    }
    public static function validateHttp()
    {
        return false;
    }
    public static function validateGroup()
    {
        return false;
    }
    public static function validateUser()
    {
        return false;
    }
}