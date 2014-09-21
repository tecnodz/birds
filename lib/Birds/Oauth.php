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
class Oauth {
    
    protected 
        $appId,
        $appSecret,
        $callback,
    
        $token,
        $tokenSecret,
        $timeout,

        $scope=array(),
    
        $prefix,
        $authUrl,
        $tokenUrl,
        $requestTokenUrl,
        $graphUrl;
    public static 
        $sessionMap;
    
    /**
     * OAuth token/request builder: set the parameters
     * 
     * $me = new \Birds\Oauth($appId, $appSecret, $callback);
     * 
     * 1. Request access token
     * $me->requestAccessToken();
     *
     * 
     */
    public function __construct($appId, $appSecret, $callback){
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->callback = $callback;
        bird::session();
    }

    public function graph()
    {
        if(!isset(bird::$session[$this->prefix])) {
            $g = $this->makeRequest($this->graphUrl);
            if($g &&  is_array(static::$sessionMap)) {
                $s = array();
                foreach(static::$sessionMap as $k=>$v) {
                    if(is_array($v)) {
                        foreach($v as $vk) {
                            if(isset($g[$vk]) && $g[$vk]) {
                                $s[$k]=$g[$vk];
                                unset($vk);
                                break;
                            } else if(strpos($vk, '$')!==false) {
                                $s[$k]=preg_replace('/\$([a-z0-9_]+)/ie', '$g[\'$1\']', $vk);
                                unset($vk);
                                break;
                            }
                            unset($vk);
                        }
                        if(!isset(bird::$session[$this->prefix][$k])) {
                            $s[$k]=false;
                        }
                    } else if(isset($g[$v])) {
                        $s[$k]=$g[$v];
                    } else if(strpos($v, '$')!==false) {
                        $s[$k]=preg_replace('/\$([a-z0-9_]+)/ie', '$g[\'$1\']', $v);
                    }
                    unset($k, $v);
                }
                $s['modified']=BIRD_TIME;
                bird::$session[$this->prefix] = $s;
            } else {
                bird::$session[$this->prefix] = $g;
            }
            unset($g);
        }
        if(!isset(bird::$session['oauth/'.$this->prefix])) {
            bird::$session['oauth/'.$this->prefix] = bird::$session[$this->prefix]['id'];
        }
        //bird::debug(var_export(bird::$session, true), var_export(isset(bird::$session['oauth/'.$this->prefix]), true));
        return bird::$session[$this->prefix];
    }
    
    protected function requestAccessToken($method = 'GET', Array $params=array(), $returnType = 'flat', Array $values = array('access_token', 'expires')){
        // add oauth verifier to parameters for oauth 1.0 request
        if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0 && isset($_GET['oauth_verifier'])){
            $parameters = array('oauth_verifier' => $_GET['oauth_verifier']);
            $parameters = array_merge($parameters, $params);
        }
        // set parameters for oauth 2.0 request
        else {
            $parameters = array(
                'client_id' => $this->appId,
                'redirect_uri' => $this->callback,
                'client_secret' => $this->appSecret,
                'code' => (isset($_GET['code']))?($_GET['code']):(false),
            );
            $parameters = array_merge($parameters, $params);
        }

        // make the request
        $response = $this->makeRequest($this->tokenUrl, $method, $parameters, $returnType, false);

        // get the correct parameters from the response
        $params = $this->getParameters($response, $returnType);

        unset($parameters, $response);
        
