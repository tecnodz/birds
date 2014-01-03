<?php
/**
 * User authentication & authorization
 *
 * Session storage is done at Birds\Session object, this component holds the 
 * authentication components, as well as the Oauth objects used to communicate
 * to foreign APIs.
 * 
 * This class can be used within a content, by assigning this class and the 
 * *Component methods, for example:
 *
 *     -
 *       class: Birds\User
 *       method: SingleSignOnComponent
 *       prepare: true
 *
 * The components have their parameters set at the Birds/User/$Component configuration.
 * Each one brings further details about the configuration options.
 *
 * PHP version 5.3
 *
 * @category  User
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * User authentication & authorization
 *
 * @category  User
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds;
class User {

    public static function id()
    {
        bird::session();
        return (isset(bird::$session['uid']))?(bird::$session['uid']):(false);
    }

    /**
     * SingleSignOnComponent
     * 
     * Set each sign in option within Birds/User/SingleSignOn configuration:
     *
     * SingleSignOn:
     *   url:             (string) Base URL
     *   signOutParam:    (string, default: bye) URL part that forces the idp to disconnect
     *   idp:
     *     $oauthIdp: 
     *       type:        (string, default oauth) oauth|saml|db
     *       appId:       (string) Client or App ID
     *       appSecret:   (string) Client or App secret
     *       callback:    (string, optional) Callback URL part, will be used.
     *       persistent:  (boolean, default: false) If the Oauth object should be preserved under birds::$vars[$oauthIdp]
     * 
     *     # Github example
     *     github:
     *        type:       oauth
     *        appId:      xxxx
     *        appSecret:  xxxx
     */
    public static function SingleSignOnComponent()
    {
        if(!isset(bird::app()->User['SingleSignOn'])) {
            return false;
        }
        $cfg = bird::app()->User['SingleSignOn'];
        $bye = (isset($cfg['signOutParam']) && $cfg['signOutParam'])?($cfg['signOutParam']):('bye');
        $s   = '';
        bird::session();
        @header('X-Frame-Options: GOFORIT'); 
        //unset(bird::$session['oauth']);
        //bird::debug('debug:', var_export(bird::$session, true));


        if(($p=\Birds\bird::urlParam($cfg['url'])) && isset($cfg['idp'][$p[0]])) {
            try {
                $cn = '\\Birds\\Oauth\\'.ucfirst($p[0]);
                $d  = $cfg['idp'][$p[0]];
                if(!isset($d['callback'])) {
                    $d['callback']=$cfg['url'].'/'.$p[0];
                }
                bird::$session['trying']=$p[0];

                $me = new $cn($d['appId'], $d['appSecret'], bird::fullUrl($d['callback']));
                if(isset($d['persistent']) && $d['persistent']) {
                    bird::$vars[$p[0]]=$me;
                }
                // signout
                if(isset(bird::$session[$p[0]]) && isset($p[1]) && $p[1]==$bye) {
                    $me->resetSession();
                    $r = true;
                } else if($me->validateAccessToken()) { // add other auth types before this
                    $me->graph();
                    $r=true;
                }
                unset($me, $cn, $d);
                if(isset($r)) {
                    $req = App::request();
                    $url = (substr($cfg['url'], 0, 1)=='/')?($req['host'].$cfg['url']):($cfg['url']);
                    /*
                    if(isset($_SERVER['HTTP_REFERER']) && substr($_SERVER['HTTP_REFERER'], 0, strlen($req['host']))==$req['host'] && substr($_SERVER['HTTP_REFERER'], 0, strlen($cfg['url']))!=$cfg['url']) {
                        $url = $_SERVER['HTTP_REFERER'];
                    }
                    */
                    unset($req);
                    bird::redirect($url);
                }
            } catch(Exception $e) {
                bird::log(__METHOD__, $e->getMessage());
                $s .= '<h1>Erro!!!</h1>'; // @todo: translate error message
            }
        }

        // @todo: template this!
        $s = '<div id="app-single-sign-on">';
        $end = bird::fullUrl($cfg['url']);
        foreach($cfg['idp'] as $p=>$d) {
            if(isset(bird::$session[$p])) {
                $s .= '<a class="app-button app-user-signed-in app-user-'.$p.'" href="'.bird::fullUrl($cfg['url'].'/'.$p.'/'.$bye).'" data-endpoint="'.$end.'">'
                    . '<span class="app-user-network">'.$p.'</span> '
                    . '<span class="app-user-name">'.bird::xml(bird::$session[$p]['name']).'</span> '
                    . '<span class="app-user-sign-out">(Desconectar)</span>'
                    . '</a>';
            } else {
                $s .= '<a class="app-button app-user-sign-in app-user-'.$p.'" href="'.bird::fullUrl($cfg['url'].'/'.$p).'" data-endpoint="'.$end.'">'
                    . '<span class="app-user-network">'.$p.'</span> '
                    . '</a>';

            }
            unset($p, $d);
        }
        $s .= '</div>';
        //bird::debug($s);
        
        //bird::log('debug:', var_export(bird::$session, true));
        return $s;
    }

}