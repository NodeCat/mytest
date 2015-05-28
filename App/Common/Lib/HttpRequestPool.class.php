<?php

class HttpRequestPool {
    public static $mh = null;
    public static $request_pool = array();
    public static $select_timeout = 0.01;
    
    public static $curl_state;
    public static $curl_pool;
    
    public static function get_curl($host_id) {
        // TODO max 20 curl
        if (isset(self::$curl_state[$host_id])) {
            $ch = self::get_curl_from_pool($host_id);
            if ($ch === false) {
                $ch = self::get_curl_create($host_id);
            }
        } else {
            $ch = self::get_curl_create($host_id);
        }
        
        return $ch;
    }
    
    public static function attach(HttpRequest $http_request) {
        $tmp = self::$request_pool;
        self::$request_pool[] = $http_request;
    }
    
    public static function send($force_all = false) {
        if (empty(self::$request_pool)) {
            throw new Exception('request pool is empty');
        }
        
        if (count(self::$request_pool) == 1) {
            self::$request_pool[0]->send();
            self::$request_pool = array();
            return;
        }
        
        if (self::$mh == null) {
            self::$mh = curl_multi_init();
        }
        
        // 由于在attach时尚未分配curl，故没有curl_id :(
        $curl_request_map = array();
        foreach (self::$request_pool as $request) {
            $request->curl_init();
            $curl_request_map[$request->get_curl_id()] = $request;
            curl_multi_add_handle(self::$mh, $request->get_ch());
        }
        
        $running = null;
        do {
            $mh_rtn = curl_multi_exec(self::$mh, $running);
            if ($mh_rtn == CURLM_CALL_MULTI_PERFORM) {
                continue;
            }
            curl_multi_select(self::$mh, self::$select_timeout);
            
            // 多个请求中单个请求的状态只有在请求期间才能获取，事后只能获取true or false，没有msg
            while ($rst = curl_multi_info_read(self::$mh, $queue_point)) {
                $curl_id = HttpRequest::fetch_curl_id($rst['handle']);
                $request = $curl_request_map[$curl_id];
                $content = curl_multi_getcontent($request->get_ch());
                $info = curl_getinfo($request->get_ch());
                
                if ($rst['result'] == CURLE_OK) {
                    $request->set_response_state(true, "");
                    $request->set_response($content, $info);
                } else {
                    $error_msg = curl_error($rst['handle']);
                    $request->set_response_state(false, $error_msg);
                    $request->set_response($content, $info, false);
                    
                    if (!$force_all) {
                        self::clean_up();
                        // TODO special exception ?
                        throw new Exception("Request " . $request->url . ": " . $error_msg);
                    }
                }
            }
            
            /*
             * 当force_all为false
             * curl_multi_info_read 没有数据就返回返回false，等于false不代表发生错误或执行完毕
             * 有数据返回也不代表没有错误，在force_all = false 的情况下，为了确保发现错误第一时间退出
             * 增加一次遍历查找
             */
            /*
            if (!$force_all || $mh_rtn != CURLM_OK) {
                // 查找是哪个请求出错，找不到啊找不到！！！
                foreach ($curl_request_map as $curl_id => $request) {
                    if (curl_errno($request->get_ch())) {
                        $error_msg = curl_error($rst['handle']);
                        $request->set_state(false, $error_msg);
                        $content = curl_multi_getcontent($request->get_ch());
                        $info = curl_getinfo($request->get_ch());
                        $request->save_response($content, $info, false);
                        
                        self::clean_up();
                        // TODO special exception ?
                        throw new Comm_Exception_Program("Request " . $request->url . ": " . $error_msg);
                    }
                }
            }
            */
        } while ($running);
        
        self::clean_up();
    }
    
    public static function clean_up() {
        self::reset_curl_state_all();
        foreach (self::$request_pool as $request) {
            $request->reset_ch();
            if (is_resource($request->get_ch())) {
                curl_multi_remove_handle(self::$mh, $request->get_ch());
                curl_close($request->get_ch());
            }
        }
        self::$request_pool = array();
    }
    
    public static function reset_curl_state($host_id, $curl_id) {
        if (isset(self::$curl_state[$host_id][$curl_id])) {
            self::$curl_state[$host_id][$curl_id] = true;
        }
    }
    
    public static function reset_curl_state_all() {
        foreach (self::$curl_state as $host_id => $states) {
            foreach ($states as $curl_id => $state) {
                self::$curl_state[$host_id][$curl_id] = true;
            }
        }
    }
    
    public static function get_avail_curl_count($array = "") {
        if (empty($array)) {
            $array = self::$curl_state;
        }
        
        $i = 0;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $i += self::get_avail_curl_count($value);
            } else {
                if ($value) {
                    $i ++;
                }
            }
        }
        
        return $i;
    }
    
    public static function get_all_curl_count() {
        $count = 0;
        foreach (self::$curl_state as $host => $states) {
            $count += count($states);
        }
        return $count;
    }
    
    private static function get_curl_create($host_id) {
        $ch = curl_init();
        $curl_id = HttpRequest::fetch_curl_id($ch);
        self::$curl_state[$host_id][$curl_id] = false;
        self::$curl_pool[$curl_id] = $ch;
        return $ch;
    }
    
    private static function get_curl_from_pool($host_id) {
        foreach (self::$curl_state[$host_id] as $curl_id => $state) {
            if ($state) {
                self::$curl_state[$host_id][$curl_id] = false;
                return self::$curl_pool[$curl_id];
            }
        }
        
        return false;
    }
}