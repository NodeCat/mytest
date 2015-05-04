<?php
namespace Wms\Controller;
use Think\Controller;

class ConfigController extends Controller {
    public function get_site_config() {
        $M = M('config');
        $map['status'] = '1';
        $config = $M->where($map)->getField('name,value');
        if($config && is_array($config)){
            return $config;
        }
        return null;
    }
    public function clear_runtime() {
        clear_runtime();
        $this->success('缓存删除成功');
    }

}