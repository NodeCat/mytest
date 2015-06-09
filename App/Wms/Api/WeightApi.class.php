<?php
namespace Wms\Api;
class WeightApi extends CommApi{
    public function weight() {
        $A = A('Wms/Order','Logic');
        $res = $A->weight_sku(I('post.'));
        $this->ajaxReturn($res);
    }   
    public function lists() {
        $A = A('Wms/Order','Logic');
        $res = $A->get_details_by_wave_and_sku(I('post.'));
        $this->ajaxReturn($res);
    } 
}
