<?php
/**
* curl封装类
* @author liang
* @example
* import("Common.Lib.HttpCurl");
* $request = new \HttpCurl();
* $data = array(
*   'currentPage' => 1,
*   'itemsPerPage' => 15,
*   'where' => array('like'=>array('name'=>$pro_name)),
* );
* $url = 'http://s.test.dachuwang.com/sku/manage';
* $json_data = json_encode($data);
* $result = $request->post($url,$json_data);
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
        	$httpRequest->set_timeout(5000);
        }
        if(isset($options['connect_timeout'])){
            $httpRequest->set_connect_timeout($options['connect_timeout']);
        }else{
        	$httpRequest->set_connect_timeout(5000);
        }

        $httpRequest->send();
        $response = $httpRequest->get_response_content();
		return $response;
	}

    public function post($url, $args, $options = array(), $urlencode = false, $file = array()){
        //默认是post_json数据
        if(empty($options['post_json'])){
            $post_json = true;
        }
        import("Common.Lib.HttpRequest");
        $httpRequest = new \HttpRequest($url);
        $httpRequest->set_method("POST");
        if($post_json){
            $httpRequest->post_fields = $args;
        }else{
            foreach($args as $key => $value){
                $httpRequest->add_post_field($key, $value,$urlencode);
            }
        }
        if ($file) {
            $httpRequest->add_post_file($file['name'], $file['dir']);
        }
        // timeout
        if(isset($options['timeout'])){
            $httpRequest->set_timeout($options['timeout']);
        }else{
            $httpRequest->set_timeout(5000);
        }
        if(isset($options['connect_timeout'])){
            $httpRequest->set_connect_timeout($options['connect_timeout']);
        }else{
            $httpRequest->set_connect_timeout(5000);
        }

        if(isset($options['header'])){
            foreach($options['header'] as $key => $value){
                $httpRequest->add_header($key,$value);
            }
        }

        if($post_json){
            $httpRequest->add_header('Content-Type','application/json; charset=utf-8');
            //$httpRequest->add_header('Content-Length',strlen($args));
        }

        $httpRequest->send();
        $response = $httpRequest->get_response_content();
        return $response;
    }
}