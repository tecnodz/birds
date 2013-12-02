<?php
/**
 * Birds Installer
 *
 * This package provides means to install a server and individual sites.
 *
 * PHP version 5.3
 *
 * @category  Installer
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * Birds Installer
 *
 * This package provides means to install a server and individual sites.
 *
 * @category  Installer
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class Installer
{
    protected static $root=BIRD_ROOT, $apps=BIRD_APP_ROOT, $site='';
    public static function install()
    {
        $r = App::request();
        $o=array(
            'apps-dir'=>false,
            'site'=>false,
            'document-root'=>false,
            'domain'=>array(),
        );
        foreach($r['argv'] as $p) {
            if(preg_match('/^\-\-([a-z\-]+)=(.+)/', $p, $m)) {
                if(substr($m[2], 0, 1)=='"' || substr($m[2], 0, 1)=="'") {
                    $m[2] = substr($m[2], 1, strlen($m[2])-2);
                }
                if(!isset($o[$m[1]])) {
                    App::output("\n  Unknown parameter: {$m[1]}\n");
                } else if(!$m[2]) {
                    App::output("\n  Parameter {$m[1]} should not be empty.\n");
                } else if(is_array($o[$m[1]])) {
                    $o[$m[1]][] = $m[2];
                } else {
                    if($o[$m[1]]!==false) {
                        App::output("\n  Overwriting {$m[1]} with {$m[2]}.\n");
                    }
                    $o[$m[1]] = $m[2];
                }
            }
        }
        try {
            if($o['apps-dir']) {
                self::installApps($o['apps-dir']);
            }
            if($o['site']) {
                $o['site'] = bird::slug($o['site'],'_-');
                self::installSite($o['site']);
            }
            foreach($o['domain'] as $url) {
                self::addDomain(bird::slug($url, '-_.'));
            }
        } catch(Exception $e) {
            App::output($e->getMessage());
        }



        //App::output("\nThis is the ".bird::number(microtime(true)-BIRD_TIME,9)."s\n");
    }

    public static function installApps($apps, $site=null)
    {
        $valid = false;
        $apps = realpath($apps);
        if(!is_dir($apps) && !is_writable(dirname($apps))) {
            throw new \Exception("  --apps-dir location is not valid or is not writeable, please select another one.\n");
        }
        $move = false;
        if($apps == self::$root) {
            echo "\nThis is an initial install of the framework. All files should be moved to {$apps}/lib/vendor/Birds.\n";
            $move = true;
            // move current directory to a temporary folder
            $cwd = getcwd();
            $tmp = dirname(self::$root).'/Birdz-'.date('YmdHis');
            //echo "Moving from ".self::$root. " to {$tmp}\n";
            chdir(dirname(self::$root));
            //system("mv ".self::$root. " {$tmp}");
            rename(self::$root, $tmp);
            //echo "Creating the lib/vendor dir\n";
            mkdir($apps.'/lib/vendor', 0755, true);
            self::$root = $apps.'/lib/vendor/Birds';
            //echo "Moving framework to new root {$root}\n";
            rename($tmp, self::$root);
            //echo "Creating shell script {$root}\n";
            bird::save($apps.'/bird', '#!/usr/bin/env php'."\n"
                . '<'.'?php'."\n"
                . "require_once 'lib/vendor/Birds/bird.php';\n"
                . "//Birds\Cache::\$memcachedServers=array('db:11211');\n"
                . "Birds\bird::app()->fly();\n",
                false, 0777);
            mkdir($apps.'/data/cache', 0777, true);
            mkdir($apps.'/sites', 0777, true);
            mkdir($apps.'/log', 0777, true);
            mkdir($apps.'/config', 0777, true);
            clearstatcache();
            chdir($cwd);
            echo "\nBirds installed at {$apps}.\n";
        }
        self::$apps = $apps;
    }

    public static function installSite($site)
    {
        if(!$site || (!is_dir(self::$apps.'/sites/'.$site) && !is_writable(self::$apps.'/sites'))) {
            throw new \Exception("  --site is not valid or is not writeable, please select proper one.\n");
        }
        if(!is_dir(self::$apps.'/sites')) {
            mkdir(self::$apps.'/sites', 0777, true);
        }
        if($site && !is_dir(self::$apps.'/sites/'.$site)) {
            system("cp -rp ".self::$root."/sites/tdz ".self::$apps."/sites/{$site}");
        }
        if(!is_dir(self::$apps.'/config/sites')) {
            mkdir(self::$apps.'/config/sites', 0777, true);
        }
        self::$site = $site;
    }

    public static function addDomain($url)
    {
        if(!self::$site || !$url) {
            throw new \Exception("  --domain is not valid or is not writeable, please select proper one.\n");
        }
        bird::save(self::$apps.'/config/sites/'.$url.'.txt', self::$site);
    }

}

