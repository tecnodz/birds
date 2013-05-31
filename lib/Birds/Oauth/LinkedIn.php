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
namespace Birds;
class Oauth\LinkedIn extends \Birds\Oauth {
	
	protected 
		$prefix = 'linkedin',
		$authUrl = 'https://www.linkedin.com/uas/oauth/authorize',
		$tokenUrl = 'https://api.linkedin.com/uas/oauth/accessToken',
		$requestTokenUrl = 'https://api.linkedin.com/uas/oauth/requestToken';
	
	public function requestAccessToken($method = 'GET', Array $params = array(), $returnType = 'flat', Array $values = array('oauth_token', 'oauth_token_secret')){
		$response = $this->makeRequest($this->_access_token_url, 'POST', array(), $returnType, false, true);
		
		if($returnType != 'json'){
			$r = explode('&', $response);
			$params = array();
			foreach($r as $v){
				$param = explode('=', $v);
				$params[$param[0]] = $param[1];
			}
		} else {
			$params = $response;
		}
		
		if(isset($params[$values[0]]) && isset($params[$values[1]])){
			$_SESSION[$this->_prefix]['access_token'] = $params[$values[0]];
			$_SESSION[$this->_prefix]['access_token_secret'] = $params[$values[1]];
		} else {
			$s = '';
			foreach($params as $k => $v){
				$s = $k . '=' . $v;
			}
			throw new Exception('incorrect access token parameters returned: ' . implode('&', $s));
		}
	}
	
}