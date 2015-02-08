<?php
/**
* Author: Tyler Holubeshen 
* Description: The webrequest class will aid in making HTTP/HTTPS requests
* Supports multiple cookies and sessions. 
*/
class request {
	
	public $url; //is a string that represents the address of the website
	public $param; //is an array of keys and values that will build a query string 
	public $type; //is a string that represets the request type
	public $cookie; //is a boolean that represents if cookies have been enabled
	public $cookieName; //is a string that represents the cookies name
	public $cookieDirectory; // is a string that represents the directory in which the cookies will be saved in
	public $cookies; // is the cookies that were sent in the request
	public $cache;
	public $cacheDir;
	public $ssl; //is a boolean that represents if https is to be used
	public $agent; //is a string that represents the alias in which the requests are being made from
	public $directory; //is a string that represents the directory in which the script is located in
	public $headerVar;//is an array of strings that can be used for the header of the request
	public $referer;//is a string that represents the referer for the request 
	public $info; //curl request debug info
	public $header; //is a string that represents the header of the request
	public $body; //is a string that represents the body of the request

	
	function __construct($extend = null){
		//set some default variables for the class
		$this->agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36';
		$this->ssl = false;
		$this->cookie = false;
		$this->cookieName = 'cookie';
		$this->directory = getcwd();
		$this->cookieDirectory = $this->directory.'\\cookies';
		$this->cache = $this->directory.'\\cache';
		
		
		//creates the cookies directory if it is not already found
		if (!file_exists($this->cookieDirectory)) 
			mkdir($this->cookieDirectory, 0777, true);
			
		//extend using a given object
		if(isset($extend))
			$this->extend($extend);
		
		
		if(count($_GET) > 0){
			$this->extend($_GET);
			$this->{$this->type}();
		}
		
		if(count($_POST) > 0){
			$this->extend($_POST);
			$this->{$this->type}();
		}
	
	}
	/**
	* extend - will extend the class object's variables using json, an object or an associative array
	*/
	public function extend($var){
		
		//if there is a json string convert it into an object
		if(is_string($var))
			$var = json_decode($var);
			
		//if an object is found cast the object to an associative array
		if(is_object($var))
			$var = get_object_vars($var);
			
		//extends the class object with the given object
		if(is_array($var) && count($var) > 0){
			foreach(array_keys($var) as $key){
				$this->{$key} = $var[$key];
			}
		}
	}
	/**
	* post - will make a page request using the post type.
	*/
	public function post(){
		
		$options = curl_init($this->url);
		curl_setopt($options, CURLOPT_POST, TRUE);
		curl_setopt($options, CURLOPT_POSTFIELDS, http_build_query($this->param));
		curl_setopt($options, CURLOPT_VERBOSE, TRUE);
		curl_setopt($options, CURLOPT_USERAGENT,$this->agent);
		curl_setopt($options, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($options, CURLOPT_SSL_VERIFYPEER, $this->ssl);
		curl_setopt($options, CURLOPT_SSL_VERIFYHOST, $this->ssl);
		curl_setopt($options, CURLOPT_HEADER, 1);
		curl_setopt($options, CURLINFO_HEADER_OUT, true);
		curl_setopt($options, CURLOPT_FOLLOWLOCATION, 1);
		
		if(isset($this->referer))
			curl_setopt($options, CURLOPT_REFERER, $this->referer);
		
		if(isset($this->headerVar))
			curl_setopt($options, CURLOPT_HTTPHEADER, $this->headerVar);
		
		if($this->cookie){
			curl_setopt($options, CURLOPT_COOKIEJAR, $this->cookieDirectory.'\\'.$this->cookieName.'.txt');
			curl_setopt($options, CURLOPT_COOKIEFILE, $this->cookieDirectory.'\\'.$this->cookieName.'.txt');
		}
	
		$this->seperate_data(curl_exec($options), curl_getinfo($options, CURLINFO_HEADER_SIZE));
		$this->info = curl_getinfo($options, CURLINFO_HEADER_OUT);
		$this->get_cookies();
		$this->param = null;
		curl_close($options);
	}
	/**
	* get - will make a page request using the get type.
	*/
	public function get(){
		
		//If there are any param build url
		if($this->param != null){
			$this->url = $this->url."?".http_build_query($this->param);
		}
		
		$options = curl_init($this->url);
		curl_setopt($options, CURLOPT_VERBOSE, TRUE);
		curl_setopt($options, CURLOPT_USERAGENT,$this->agent);
		curl_setopt($options, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($options, CURLOPT_SSL_VERIFYPEER, $this->ssl);
		curl_setopt($options, CURLOPT_SSL_VERIFYHOST, $this->ssl);
		curl_setopt($options, CURLOPT_HEADER, 1);
		curl_setopt($options, CURLINFO_HEADER_OUT, true);		
		curl_setopt($options, CURLOPT_FOLLOWLOCATION, 1);
		
		if(isset($this->referer))
			curl_setopt($options, CURLOPT_REFERER, $this->referer);
		
		if(isset($this->headerVar))
			curl_setopt($options, CURLOPT_HTTPHEADER, $this->headerVar);
		
		if($this->cookie){
			curl_setopt($options, CURLOPT_COOKIEJAR, $this->cookieDirectory.'\\'.$this->cookieName.'.txt');
			curl_setopt($options, CURLOPT_COOKIEFILE, $this->cookieDirectory.'\\'.$this->cookieName.'.txt');
		}
		
		$this->seperate_data(curl_exec($options), curl_getinfo($options, CURLINFO_HEADER_SIZE));
		$this->info = curl_getinfo($options);
		$this->get_cookies();
		$this->param = null;
		curl_close($options);
	}
	/**
	* seperate_data - will seperate the header and the body from the page data
	*	$page - the page data from the curl request
	*	$header_size - the size of the header
	*/
	public function seperate_data($page, $header_size){
	
		$this->header = substr($page, 0, $header_size);
		$this->body = substr($page, $header_size);
		
	}
	/**
	* get_cookies - will return an array of all the cookies being used in the request
	*/
	public function get_cookies(){
	
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $this->header, $temp);
		foreach($temp[1] as $var){
			$exploded = explode('=', $var);
			$this->cookies[$exploded[0]] = $exploded[1];
		}
		
	}
}
?>
