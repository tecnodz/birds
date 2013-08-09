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
class Twitter extends \Birds\Oauth {
    
    protected 
        $prefix = 'twitter',
        $authUrl = 'https://api.twitter.com/oauth/authorize',
        $tokenUrl = 'https://api.twitter.com/oauth/access_token',
        $requestTokenUrl = 'https://api.twitter.com/oauth/request_token',
        $graphUrl = 'https://api.twitter.com/1.1/account/verify_credentials.json?skip_status=1',
        $scope,
        $session;
    public static
        $sessionMap=array('id'=>'id', 'name'=>'name', 'username'=>'screen_name', 'image'=>array('profile_image_url_https','profile_image_url','default_profile_image'));

    
    public function requestAccessToken($method = 'POST', Array $params = array(), $returnType = 'flat', Array $values = array('oauth_token', 'oauth_token_secret')){
        parent::requestAccessToken($method, $params, $returnType, $values);
    }
    
}