        // add the token to the session
        if(isset($params[$values[0]]) && (!isset($values[1]) || isset($params[$values[1]]))) {
            bird::$session[$this->prefix.'AppId'] = $this->appId;
            if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0){
                $this->token = bird::$session[$this->prefix.'AccessToken'] = $params[$values[0]];
                $this->tokenSecret = bird::$session[$this->prefix.'AccessTokenSecret'] = $params[$values[1]];
            } else {
                bird::$session[$this->prefix.'AccessToken'] = $params[$values[0]];
                if(isset($values[1])) {
                    bird::$session[$this->prefix.'Expires'] = time() + $params[$values[1]];
                }
            }
        }
        // throw exception if incorrect parameters were returned
        else {
            $s = '';
            foreach($params as $k => $v){
                $s = $k . '=' . $v;
                unset($k, $v);
            }
            throw new Exception('incorrect access token parameters returned: ' . implode('&', $s));
        }
    }

    public function setAccessToken($access_token, $access_tokenSecret = null, $expires = null){
        $this->token = $access_token;
        $this->tokenSecret = $access_tokenSecret;
        $this->timeout = $expires;
    }
    
    public function setScope(Array $scope){
        $this->scope = $scope;
    }
    
    public function makeRequest($url, $method = 'GET', Array $parameters = array(), $returnType = 'json', $includeCallback = false, $includeVerifier = false){
        // set oauth headers for oauth 1.0
        $ua = 'User-Agent: Birds/Oauth/'.BIRD_VERSION;
        
        if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0){
            $headers = $this->getOauthHeaders($includeCallback);
            if($includeVerifier && isset($_GET['oauth_verifier'])){
                $headers['oauth_verifier'] = $_GET['oauth_verifier'];
                if(isset($_GET['oauth_token']) && !isset($parameters['oauth_token'])) {
                    $parameters['oauth_token']=$_GET['oauth_token'];
                }
            }
            $base_info = $this->buildBaseString($url, $method, array_merge($headers, $parameters));
            $composite_key = $this->getCompositeKey();
            $headers['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
            $header = array($this->buildAuthorizationHeader($headers), 'Expect:', $ua);
            //bird::log($url, $base_info, $composite_key, $header, $headers);
        }
        // add access token to parameter list for oauth 2.0 requests
        else {
            $header = array($ua);
            if(isset(bird::$session[$this->prefix.'AccessToken'])){
                $parameters['access_token'] = bird::$session[$this->prefix.'AccessToken'];
            }
        }

        // create a querystring for GET requests
        if(count($parameters) > 0 && $method == 'GET' && strpos($url, '?') === false){
            $p = array();
            foreach($parameters as $k => $v){
                $p[] = $k . '=' . $v;
            }
            $querystring = implode('&', $p);
            $url = $url . '?' . $querystring;
        }
        
        // set default CURL options
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );
        
        // set post fields for POST requests
        //bird::log(__METHOD__.', '.__LINE__.'--> '.$url, $header);
        if($method == 'POST'){
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($parameters);
            $header[]='Content-Type: application/x-www-form-urlencoded';
        }

        // set CURL headers for oauth 1.0 requests
        if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0){
            $options[CURLOPT_HTTPHEADER] = $header;
            $options[CURLOPT_HEADER] = false;
        } else {
            $options[CURLOPT_HTTPHEADER] = $header;
        }
        
        // make CURL request
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        
        // show error when http_code is not 200
        if($info['http_code'] != 200){
            // mostly errors are thrown when a user has denied access
            $this->resetSession();
            throw new Exception($response);
        }
        
        // return json decoded array or plain response
        if($returnType == 'json'){
            return json_decode($response, true);
        } else {
            return $response;
        }
    }

    public function resetSession()
    {
        return self::resetSessionPrefix($this->prefix);
    }

    public static function resetSessionPrefix($p)
    {
        $l = strlen($p);
        foreach(bird::$session as $k=>$v) {
            if(substr($k, 0, $l)==$p) {
                unset(bird::$session[$k]);
            }
            unset($k, $v);
        }
        if(isset(bird::$session['oauth'][$p])) {
            if(count(bird::$session['oauth'])==1) unset(bird::$session['oauth']);
            else unset(bird::$session['oauth/'.$p]);
        }
        unset($l);
    }
    
    public function validateAccessToken(){
        // check if current token has expired
        if((isset(bird::$session[$this->prefix.'Expires']) && bird::$session[$this->prefix.'Expires'] < time()) || (isset(bird::$session[$this->prefix.'AppId']) && bird::$session[$this->prefix.'AppId']!=$this->appId)) {
            $this->resetSession();
            $this->authorize($this->scope);
            return false;
        }
        // return true if access token is found
        if(isset(bird::$session[$this->prefix.'AccessToken']) || (isset($this->token) && strlen($this->token) > 0)){
            $this->token = bird::$session[$this->prefix.'AccessToken'];
            if(isset(bird::$session[$this->prefix.'AccessTokenSecret'])){
                $this->tokenSecret = bird::$session[$this->prefix.'AccessTokenSecret'];
            }
            if(isset(bird::$session[$this->prefix.'Expires'])){
                $this->timeout = bird::$session[$this->prefix.'Expires'];
            }
            return true;
        }
        // authorize app if no token is found
        if(!isset($this->token) || strlen($this->token) == 0){
            // handle oauth 1.0 flow
            if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0){
                // request token and authorize app
                if(!isset($_GET['oauth_token']) && !isset($_GET['oauth_verifier'])){
                    $this->requestToken();
                    $this->authorize();
                    return false;
                }
                // request access token
                else {
                    if(isset(bird::$session[$this->prefix.'Token']) && $_GET['oauth_token'] != bird::$session[$this->prefix.'Token']){
                        unset(bird::$session[$this->prefix.'Token'], bird::$session[$this->prefix.'TokenSecret']);
                        return false;
                    } else {
                        $this->requestAccessToken();
                        //unset(bird::$session[$this->prefix.'Token'], bird::$session[$this->prefix.'TokenSecret']);
                        return true;
                    }
                }
            }
            // handle oauth 2.0 flow
            else {
                // authorize app
                if(!isset($_GET['state']) && !isset($_GET['code'])){
                    $this->authorize($this->scope);
                    return false;
                }
                // request access token
                else {
                    if($_GET['state'] != bird::$session[$this->prefix.'State']){
                        unset(bird::$session[$this->prefix.'State']);
                        return false;
                    } else {
                        unset(bird::$session[$this->prefix.'State']);
                        $this->requestAccessToken();
                        return true;
                    }
                }
            }
        }
    }
    
    protected function requestToken($returnType = 'flat', Array $values = array('oauth_token', 'oauth_token_secret'))
    {
        // make the request
        $response = $this->makeRequest($this->requestTokenUrl, 'POST', array(), $returnType, true);
        
        // get the correct parameters from the response
        $params = $this->getParameters($response, $returnType);

        // add the token and token secret to the session
        if(isset($params[$values[0]]) && isset($params[$values[1]])){
            bird::$session[$this->prefix.'Token'] = $params[$values[0]];
            bird::$session[$this->prefix.'TokenSecret'] = $params[$values[1]];
        }
        // throw exception if incorrect parameters were returned
        else {
            $s = '';
            foreach($params as $k => $v){$s = $k . '=' . $v;}
            throw new Exception('incorrect access token parameters returned: ' . implode('&', $s));
        }
    }
    
    
    protected function authorize(Array $scope = array(), $scope_seperator = ',', $attach = null)
    {
        $this->authUrl .= (strpos($this->authUrl, '?'))?('&'):('?');
        // build authorize url for oauth 1.0 requests
        if(isset($this->requestTokenUrl) && strlen($this->requestTokenUrl) > 0){
            $this->authUrl .= 'oauth_token=' . bird::$session[$this->prefix.'Token'];
        }
        // build authorize url for oauth 2.0 requests
        else {
            $this->authUrl .= 'client_id=' . $this->appId . '&redirect_uri=' . $this->callback;
            $state = md5(time() . mt_rand());
            bird::$session[$this->prefix.'State'] = $state;
            $this->authUrl .= '&state=' . $state . '&scope=' . implode($scope_seperator, $scope) . $attach;
        }
        // redirect
        header('Location: ' . $this->authUrl);
        bird::end();
    }
    
    private function getParameters($response, $returnType)
    {
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
        return $params;
    }
    
    private function getCompositeKey()
    {
        if(isset($this->tokenSecret) && strlen($this->tokenSecret) > 0){
            $composite_key = rawurlencode($this->appSecret) . '&' . rawurlencode($this->tokenSecret);
        } else if(isset(bird::$session[$this->prefix.'TokenSecret'])){
            $composite_key = rawurlencode($this->appSecret) . '&' . rawurlencode(bird::$session[$this->prefix.'TokenSecret']);
        } else {
            $composite_key = rawurlencode($this->appSecret) . '&';
        }
        return $composite_key;
    }
    
    private function getOauthHeaders($includeCallback = false)
    {
        $oauth = array(
            'oauth_consumer_key' => $this->appId,
            'oauth_nonce' => uniqid(),//time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
        );
        if(isset($this->token)){
            $oauth['oauth_token'] = $this->token;
        } else if(isset(bird::$session[$this->prefix.'Token'])){
            $oauth['oauth_token'] = bird::$session[$this->prefix.'Token'];
        }
        $oauth['oauth_version'] = '1.0';
        if($includeCallback){
            $oauth['oauth_callback'] = $this->callback;
        }
        return $oauth;
    }
    
    private function buildBaseString($baseURI, $method, $params)
    {
        $r = array();
        if($p=strpos($baseURI, '?')) {
            parse_str(substr($baseURI, $p+1), $a);
            $params+=$a;
            $baseURI = substr($baseURI, 0, $p);
            unset($a, $p);
        }
        ksort($params);
        foreach($params as $key => $value){
            $r[] = $key . '=' . rawurlencode($value);
        }
        return $method . '&' . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }
    
    private function buildAuthorizationHeader($oauth)
    {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key => $value){
            $values[] = $key . '="' . rawurlencode($value) . '"';
        }
        $r .= implode(', ', $values);
        return $r;
    }
    
}