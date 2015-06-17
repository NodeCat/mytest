<?php
namespace Wms\Logic;
/**
 * 配送路线逻辑封装
 * @author zhangchaoge
 *
 */
class DistributionLogic {
    
    public static $line = array(); //线路 （缓存线路）
    /**
     * 搜索订单
     */
    public function search($search = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($search)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        $M = M('stock_bill_out');
        
        $map['company_id'] = $search['company_id'];
        $map['wh_id'] = $search['wh_id'];
        $map['line'] = $search['line'];
        $map['status'] = 4; //状态 分拣完成
        
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
        $data = array(); //订单筛选条件
        foreach ($result as $value) {
            $data['order_ids'][] = $value['refer_code'];
        }
        $data['deliver_date'] = date($search['date']);
        if (isset($search['time'])) {
            $data['deliver_time'] = $search['time'];
        }
        $data['order_type'] = $search['order_type'];
        //获取订单详情
        $order = D('Order', 'Logic');
        $order_info = $order->getOrderInfoByOrderIds($data);
        if ($order_info['status'] == false) {
            $return['msg'] = $order_info['msg'];
            return $return;
        }
        $list = array();
        $list = $this->format_data($order_info['orderlist']);
        
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $list;
        return $return;
    }
    
    public function order_lists($post) {
        $return = array('status' => false, 'msg' => '');

        if (empty($post['company_id'])) {
            $return['msg'] = '请选择系统';
            return $return;
            
            $this->msgReturn(false, '请选择系统');
        }
        if (empty($post['wh_id'])) {
            $return['msg'] = '请选择仓库';
            return $return;
            $this->msgReturn(false, '请选择仓库');
        }
        if (empty($post['type'])) {
            $return['msg'] = '请选择订单类型';
            return $return;
            $this->msgReturn(false, '请选择订单类型');
        }
        if (empty($post['line'])) {
            $return['msg'] = '请选择线路';
            return $return;
            $this->msgReturn(false, '请选择线路');
        }
        if (empty($post['time'])) {
            $return['msg'] = '请选择时段';
            return $return;
            $this->msgReturn(false, '请选择时段');
        }
        if (empty($post['date'])) {
            $return['msg'] = '请选择日期';
            return $return;
            $this->msgReturn(false, '请选择日期');
        }
        //时段是否区分
        if ($post['time'] == 3) {
            unset($post['time']);
        }
        //获取搜索结果
        $seach_info = $this->search($post);
        if ($seach_info['status'] == false) {
            //搜索失败
            $return['msg'] = $seach_info['msg'];
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $seach_info['list']; 
        return $return;
        //$this->assign('order_list', $seach_info);
        //$this->display('order-list');
    
    }
    
    /*public function search_test() {
         $str ='{"status":0,"orderlist":[{"id":"42043","order_number":"201506101109532297","username":"\u95eb\u8001\u677f","user_id":"3295","remarks":"","status":"2","created_time":"2015\/06\/10 03:09","updated_time":"1433905793","total_price":984,"deal_price":0,"city_id":"804","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/10","line_id":"28","location_id":"804","minus_amount":0,"promo_event_rule_id":"0","sale_id":"32","sale_role":"14","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":984,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u5317\u4eac","site_name":"\u5927\u53a8","deliver_addr":"\u5317\u4eac\u5e02\u4e1c\u57ce\u533a\u9f99\u6f6d\u8857\u9053\u5915\u7167\u5bfa\u4e2d\u88575\u53f7","mobile":"18201629215","shop_name":"\u5317\u4eac\u79e6\u5173\u9762\u9053\u9762\u9986","realname":"\u95eb\u8001\u677f","geo":"{\"lng\":\"116.442586\",\"lat\":\"39.889372\"}","address":"\u5317\u4eac\u5e02\u4e1c\u57ce\u533a\u9f99\u6f6d\u8857\u9053\u5915\u7167\u5bfa\u4e2d\u88575\u53f7","line":"\u5927\u53a8\u5e7f\u6e20\u95e8\u76f4\u9001","warehouse_id":"2","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u59dc\u6d77\u9f99","mobile":"18604144319","id":"13","role":"BD"},"am":{"name":"\u5efa\u4f1f\u534e","mobile":"15652558879","id":"32","role":"AM"},"sale":{"name":"\u5efa\u4f1f\u534e","mobile":"15652558879","id":"32","role":"AM"},"status_cn":"\u5f85\u5ba1\u6838","detail":[{"id":"83892","order_id":"42043","product_id":"10784","name":"\u4e09\u878d\u7435\u7436\u817f\uff08130-150g\/\u4e2a\uff09","quantity":"1","price":143,"sum_price":143,"spec":[{"name":"\u89c4\u683c","id":"50","val":"10kg\/\u4ef6"},{"name":"\u4ea7\u5730","id":"51","val":"\u6cb3\u5317"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u4ef6","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1002664","category_id":"330","single_price":143,"close_unit":"\u4ef6","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"},{"id":"83893","order_id":"42043","product_id":"8500","name":"\u5fc3\u8bfa\u62bd\u53d6\u5f0f\u9910\u5dfe\u7eb8\uff08140\u62bd\uff09","quantity":"1","price":87,"sum_price":87,"spec":[{"name":"\u54c1\u724c","id":"32","val":"\u5fc3\u8bfa"},{"name":"\u89c4\u683c","id":"33","val":"140\u62bd*100\u5305"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000756","category_id":"171","single_price":87,"close_unit":"\u7bb1","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"},{"id":"83894","order_id":"42043","product_id":"6648","name":"\u4f18\u8d28\u9910\u76d2\uff08\u5706\u5f62\uff0c\u5e26\u76d6\uff0c1000ml*300\u5957\uff09","quantity":"1","price":124,"sum_price":124,"spec":[{"name":"\u89c4\u683c","id":"33","val":"300\u4e2a\/\u7bb1"},{"name":"\u63cf\u8ff0","id":"34","val":"\u7528\u4e8e\u76d6\u996d\u3001\u4e2d\u5c0f\u7897\u9762\u3001\u7c73\u7ebf\u3001\u7c73\u7c89\u3001\u9ebb\u8fa3\u70eb\u7b49\uff1b"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000807","category_id":"184","single_price":124,"close_unit":"\u7bb1","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"},{"id":"83895","order_id":"42043","product_id":"10155","name":"\u4e94\u5f97\u5229\u7279\u7cbe\u9ad8\u7b4b\u5c0f\u9ea6\u7c89\uff08\u6cb3\u5317\u90af\u90f8\u5927\u540d\uff09","quantity":"3","price":87,"sum_price":261,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u4e94\u5f97\u5229"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000397","category_id":"17","single_price":87,"close_unit":"\u888b","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"},{"id":"83896","order_id":"42043","product_id":"10220","name":"\u6c47\u798f\u4e00\u7ea7\u5927\u8c46\u6cb9\uff0820L*1\uff09","quantity":"2","price":125,"sum_price":250,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u6c47\u798f"},{"name":"\u89c4\u683c","id":"6","val":"20L*1"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000044","category_id":"11","single_price":125,"close_unit":"\u7bb1","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"},{"id":"83897","order_id":"42043","product_id":"5289","name":"\u4e2d\u7cae\u91d1\u7a3b\u7530\u4f18\u8d28\u4e1c\u5317\u5927\u7c73","quantity":"1","price":119,"sum_price":119,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u91d1\u7a3b\u7530"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"2","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000002","category_id":"24","single_price":119,"close_unit":"\u888b","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42377"}],"log_list":[{"id":"267415","obj_id":"42043","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"125.39.114.108","remark":"","operator_type":"20","operator_id":"3295","operator":"\u95eb\u8001\u677f","created_time":"2015-06-10 03:09:53","updated_time":"1433905793","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"}],"class":"label-primary"},{"id":"42042","order_number":"201506101109047766","username":"\u674e\u8001\u677f","user_id":"8967","remarks":"","status":"3","created_time":"2015\/06\/10 03:09","updated_time":"1433905799","total_price":500,"deal_price":0,"city_id":"993","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"14:00\u81f316:30","deliver_date":"2015\/06\/10","line_id":"35","location_id":"993","minus_amount":0,"promo_event_rule_id":"0","sale_id":"124","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":500,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u4e0a\u6d77","site_name":"\u5927\u53a8","deliver_addr":"\u4e0a\u6d77\u5e02\u666e\u9640\u533a\u957f\u98ce\u65b0\u6751\u8857\u9053\u4e2d\u6c5f\u8def657\u53f7","mobile":"18964318463","shop_name":"\u54b8\u8089\u83dc\u996d\u9aa8\u5934\u6c64 \u8001\u9e2d\u7c89\u4e1d\u9986","realname":"\u674e\u8001\u677f","geo":"{\"lng\":\"121.392126\",\"lat\":\"31.230166\"}","address":"\u4e0a\u6d77\u5e02\u666e\u9640\u533a\u957f\u98ce\u65b0\u6751\u8857\u9053\u4e2d\u6c5f\u8def657\u53f7","line":"\u5927\u53a8\u957f\u98ce\u5546\u5708","warehouse_id":"66","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u7530\u91ce","mobile":"13127775373","id":"124","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u7530\u91ce","mobile":"13127775373","id":"124","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83891","order_id":"42042","product_id":"5315","name":"\u5e1d\u738b\u5fa1\u8d21\u957f\u7c92\u9999\u7c73","quantity":"4","price":125,"sum_price":500,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u5e1d\u738b\u5fa1\u8d21"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 03:09","updated_time":"2015\/06\/10 03:09","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000528","category_id":"23","single_price":125,"close_unit":"\u888b","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42376"}],"log_list":[{"id":"267412","obj_id":"42042","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"114.81.255.37","remark":"","operator_type":"20","operator_id":"8967","operator":"\u674e\u8001\u677f","created_time":"2015-06-10 03:09:04","updated_time":"1433905744","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267416","obj_id":"42042","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"286","operator":"\u6b66\u96c5\u96c5","created_time":"2015-06-10 03:09:59","updated_time":"1433905799","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42041","order_number":"201506101108188504","username":"\u9a6c\u84ec\u52c3","user_id":"7297","remarks":"","status":"3","created_time":"2015\/06\/10 03:08","updated_time":"1433943739","total_price":830,"deal_price":0,"city_id":"804","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"14:00\u81f316:30","deliver_date":"2015\/06\/10","line_id":"176","location_id":"804","minus_amount":0,"promo_event_rule_id":"0","sale_id":"246","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":830,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u5317\u4eac","site_name":"\u5927\u53a8","deliver_addr":"\u5317\u4eac\u5e02\u6d77\u6dc0\u533a\u7518\u5bb6\u53e3\u8857\u9053\u589e\u5149\u8def35-1\u53f7\u7f8e\u5ec9\u7f8e\u4e00\u5c42\u5e02\u573a\u4e3b\u98df\u533a","mobile":"13261462398","shop_name":"\u4e3b\u98df\u53a8\u623f","realname":"\u9a6c\u84ec\u52c3","geo":"{\"lng\":\"116.322804\",\"lat\":\"39.928318\"}","address":"\u5317\u4eac\u5e02\u6d77\u6dc0\u533a\u7518\u5bb6\u53e3\u8857\u9053\u589e\u5149\u8def35-1\u53f7\u7f8e\u5ec9\u7f8e\u4e00\u5c42\u5e02\u573a\u4e3b\u98df\u533a","line":"\u5927\u53a8\u516b\u91cc\u5e84\uff08\u6d77\u6dc0\u533a\uff09","warehouse_id":"2","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u5b81\u7fcc\u5c91","mobile":"15942215004","id":"246","role":"BD"},"am":{"name":"\u767d\u6c38\u660e","mobile":"13301256339","id":"22","role":"AM"},"sale":{"name":"\u5b81\u7fcc\u5c91","mobile":"15942215004","id":"246","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83890","order_id":"42041","product_id":"10480","name":"\u4e94\u5f97\u5229\u5bcc\u5f3a\u9ad8\u7b4b\u5c0f\u9ea6\u7c89\uff08\u6cb3\u5317\u90af\u90f8\u5927\u540d\uff09","quantity":"10","price":83,"sum_price":830,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u4e94\u5f97\u5229"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 03:08","updated_time":"2015\/06\/10 13:42","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000395","category_id":"17","single_price":83,"close_unit":"\u888b","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42375"}],"log_list":[{"id":"267411","obj_id":"42041","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"61.49.57.61","remark":"","operator_type":"20","operator_id":"7297","operator":"\u9a6c\u84ec\u52c3","created_time":"2015-06-10 03:08:18","updated_time":"1433905698","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267414","obj_id":"42041","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"286","operator":"\u6b66\u96c5\u96c5","created_time":"2015-06-10 03:09:47","updated_time":"1433905787","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"},{"id":"267926","obj_id":"42041","log_info":"\u6ce2\u6b21\u4e2d","operate_type":"11","log_ip":"10.0.2.2","remark":"","operator_type":"100","operator_id":"67","operator":"\u738b\u6811\u6625","created_time":"2015-06-10 10:09:10","updated_time":"1433930950","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"},{"id":"267935","obj_id":"42041","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"100","operator_id":"1","operator":"\u7ba1\u7406\u5458","created_time":"2015-06-10 13:42:19","updated_time":"1433943739","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"}],"class":"label-info"},{"id":"42040","order_number":"201506101104468200","username":"\u9648\u8001\u677f","user_id":"11271","remarks":"","status":"3","created_time":"2015\/06\/10 03:04","updated_time":"1433905777","total_price":125,"deal_price":0,"city_id":"993","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"14:00\u81f316:30","deliver_date":"2015\/06\/10","line_id":"148","location_id":"993","minus_amount":0,"promo_event_rule_id":"0","sale_id":"425","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":125,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u4e0a\u6d77","site_name":"\u5927\u53a8","deliver_addr":"\u4e0a\u6d77\u5e02\u5f90\u6c47\u533a\u7530\u6797\u8857\u9053\u94a6\u5dde\u8def789\u53f7","mobile":"13524824312","shop_name":"\u6cb9\u997c\u94fa","realname":"\u9648\u8001\u677f","geo":"{\"lng\":\"121.427222\",\"lat\":\"31.177359\"}","address":"\u4e0a\u6d77\u5e02\u5f90\u6c47\u533a\u7530\u6797\u8857\u9053\u94a6\u5dde\u8def789\u53f7","line":"\u5927\u53a8\u7530\u6797\u5357\u5546\u5708","warehouse_id":"66","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u4e01\u5fd7\u4f1f","mobile":"15252009520","id":"425","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u4e01\u5fd7\u4f1f","mobile":"15252009520","id":"425","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83889","order_id":"42040","product_id":"6016","name":"\u7389\u5170\u4e00\u7ea7\u5927\u8c46\u6cb9\uff085L*4\uff09","quantity":"1","price":125,"sum_price":125,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u7389\u5170"},{"name":"\u89c4\u683c","id":"6","val":"5L*4"}],"status":"3","created_time":"2015\/06\/10 03:04","updated_time":"2015\/06\/10 03:09","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000695","category_id":"11","single_price":125,"close_unit":"\u7bb1","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42374"}],"log_list":[{"id":"267395","obj_id":"42040","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"183.195.233.181","remark":"","operator_type":"20","operator_id":"11271","operator":"\u9648\u8001\u677f","created_time":"2015-06-10 03:04:46","updated_time":"1433905486","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267413","obj_id":"42040","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"286","operator":"\u6b66\u96c5\u96c5","created_time":"2015-06-10 03:09:37","updated_time":"1433905777","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42039","order_number":"201506101102198060","username":"\u9648\u8f89","user_id":"3112","remarks":"\u9762\u7c89\u8981\u597d\u70b9\u7684","status":"3","created_time":"2015\/06\/10 03:02","updated_time":"1433905407","total_price":399,"deal_price":0,"city_id":"993","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"14:00\u81f316:30","deliver_date":"2015\/06\/10","line_id":"134","location_id":"993","minus_amount":0,"promo_event_rule_id":"0","sale_id":"125","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":399,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u4e0a\u6d77","site_name":"\u5927\u53a8","deliver_addr":"\u4e0a\u6d77\u5e02\u957f\u5b81\u533a\u5251\u6cb3\u8def90\u53f7","mobile":"18301930216","shop_name":"\u5df4\u6bd4\u9992\u5934","realname":"\u9648\u8f89","geo":"{\"lng\":\"121.371786\",\"lat\":\"31.219397\"}","address":"\u4e0a\u6d77\u5e02\u957f\u5b81\u533a\u5251\u6cb3\u8def90\u53f7","line":"\u5927\u53a8\u5929\u5c71\u897f\u8def\u5251\u6cb3\u8def\u5546\u5708","warehouse_id":"66","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u5f20\u6d9b","mobile":"18201991553","id":"125","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u5f20\u6d9b","mobile":"18201991553","id":"125","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83887","order_id":"42039","product_id":"10771","name":"\u9e21\u86cb\uff08\u7ea2\u76ae\/\u539a\uff09","quantity":"1","price":108,"sum_price":108,"spec":[{"name":"\u63cf\u8ff0","id":"4","val":"\u542b\u7b50\u62bc\u91d110\u5143\uff0c\u7b50\u53ef\u9000"},{"name":"\u54c1\u724c","id":"7","val":"\u6563\u88c5"},{"name":"\u5305\u88c5\u89c4\u683c","id":"10","val":"\u51c0\u91cd27\u65a4\uff0c\u6bcf\u65a4\u7ea67-8\u4e2a"}],"status":"3","created_time":"2015\/06\/10 03:02","updated_time":"2015\/06\/10 03:03","unit_id":"\u7b50","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000540","category_id":"36","single_price":108,"close_unit":"\u7b50","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42373"},{"id":"83888","order_id":"42039","product_id":"12705","name":"\u82cf\u4e09\u96f6\u8d85\u7ea7\u5c0f\u9ea6\u7c89\uff08\u96ea\u971e\uff09","quantity":"3","price":97,"sum_price":291,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u82cf\u4e09\u96f6"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 03:02","updated_time":"2015\/06\/10 03:03","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000754","category_id":"178","single_price":97,"close_unit":"\u888b","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42373"}],"log_list":[{"id":"267369","obj_id":"42039","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"123.155.155.180","remark":"","operator_type":"20","operator_id":"3112","operator":"\u9648\u8f89","created_time":"2015-06-10 03:02:19","updated_time":"1433905339","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267385","obj_id":"42039","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"133","operator":"\u674e\u8273\u6885","created_time":"2015-06-10 03:03:27","updated_time":"1433905407","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42038","order_number":"201506101102099813","username":"\u53f2\u5efa\u51ef","user_id":"9078","remarks":"","status":"14","created_time":"2015\/06\/10 03:02","updated_time":"1434354388","total_price":85,"deal_price":0,"city_id":"804","market_id":"0","site_src":"2","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/10","line_id":"123","location_id":"804","minus_amount":0,"promo_event_rule_id":"0","sale_id":"10","sale_role":"14","dist_id":"4207","dist_order":"1","wave_id":"293","pick_task_id":"4038","order_type":"1","deliver_fee":0,"final_price":85,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"F267574632989","city_name":"\u5317\u4eac","site_name":"\u5927\u679c","deliver_addr":"\u5317\u4eac\u5e02\u897f\u57ce\u533a\u5fb7\u80dc\u8857\u9053\u53cc\u65d7\u6746\u4e1c\u91cc(\u4ec0\u574a\u8857)\u53cc\u65d7\u6746\u5c0f\u533a13\u53f7\u697c\u897f\u4fa7\u6c34\u679c\u644a","mobile":"13521128798","shop_name":"\u53cc\u65d7\u65d7\u6746\u793e\u533a\u4fbf\u6c11\u670d\u52a1\u83dc\u7ad9","realname":"\u53f2\u5efa\u51ef","geo":"{\"lng\":\"116.387217\",\"lat\":\"39.965711\"}","address":"\u5317\u4eac\u5e02\u897f\u57ce\u533a\u5fb7\u80dc\u8857\u9053\u53cc\u65d7\u6746\u4e1c\u91cc(\u4ec0\u574a\u8857)\u53cc\u65d7\u6746\u5c0f\u533a13\u53f7\u697c\u897f\u4fa7\u6c34\u679c\u644a","line":"\u5927\u679c\u5730\u575b","warehouse_id":"2","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u4e8e\u529f\u5bcc","mobile":"18801212612","id":"44","role":"BD"},"am":{"name":"\u8d75\u5efa\u7acb","mobile":"13301344415","id":"10","role":"AM"},"sale":{"name":"\u8d75\u5efa\u7acb","mobile":"13301344415","id":"10","role":"AM"},"status_cn":"\u5df2\u5206\u62e8","detail":[{"id":"83886","order_id":"42038","product_id":"7526","name":"\u7687\u51a0\u68a8","quantity":"1","price":85,"sum_price":85,"spec":[{"name":"\u7b49\u7ea7","id":"5","val":"45\u4e2a\/\u7bb1"},{"name":"\u5305\u88c5\u89c4\u683c","id":"8","val":"\u4e0d\u4f4e\u4e8e26\u65a4\/\u7bb1"},{"name":"\u4ea7\u5730","id":"9","val":"\u6cb3\u5317"}],"status":"14","created_time":"2015\/06\/10 03:02","updated_time":"2015\/06\/15 07:46","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000440","category_id":"60","single_price":85,"close_unit":"\u7bb1","city_id":"804","wave_id":"0","pick_task_id":"0","suborder_id":"42372"}],"log_list":[{"id":"267367","obj_id":"42038","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"111.161.77.228","remark":"","operator_type":"20","operator_id":"9078","operator":"\u53f2\u5efa\u51ef","created_time":"2015-06-10 03:02:09","updated_time":"1433905329","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267389","obj_id":"42038","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"133","operator":"\u674e\u8273\u6885","created_time":"2015-06-10 03:03:37","updated_time":"1433905417","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"},{"id":"267390","obj_id":"42038","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"107","operator":"\u9648\u6021\u7487","created_time":"2015-06-10 03:03:39","updated_time":"1433905419","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"},{"id":"267940","obj_id":"42038","log_info":"\u6ce2\u6b21\u4e2d","operate_type":"11","log_ip":"106.39.94.162","remark":"","operator_type":"100","operator_id":"1","operator":"\u7ba1\u7406\u5458","created_time":"2015-06-10 13:42:26","updated_time":"1433943746","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"},{"id":"267946","obj_id":"42038","log_info":"\u5f85\u5206\u62e3","operate_type":"12","log_ip":"106.39.94.162","remark":"","operator_type":"100","operator_id":"1","operator":"\u7ba1\u7406\u5458","created_time":"2015-06-10 13:42:43","updated_time":"1433943763","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"},{"id":"267996","obj_id":"42038","log_info":"\u5df2\u590d\u6838","operate_type":"13","log_ip":"10.0.2.2","remark":"","operator_type":"100","operator_id":"67","operator":"\u738b\u6811\u6625","created_time":"2015-06-13 10:04:04","updated_time":"1434189844","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"},{"id":"267997","obj_id":"42038","log_info":"\u5df2\u5206\u62e8","operate_type":"14","log_ip":"10.0.2.2","remark":"","operator_type":"100","operator_id":"67","operator":"\u738b\u6811\u6625","created_time":"2015-06-15 07:46:28","updated_time":"1434354388","status":"1","edit_type":"1","operator_type_cn":"\u8d85\u7ea7\u7ba1\u7406\u5458"}],"class":"label-info"},{"id":"42037","order_number":"201506101101484356","username":"\u9a6c\u82cf\u529b\u6bdb","user_id":"10329","remarks":"","status":"3","created_time":"2015\/06\/10 03:01","updated_time":"1433905387","total_price":108,"deal_price":0,"city_id":"993","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/11","line_id":"148","location_id":"993","minus_amount":0,"promo_event_rule_id":"0","sale_id":"353","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":108,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u4e0a\u6d77","site_name":"\u5927\u53a8","deliver_addr":"\u4e0a\u6d77\u5e02\u5f90\u6c47\u533a\u6f15\u6cb3\u6cfe\u8857\u9053\u94a6\u5dde\u8def290\u53f7","mobile":"13641815693","shop_name":"\u5170\u5dde\u62c9\u9762","realname":"\u9a6c\u82cf\u529b\u6bdb","geo":"{\"lng\":\"121.430915\",\"lat\":\"31.165384\"}","address":"\u4e0a\u6d77\u5e02\u5f90\u6c47\u533a\u6f15\u6cb3\u6cfe\u8857\u9053\u94a6\u5dde\u8def290\u53f7","line":"\u5927\u53a8\u7530\u6797\u5357\u5546\u5708","warehouse_id":"66","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u6768\u8273\u51b0","mobile":"13817317781","id":"353","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u6768\u8273\u51b0","mobile":"13817317781","id":"353","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83885","order_id":"42037","product_id":"10771","name":"\u9e21\u86cb\uff08\u7ea2\u76ae\/\u539a\uff09","quantity":"1","price":108,"sum_price":108,"spec":[{"name":"\u63cf\u8ff0","id":"4","val":"\u542b\u7b50\u62bc\u91d110\u5143\uff0c\u7b50\u53ef\u9000"},{"name":"\u54c1\u724c","id":"7","val":"\u6563\u88c5"},{"name":"\u5305\u88c5\u89c4\u683c","id":"10","val":"\u51c0\u91cd27\u65a4\uff0c\u6bcf\u65a4\u7ea67-8\u4e2a"}],"status":"3","created_time":"2015\/06\/10 03:01","updated_time":"2015\/06\/10 03:03","unit_id":"\u7b50","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000540","category_id":"36","single_price":108,"close_unit":"\u7b50","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42371"}],"log_list":[{"id":"267361","obj_id":"42037","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"123.155.153.50","remark":"","operator_type":"20","operator_id":"10329","operator":"\u9a6c\u82cf\u529b\u6bdb","created_time":"2015-06-10 03:01:48","updated_time":"1433905308","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267382","obj_id":"42037","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"107","operator":"\u9648\u6021\u7487","created_time":"2015-06-10 03:03:07","updated_time":"1433905387","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42036","order_number":"201506101101407545","username":"\u674e","user_id":"10074","remarks":"9\u70b9\u9001","status":"3","created_time":"2015\/06\/10 03:01","updated_time":"1433905377","total_price":124,"deal_price":0,"city_id":"1206","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/10","line_id":"42","location_id":"1206","minus_amount":0,"promo_event_rule_id":"0","sale_id":"145","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":124,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u5929\u6d25","site_name":"\u5927\u53a8","deliver_addr":"\u5929\u6d25\u5e02\u5357\u5f00\u533a\u4e07\u5174\u8857\u9053\u897f\u6e56\u9053\u5357\u98ce\u8def\u4ea4\u53e3\u897f50\u7c73\u5c0f\u8001\u996d\u5e84\u95e8\u53e3","mobile":"18722311998","shop_name":"\u65e9\u70b9","realname":"\u674e","geo":"{\"lng\":\"117.163034\",\"lat\":\"39.118547\"}","address":"\u5929\u6d25\u5e02\u5357\u5f00\u533a\u4e07\u5174\u8857\u9053\u897f\u6e56\u9053\u5357\u98ce\u8def\u4ea4\u53e3\u897f50\u7c73\u5c0f\u8001\u996d\u5e84\u95e8\u53e3","line":"\u5927\u679c\u4e07\u5174\u8857\u5546\u5708","warehouse_id":"72","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u6881\u6770","mobile":"15022018023","id":"145","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u6881\u6770","mobile":"15022018023","id":"145","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83884","order_id":"42036","product_id":"7180","name":"\u5143\u5b9d\u4e00\u7ea7\u5927\u8c46\u6cb9\uff0810L*2\uff09","quantity":"1","price":124,"sum_price":124,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u5143\u5b9d"},{"name":"\u89c4\u683c","id":"6","val":"10L*2"}],"status":"3","created_time":"2015\/06\/10 03:01","updated_time":"2015\/06\/10 03:02","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000138","category_id":"11","single_price":124,"close_unit":"\u7bb1","city_id":"1206","wave_id":"0","pick_task_id":"0","suborder_id":"42370"}],"log_list":[{"id":"267358","obj_id":"42036","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"61.49.56.47","remark":"","operator_type":"20","operator_id":"10074","operator":"\u674e","created_time":"2015-06-10 03:01:40","updated_time":"1433905300","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267379","obj_id":"42036","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"107","operator":"\u9648\u6021\u7487","created_time":"2015-06-10 03:02:57","updated_time":"1433905377","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42035","order_number":"201506101058292006","username":"\u5218\u5973\u58eb","user_id":"2619","remarks":"","status":"3","created_time":"2015\/06\/10 02:58","updated_time":"1433905142","total_price":991,"deal_price":0,"city_id":"1206","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/10","line_id":"40","location_id":"1206","minus_amount":0,"promo_event_rule_id":"0","sale_id":"140","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":991,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u5929\u6d25","site_name":"\u5927\u53a8","deliver_addr":"\u5929\u6d25\u5e02\u5357\u5f00\u533a\u5cb3\u6e56\u9053\u7389\u6cc9\u8def\u4ea4\u53e3\u897f\u884c50\u7c73\u8def\u5357","mobile":"13516145255","shop_name":"\u4e8c\u59d1\u5305\u5b50","realname":"\u5218\u5973\u58eb","geo":"{\"lng\":\"117.167996\",\"lat\":\"39.11997\"}","address":"\u5929\u6d25\u5e02\u5357\u5f00\u533a\u5cb3\u6e56\u9053\u7389\u6cc9\u8def\u4ea4\u53e3\u897f\u884c50\u7c73\u8def\u5357","line":"\u5927\u53a8\u4e07\u5fb7\u5e84\u5927\u8857\u7ebf","warehouse_id":"72","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u90a2\u535a","mobile":"18931161080","id":"140","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u90a2\u535a","mobile":"18931161080","id":"140","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83882","order_id":"42035","product_id":"7209","name":"\u4e2d\u7cae\u4e94\u6e56\u4e00\u7ea7\u5927\u8c46\u6cb9\uff085L*4\uff09","quantity":"1","price":126,"sum_price":126,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u4e94\u6e56"},{"name":"\u89c4\u683c","id":"6","val":"5L*4"}],"status":"3","created_time":"2015\/06\/10 02:58","updated_time":"2015\/06\/10 02:59","unit_id":"\u7bb1","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000542","category_id":"11","single_price":126,"close_unit":"\u7bb1","city_id":"1206","wave_id":"0","pick_task_id":"0","suborder_id":"42369"},{"id":"83883","order_id":"42035","product_id":"10397","name":"\u4e94\u5f97\u5229\u8d85\u7cbe\u9ad8\u7b4b\u5c0f\u9ea6\u7c89\uff08\u6cb3\u5317\u8861\u6c34\u6df1\u5dde\uff09","quantity":"10","price":86.5,"sum_price":865,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u4e94\u5f97\u5229"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 02:58","updated_time":"2015\/06\/10 02:59","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000479","category_id":"178","single_price":86.5,"close_unit":"\u888b","city_id":"1206","wave_id":"0","pick_task_id":"0","suborder_id":"42369"}],"log_list":[{"id":"267262","obj_id":"42035","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"61.49.56.47","remark":"","operator_type":"20","operator_id":"2619","operator":"\u5218\u5973\u58eb","created_time":"2015-06-10 02:58:29","updated_time":"1433905109","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267267","obj_id":"42035","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"133","operator":"\u674e\u8273\u6885","created_time":"2015-06-10 02:59:02","updated_time":"1433905142","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"},{"id":"42034","order_number":"201506101055081596","username":"\u80e1\u8001\u677f","user_id":"10757","remarks":"","status":"3","created_time":"2015\/06\/10 02:55","updated_time":"1433905032","total_price":251,"deal_price":0,"city_id":"993","market_id":"0","site_src":"1","sign_msg":"","deliver_time":"08:00\u81f310:30","deliver_date":"2015\/06\/10","line_id":"147","location_id":"993","minus_amount":0,"promo_event_rule_id":"0","sale_id":"291","sale_role":"12","dist_id":"0","dist_order":"0","wave_id":"0","pick_task_id":"0","order_type":"1","deliver_fee":0,"final_price":251,"promotion_id":"0","pay_type":"0","pay_status":"0","pay_reduce":0,"customer_coupon_id":"0","customer_type":"1","order_type_cn":"\u666e\u901a\u8ba2\u5355","pick_number":"","city_name":"\u4e0a\u6d77","site_name":"\u5927\u53a8","deliver_addr":"\u4e0a\u6d77\u5e02\u95f5\u884c\u533a\u53e4\u7f8e\u8857\u9053\u4e1c\u5170\u8def\u4e1c\u5170\u83dc\u5e02\u573a320","mobile":"13472737947","shop_name":"\u6b63\u5b97\u5c71\u4e1c\u714e\u997c","realname":"\u80e1\u8001\u677f","geo":"{\"lng\":\"121.397565\",\"lat\":\"31.156734\"}","address":"\u4e0a\u6d77\u5e02\u95f5\u884c\u533a\u53e4\u7f8e\u8857\u9053\u4e1c\u5170\u8def\u4e1c\u5170\u83dc\u5e02\u573a320","line":"\u5927\u53a8\u9759\u5b89\u65b0\u57ce\u5357\u5546\u5708","warehouse_id":"66","customer_type_name":"\u666e\u901a\u5ba2\u6237","bd":{"name":"\u5f90\u7acb","mobile":"18701867707","id":"291","role":"BD"},"am":{"role":"AM"},"sale":{"name":"\u5f90\u7acb","mobile":"18701867707","id":"291","role":"BD"},"status_cn":"\u5f85\u751f\u4ea7","detail":[{"id":"83880","order_id":"42034","product_id":"10328","name":"\u82cf\u4e09\u96f6\u6cb9\u6761\u738b\uff08\u96ea\u6f84\uff09","quantity":"2","price":80,"sum_price":160,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u96ea\u6f84\/\u82cf\u4e09\u96f6"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 02:55","updated_time":"2015\/06\/10 02:57","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1001727","category_id":"416","single_price":80,"close_unit":"\u888b","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42368"},{"id":"83881","order_id":"42034","product_id":"12704","name":"\u82cf\u4e09\u96f6\u7279\u4e00\u5c0f\u9ea6\u7c89\uff08\u96ea\u971e\uff09","quantity":"1","price":91,"sum_price":91,"spec":[{"name":"\u54c1\u724c","id":"2","val":"\u82cf\u4e09\u96f6"},{"name":"\u89c4\u683c","id":"6","val":"25kg*1"}],"status":"3","created_time":"2015\/06\/10 02:55","updated_time":"2015\/06\/10 02:57","unit_id":"\u888b","actual_quantity":"0","actual_price":0,"actual_sum_price":0,"sku_number":"1000752","category_id":"178","single_price":91,"close_unit":"\u888b","city_id":"993","wave_id":"0","pick_task_id":"0","suborder_id":"42368"}],"log_list":[{"id":"267256","obj_id":"42034","log_info":"\u5f85\u5ba1\u6838","operate_type":"2","log_ip":"140.207.23.173","remark":"","operator_type":"20","operator_id":"10757","operator":"\u80e1\u8001\u677f","created_time":"2015-06-10 02:55:08","updated_time":"1433904908","status":"1","edit_type":"1","operator_type_cn":"\u91c7\u8d2d\u5546"},{"id":"267257","obj_id":"42034","log_info":"\u5f85\u751f\u4ea7","operate_type":"3","log_ip":"106.39.94.162","remark":"","operator_type":"10","operator_id":"133","operator":"\u674e\u8273\u6885","created_time":"2015-06-10 02:57:12","updated_time":"1433905032","status":"1","edit_type":"1","operator_type_cn":"\u8fd0\u8425"}],"class":"label-info"}],"total":{"-1":42043,"0":2883,"1":25547,"2":1,"3":509,"11":0,"12":217,"4":0,"13":28,"14":132,"5":11823,"8":0,"6":60,"7":843,"100":0},"total_count":42043}';
        $orders = json_decode($str,true);
        $this->format_data($orders['orderlist']);
        $list =   $orders['orderlist'];
        $res['msg'] = '成功';
        $res['status'] = true;
        $res['list'] = $list;
        return $res;
    }*/
    
    /**
     * 订单数据处理
     * @param unknown $data
     * @return multitype:
     */
    public function format_data(&$data) {
        
        foreach ($data as $key => &$value) {
            $value['line_total'] = count($value['detail']);
            foreach ($value['detail'] as $k => $v) {
                $value['sku_total'] += $v['quantity'];
                //$list[$key]['colspan'] = count($list[$key]['sku']);
            }
        }
    }
    
    /**
     * 根据线路id获取线路名称
     * @param number $line_id
     * @return string|Ambigous <string, unknown>
     */
    public function format_line($line_id = 0) {
        $return = '';
        
        //获取线路 只获取一次
        if (empty($this->line)) {
            $lines = D('Wave', 'Logic');
            $result = $lines->line();
            $this->line = $result;
        }
        if (empty($line_id)) {
            //返回所有
            $return = array();
            $return = $this->line;
            return $return;
        }
        
        foreach ($this->line as $key => $value) {
            if ($key == $line_id) {
                $return = $value;
                break;
            }
        }
        
        return $return;
    }
    
    /**
     * 根据配送单id获取订单
     * @param int $dis_id 订单ID
     */
    public function get_order_ids_by_dis_id($dis_id = 0) {
        $return = array();
        
        if (empty($dis_id)) {
            return $return;
        }
        $M = M('stock_wave_distribution_detail');
        $map['pid'] = $dis_id;
        $result = $M->field('bill_out_id')->where($map)->select();
        if (empty($result)) {
            return $return;
        }
        foreach ($result as $value) {
            $return[] = $value['bill_out_id'];
        }
        
        return $return;
    }
    
    /**
     * 创建新配送单
     * @param array $ids 订单id组
     */
    public function add_distributioin($ids = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($ids) || count($ids, 1) == count($ids)) {
            $return['msg'] = '没有选择订单';
        }
        
        $D = D('Order', 'Logic');
        $dis = D('Distribution'); 
        $det = M('stock_wave_distribution_detail');
        foreach ($ids as $value) {
            $result = $D->getOrderInfoByOrderIdArr($value);
            if ($result['status'] == false) {
                $return['msg'] = $result['msg'];
                return $return;
            }
            $result = $result['list'];
            $data = array();
            $data['dist_code'] = get_sn('LT'); //配送单号
            $data['total_price'] = 0; //应收金额
            $data['company_id'] = 1;
            $data['order_count'] = count($value); //订单数
            $data['status'] = 1; //状态 未发运
            $data['is_printed'] = 0; //未打印
            
            $i = 0;
            foreach ($result as $val) {
                $data['total_price'] += $val['total_price']; //总价格
                $data['line_count'] += count($val['detail']); //总种类
                foreach ($val['detail'] as $v) {
                    $data['sku_count'] += $v['quantity']; //sku总数量
                }
                if ($i < 1) {
                    $data['line_id'] = $val['line_id']; //路线
                    $data['deliver_date'] = $val['deliver_date']; //配送日期
                    $data['deliver_time'] = $val['deliver_time']; //配送时段
                    $data['wh_id'] = $val['warehouse_id']; //所属仓库
                }
                
                $i ++;
            }
            if ($dis->create($data)) {
                $pid = $dis->add();
            }
            if (!$pid) {
                $return['msg'] = '写入失败';
                return $return;
            }
            $detail = array();
            $detail['created_user'] = session()['user']['uid'];
            $detail['created_time'] = NOW_TIME;
            $detail['updated_user'] = session()['user']['uid'];
            $detail['updated_user'] = session()['user']['uid'];
            $detail['is_deleted'] = 0;
            foreach ($val as $vv) {
                $detail['bill_out_id'] = $vv;
                $detail['pid'] = $pid;
                if ($det->create($detail)) {
                    $det->add();
                }
            }
        }
        
        $return['status'] = true;
        $reurn['msg'] = '成功';
        return $return;
    }
    
    /**
     * 根据pid获取出库单详情 支持批量获取
     * @param array or int $pid 父id
     */
    public function get_out_detail_by_pids($pid) {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out_detail');
        if (empty($pid)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        if (is_array($pid)) {
            $map['pid'] = array('in', $pid);
        } else {
            $map['pid'] = $pid;
        }
        $result = $M->where($map)->select();
        if (!empty($result)) {
            $return['msg'] = '成功';
            $return['status'] = true;
            $return['list'] = $result;
        }
        
        return $return;
    }
}