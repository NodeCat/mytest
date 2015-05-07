<?php
/**
* curl封装类
* @author liang
* @example
import("Common.Lib.HttpCurl");
$request = new \HttpCurl();
$re = $request->get('http://dachuwang.com');
var_dump($re);exit;
*/
class HttpCurl{
	public function get($url, $args = array(), $option = array(), $urlencode = false){
		import("Common.Lib.HttpRequest");
		$httpRequest = new \HttpRequest($url);
		$httpRequest->set_method("GET");
		foreach($args as $key => $value){
            $httpRequest->add_query_field($key, $value,$urlencode);
        }
        // timeout
        if(isset($options['timeout'])){
            $httpRequest->set_timeout($options['timeout']);
        }else{
        	$httpRequest->set_timeout(3000);
        }
        if(isset($options['connect_timeout'])){
            $httpRequest->set_connect_timeout($options['connect_timeout']);
        }else{
        	$httpRequest->set_connect_timeout(3000);
        }

        $httpRequest->send();
        $response = $httpRequest->get_response_content();
		return $response;
	}
}