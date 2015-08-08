<?php
namespace Erp\Api;
use Think\Controller;
class WarehouseApi extends Controller{
    
    public function get_warehouse() {
        if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
            $post = json_decode(file_get_contents("php://input"),true);
        }
        else{
            $post = I('post.');
        }

        $warehouse = M('warehouse');
        $map['is_deleted'] = 0;
        $map['status'] = 2;

        $res = $warehouse->field('id, name')->where($map)->select();      
        if(empty($res)) {
            $return = array('error_code' => '401', 'error_message' => 'get warehouse error', 'data' => '' );
            $this->ajaxReturn($return);
        }else {

            $return = array('error_code' => '0', 'error_message' => 'success', 'data' => $res );
            $this->ajaxReturn($return);
        }
    }
    
}
