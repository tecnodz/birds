<?php
/**
 * OAuth authentication
 *
 * This package enables various OAuth SSO
 *
 * PHP version 5.3
 *
 * @category  Oauth
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @version   SVN: $Id$
 * @link      http://tecnodz.com/
 */

/**
 * OAuth authentication
 *
 * This package enables various OAuth SSO
 *
 * @category  Oauth
 * @package   Birds
 * @author    Guilherme Capilé, Tecnodesign <ti@tecnodz.com>
 * @copyright 2013 Tecnodesign
 * @license   http://creativecommons.org/licenses/by/3.0  CC BY 3.0
 * @link      http://tecnodz.com/
 */
namespace Birds\Oauth;
class Github extends \Birds\Oauth {
    
    protected 
        $prefix = 'github',
        $authUrl = 'https://github.com/login/oauth/authorize',
        $tokenUrl = 'https://github.com/login/oauth/access_token',
        //$requestTokenUrl = 'https://github.com/login/oauth/access_token',
        $graphUrl = 'https://api.github.com/user';
    
    public function requestAccessToken($method = 'POST', Array $params = array(), $returnType = 'flat', Array $values = array('access_token')){
        parent::requestAccessToken($method, $params, $returnType, $values);
    }

    public function graph()
    {
        if(!isset(\Birds\bird::$session[$this->prefix])) {
            \Birds\bird::$session[$this->prefix] = $this->makeRequest($this->graphUrl.'?access_token='.\Birds\bird::$session[$this->prefix.'AccessToken']);
        }
        return \Birds\bird::$session[$this->prefix];
    }
    
}