<?php
namespace Wms\Api;
use Think\Controller;
class WeightApi extends Controller{
    
    public function weight() {
        $A = A('Wms/Order','Logic');
        $res = $A->weight_sku($map);
        $this->ajaxReturn($res);
    }   
    public function list() {
        $A = A('Wms/Order','Logic');
        $res = $A->get_details_by_wave_and_sku($map);
        $this->ajaxReturn($res);
    } 
}
