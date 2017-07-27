<?php
//***************************************************//
//													 //
//				Class for cURL		                 //
//	 *********************************************   //
//	 Author: Stsepanchuk Ruslan						 //
//   *********************************************   //
//	 Version 3.0.8									 //
//   *********************************************   //
//   Web-site: swordmedia.ru						 //
//   *********************************************   //
//   Blog: swblog.ru								 //
//                                                   //
//***************************************************//

class Curl {

	var $ch;
	var $httpget = '';	
	var $head = '';
	var $is_post = false;
	var $postparams = null;
	var $httpheaders = [];
	var $cookies = [];
	var $proxy = '';
	var $proxy_type = '';
	var $proxy_user_data = '';
	var $verbose = 0;
	var $referer = '';
	var $autoreferer = 0;
	var $writeheader = '';
	var $agent = 'Mozilla/5.0 (Windows NT 5.1; rv:23.0) Gecko/20100101 Firefox/23.0';
	var $url = '';	
	var $followlocation = 1;
	var $returntransfer = 1;
	var $ssl_verifypeer = 0;
	var $ssl_verifyhost = 2;
	var $sslcert = '';
	var $sslkey = '';
	var $cainfo = '';
	var $cookiefile = '';
	var $timeout = 0;
	var $connect_time = 0;
	var $encoding = 'deflate';	
	var $interface = '';
	
	function __construct (){
		$this->ch = curl_init();
		$this->add_httpheaders(array('Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language' => 'ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3', 'Accept-Charset' => 'windows-1251,utf-8;q=0.7,*;q=0.7'));
	}
	
	function get ($url){
		$this->url = $url;
		return $this->exec();
	}
	
	function post ($url, $postparams = null){
		$this->url = $url;		
		$this->is_post = true;
		
		$this->postparams = $postparams;
		
		return $this->exec();
	}
	
	function set_httpget ($httpget){
		$this->httpget = $httpget;
	}
	
	function set_referer ($referer){
		$this->referer = $referer;
	}
	
	function set_autoreferer ($autoreferer){
		$this->autoreferer = $autoreferer;
	}
	
	function set_useragent ($agent){
		$this->agent = $agent;
	}	
	
	function set_cookie (){
		preg_match_all('/Set-Cookie: (.*?)=(.*?);/i', $this->head, $matches, PREG_SET_ORDER);
		
		for ($i = 0; $i < count($matches); $i++) {
			if ($matches[$i][2] == 'deleted') {
				$this->delete_cookies(array($matches[$i][1]));
			} else {
				$this->cookies[$matches[$i][1]] = $matches[$i][2];
			}
		}	
	}
	
	function add_cookies ($cookies){
		foreach ($cookies as $name => $value) {
			$this->cookies[$name] = $value;
		}
	}
	
	function delete_cookies ($cookies){
		foreach ($cookies as $name) {
			if (isset($this->cookies[$name]))
				unset($this->cookies[$name]);
		}
	}
	
	function get_cookies (){
		return $this->cookies;
	}
	
	function clear_cookie (){
		$this->cookies = array ();
	}
	
	function add_httpheaders ($headers){
		foreach ($headers as $name => $value) {
			$this->httpheaders[$name] = $name . ': ' . $value;
		}
	}
	
	function delete_httpheaders ($headers){
		foreach ($headers as $name) {
			if (isset($this->httpheaders[$name]))
				unset($this->httpheaders[$name]);
		}
	}
	
	function get_httpheader (){
		return $this->httpheaders;
	}
	
	function clear_httpheaders (){
		$this->httpheaders = array ();
	}
	
	function set_head ($head){
		$this->head = $head;
	}
	
	function set_encoding ($encoding){
		$this->encoding = $encoding;
	}	
	
	function set_interface ($interface){
		$this->interface = $interface;
	}

	function set_writeheader ($writeheader){	
		$this->writeheader = $writeheader;
	}

	function set_followlocation ($followlocation){
		$this->followlocation = $followlocation;
	}

	function set_returntransfer ($returntransfer){
		$this->returntransfer = $returntransfer;
	}
	
