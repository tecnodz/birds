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
class Microsoft extends \Birds\Oauth {
    
    protected 
        $prefix = 'microsoft',
        $authUrl = 'https://login.live.com/oauth20_authorize.srf?response_type=code',
        $tokenUrl = 'https://login.live.com/oauth20_token.srf',
        $graphUrl = 'https://apis.live.net/v5.0/me',
        $scope = array('wl.basic');
    
    public function requestAccessToken($method = 'POST', Array $params = array(), $returnType = 'json', Array $values = array('access_token')){
    	$params['grant_type']='authorization_code';
        parent::requestAccessToken($method, $params, $returnType, $values);
    }
    
}