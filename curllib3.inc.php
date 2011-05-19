<?php
/*
Sean Huber CURL library

This library is a basic implementation of CURL capabilities.
It works in most modern versions of IE and FF.

==================================== USAGE ====================================
It exports the CURL object globally, so set a callback with setCallback($func).
(Use setCallback(array('class_name', 'func_name')) to set a callback as a func
that lies within a different class)
Then use one of the CURL request methods:

get($url);
post($url, $vars); vars is a urlencoded string in query string format.

Your callback function will then be called with 1 argument, the response text.
If a callback is not defined, your request will return the response text.

================================================================================
Modyfikacja by ToyotaSupra, 26.03.2007

post($url, $vars, $proxy, $timeout, $referer)
get($url, $proxy, $timeout, $referer)

$proxy - proxy prze jakie sie laczymy - jezeli pusty string - nie uzywaj proxy
$timeout - timeout w seksundach - jesli 0 - no timeuot
$referer - czasem sie przydaje :)
================================================================================
Modyfikacja by ToyotaSupra, 13.09.2007

get($url, $proxy, $timeout, $referer, $cookie, $interface)
post($url, $vars, $proxy, $timeout, $referer, $cookie, $interface)

$cookie - ciacho w formacie z headera
$interface - interface (zeby spa... znaczy łączyć się z kilku IP)
================================================================================
Modyfikacja by ToyotaSupra, 13.09.2007

poprawione proxy zeby dzialalo z listą proxy od ruska :)
================================================================================
Modyfikacja by ToyotaSupra, 10.07.2009

dodano mozliwosc postowania ze swoim headerem, pomocne przy spamowaniu mulipartów  :)
*/

class CURL {
   var $callback = false;

function setCallback($func_name) {
   $this->callback = $func_name;
}

function doRequest($method, $url, $vars, $proxy, $timeout, $referer, $cookie, $interface,$headers=null) {
   //echo "*** curl_debug: proxy: $proxy\n";
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_HEADER, 0);
   //curl_setopt($ch, CURLOPT_VERBOSE, 1); /* debug mode */
   if (isset($headers)) curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
   //curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)');
   if ($proxy!='') 
	{
		if (strstr($proxy, '@SOCKS5')){
		$proxyType = CURLPROXY_SOCKS5;
		$proxy=str_replace('@SOCKS5','',$proxy);}
		if (strstr($proxy, '@SOCKS4')){
		$proxyType = CURLPROXY_SOCKS4;
		$proxy=str_replace('@SOCKS4','',$proxy);}
		
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
		if (isset($proxyType)){ curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType);}
   	}
   if ($referer!='') curl_setopt($ch, CURLOPT_REFERER, $referer);
   if ($cookie!='') {curl_setopt($ch, CURLOPT_COOKIE, $cookie);}
     else {curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');}
   if ($interface!='') curl_setopt($ch, CURLOPT_INTERFACE, $interface);
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   if ($timeout) curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
   if ($method == 'POST') {
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
   }
   $data = curl_exec($ch);
   if (!$data) {$curlerr=curl_error($ch);echo "*** curllib: ".$curlerr."\n";}
   curl_close($ch);
   if ($data) {
       if ($this->callback)
       {
           $callback = $this->callback;
           $this->callback = false;
           return call_user_func($callback, $data);
       } else {
           return $data;
       }
   } else {
       return $curlerr;
   }

}

function get($url, $proxy="", $timeout=30, $referer="", $cookie="", $interface="") {
   return $this->doRequest('GET', $url, 'NULL', $proxy, $timeout, $referer, $cookie, $interface);
}

function post($url, $vars, $proxy="", $timeout=30, $referer="", $cookie="", $interface="") {
   return $this->doRequest('POST', $url, $vars, $proxy, $timeout, $referer, $cookie, $interface);
}

function post_with_headers($url, $vars, $proxy, $timeout, $referer, $cookie, $interface,$headers) {
   return $this->doRequest('POST', $url, $vars, $proxy, $timeout, $referer, $cookie, $interface,$headers);
}

}
?>
