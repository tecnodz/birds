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
class Google extends \Birds\Oauth {
	
	protected
		$prefix = 'google',
		$authUrl = 'https://accounts.google.com/o/oauth2/auth',
		$tokenUrl = 'https://accounts.google.com/o/oauth2/token',
		$graphUrl = 'https://www.googleapis.com/oauth2/v1/userinfo',
		$scope = array('openid', 'profile', 'email');
    public static
        $sessionMap=array('id'=>'id', 'name'=>'name', 'image'=>'picture', 'email'=>'email');
	
	protected function authorize(Array $scope = array(), $scope_seperator = '+'){
		parent::authorize($scope, $scope_seperator, '&response_type=code');
	}
	
	protected function requestAccessToken($method = 'POST', Array $params = array('grant_type' => 'authorization_code'), $returnType = 'json', Array $values = array('access_token', 'expires_in')){
		parent::requestAccessToken($method, $params, $returnType, $values);
	}
	
}