	function set_ssl_verifypeer ($ssl_verifypeer){
		$this->ssl_verifypeer = $ssl_verifypeer;
	}
	
	function set_ssl_verifyhost ($ssl_verifyhost){
		$this->ssl_verifyhost = $ssl_verifyhost;
	}
	
	function set_sslcert ($sslcert) {
		$this->sslcert = $sslcert;
	}
	
	function set_sslkey ($sslkey) {
		$this->sslkey = $sslkey;
	}
	
	function set_cainfo ($cainfo) {
		$this->cainfo = $cainfo;
	}
	
	function set_timeout ($timeout){
		$this->timeout = $timeout;
	}
	
	function set_connect_time ($connect_time){
		$this->connect_time = $connect_time;
	}
	
	function set_cookiefile ($cookiefile){
		$this->cookiefile = $cookiefile;
	}

	function set_proxy ($proxy, $proxy_type = ''){
		$this->proxy = $proxy;
		$this->proxy_type = $proxy_type;
	}
	
	function set_proxy_auth ($proxy_user_data){
		$this->proxy_user_data = $proxy_user_data;
	}
	
	function set_verbose ($verbose){
		$this->verbose = $verbose;
	}
	
	function get_error (){
		return curl_errno($this->ch);
	}
	
	function get_location (){
		$result = '';
		
		if (preg_match("/Location: (.*?)\r\n/is", $this->head, $matches)) {
			$result = end($matches);
		}
	
		return $result;
	}
	
	function get_http_code (){
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}
	
	function get_speed_download (){
		return curl_getinfo($this->ch, CURLINFO_SPEED_DOWNLOAD);
	}
	
	function get_content_type (){
		return curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
	}
	
	function get_url (){
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}
	
	function join_cookie() {
		$result = [];
		foreach ($this->cookies as $key => $value)
			$result[] = "$key=$value";
		return join('; ', $result);
	}
	
	function exec (){
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, $this->autoreferer);
		curl_setopt($this->ch, CURLOPT_ENCODING, $this->encoding);
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_POST, $this->is_post);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION , $this->followlocation);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,$this->returntransfer);	
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, $this->ssl_verifyhost);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->connect_time);
		curl_setopt($this->ch, CURLOPT_VERBOSE, $this->verbose);
		
		if ($this->referer)
			curl_setopt($this->ch, CURLOPT_REFERER, $this->referer);			
		
		if ($this->interface)
			curl_setopt($this->ch, CURLOPT_INTERFACE, $this->interface);
		
		if ($this->httpget)
			curl_setopt($this->ch, CURLOPT_HTTPGET, $this->httpget);
		
		if ($this->writeheader != '')
			curl_setopt($this->ch, CURLOPT_WRITEHEADER, $this->writeheader);		

		if ($this->is_post) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postparams);
		}
		
		if ($this->proxy)
			curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
			
		if ($this->proxy_type == 'socks5')
			curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		
		if ($this->proxy_user_data)
			curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->proxy_user_data);

		if ($this->cookies)
			curl_setopt($this->ch, CURLOPT_COOKIE, $this->join_cookie());
		
		if (count($this->httpheaders))
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->httpheaders);

		if ($this->sslcert)
			curl_setopt($this->ch, CURLOPT_SSLCERT, $this->sslcert);
			
		if ($this->sslkey)
			curl_setopt($this->ch, CURLOPT_SSLKEY, $this->sslkey);
			
		if ($this->cainfo)
			curl_setopt($this->ch, CURLOPT_CAINFO, $this->cainfo);
		
		if ($this->cookiefile) {		
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookiefile);
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookiefile);
		}		
		
		$response = curl_exec($this->ch);
		$this->set_head(substr($response, 0, curl_getinfo($this->ch, CURLINFO_HEADER_SIZE)));
		$response = substr($response, curl_getinfo($this->ch, CURLINFO_HEADER_SIZE));
		$this->set_cookie();
		
		$this->postparams = null;
		$this->is_post = false;
		
		return $response;
	}
	
	function __destruct (){
		curl_close($this->ch);
	}
}
?>