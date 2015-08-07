<?php
/**
 *  TMS派车任务控制器
 *  @author    pengyanlei@dachuwang.com
 */
namespace Tms\Controller;
use Think\Controller;

class DispatchTaskController extends Controller
{
    protected function _initialize()
    {   
        $this->warehouses = A('Wms/Distribution', 'Logic')->getAllWarehouse();
        $this->task_types = A('Tms/Dist', 'Logic')->getCatesByType('task_type');
        $this->car_types  = A('Tms/Dist', 'Logic')->getCatesByType('car_type');
    }

    /**
     * [index 任务列表]
     * @return [type] [description]
     */
    public function index()
    {
        //筛选条件
        if ($wh_id = I('post.wh_id/d', 0)) {
            $map['wh_id'] = $wh_id;
            $this->wh_id = $wh_id;
        }
        if ($task_type = I('post.task_type/d', 0)) {
            $map['task_type'] = $task_type;
            $this->task_type = $task_type;
        }
        if ($car_type = I('post.car_type/d', 0)) {
            $map['car_type'] = $car_type;
            $this->car_type = $car_type;
        }
        if ($platform = I('post.platform', '', 'trim')) {
            $map['platform'] = $platform;
            $this->platform = $platform;
        }
        if ($status = I('post.status', '', 'trim')) {
            $map['status'] = $status;
            $this->status = $status;
        }
        if ($apply_user = I('post.apply_user', '', 'trim')) {
            $map['apply_user'] = array('like', $apply_user);
            $this->apply_user = $apply_user;
        }
        if ($created_time = I('post.created_time', '', 'trim')) {
            $start_date = $created_time;
            $end_date = date('Y-m-d',strtotime('+1 Days', strtotime($start_date)));
            $map['created_time'] = array('between', array($start_date, $end_date));
            $this->created_time = $created_time;
        }
        if ($apply_info = I('post.apply_info', '', 'trim')) {
            $where['apply_user']   = $apply_info;
            $where['apply_mobile'] = $apply_info;
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $this->apply_info = $apply_info;

        }
        $M = M('tms_dispatch_task');
        $map['is_deleted'] = 0;
        $order = 'created_time DESC';
        $list = $M->where($map)->order($order)->select();
        //获取任务类型用车平台和司机信息
        foreach ($list as &$val) {
            $val['warehouse_name'] = A('Wms/Distribution', 'Logic')->getWarehouseById($val['wh_id']);
            $val['task_type_name'] = A('Tms/Dist', 'Logic')->getCateNameById($val['task_type']);
            $val['platform_name'] = A('Tms/Dist', 'Logic')->getCateNameById($val['platform']);
            $val['expect_car_type_name']  = A('Tms/Dist', 'Logic')->getCateNameById($val['expect_car_type']);
            $val['driver']  = A('Tms/Dist', 'Logic')->getDriverInfoById($val['driver_id']);
            $val['status_cn']  = A('Tms/Dist', 'Logic')->getStatusCnByCode($val['status']);
        }
        //所有的任务状态和用车平台
        $this->task_status = A('Tms/Dist', 'Logic')->getAllTaskStatus();
        $this->platforms = A('Tms/Dist', 'Logic')->getCatesByType('platform');
        $this->list = $list;
        $this->display('DispatchTask:dispatch-task');
    }

    /**
     * [addTask 新建任务]
     */
    public function addTask()
    {
        if (IS_POST) {
            //必选信息
            $rdata = array(
                'task_name'        => I('post.task_name', array()),
                'wh_id'            => I('post.wh_id', array()),
                'task_type'        => I('post.task_type/', array()),
                'apply_user'       => I('post.apply_user', array()),
                'apply_mobile'     => I('post.apply_mobile', array()),
                'apply_department' => I('post.apply_department', array()),
                'op_time'          => I('post.op_time', array()),
                'nodes'             => I('post.node', array()),
            );
            //判断必选信息是否完整
            foreach ($rdata as $r) {
                if (empty($r)) {
                    $res = array(
                        'status' => -1,
                        'msg'    => '请将必选项填写完整'
                    );
                    $this->ajaxReturn($res);
                }
            }
            //非必选信息
            $ndata = array(
                'expect_car_type' => I('post.expect_car_type', array()),
                'expect_fee'      => I('post.expect_fee', array()),
                'reason'          => I('post.reason', array()),
                'remark'          => I('post.remark', array()),
            );
            foreach ($rdata['task_name'] as $key => $value) {
                $data = array(
                    'task_name'        => $value,
                    'wh_id'            => $rdata['wh_id'][$key],
                    'task_type'        => $rdata['task_type'][$key],
                    'apply_user'       => $rdata['apply_user'][$key],
                    'apply_mobile'     => $rdata['apply_mobile'][$key],
                    'apply_department' => $rdata['apply_department'][$key],
                    'op_time'          => $rdata['op_time'][$key],
                    'expect_car_type'  => $ndata['expect_car_type'][$key],
                    'expect_fee'       => $ndata['expect_fee'][$key],
                    'reason'           => $ndata['reason'][$key],
                    'remark'           => $ndata['remark'][$key],
                );
                //创建人、创建时间
                $data['created_time'] = get_time();
                $data['created_user'] = session('user.uid');
                $M = M('tms_dispatch_task');
                $id = $M->add($data);
                if ($id) {
                    $cdata = array('code' => 'DT' . $id);
                    $M->where(array('id'=>$id))->save($cdata);
                    //任务节点数据
                    $task_node = $rdata['nodes'][$key];
                    $nodeData = array();
                    foreach ($task_node as $k => $v) {
                        $info = explode(',', $v);
                        if ($info[3] && $info[3]) {
                            $geo = array(
                                'lat' => $info[3],
                                'lng' => $info[4],
                            );
                            $geo = json_encode($geo);
                        } else {
                            $geo = '';
                        }
                        $tmp = array(
                            'pid'          => $id,
                            'name'         => $info[0],
                            'customer'     => $info[1],
                            'mobile'       => $info[2],
                            'geo'          => $geo,
                            'queue'        => $k,
                            'created_time' => get_time(),
                            'created_user' => session('user.uid'),
                        );
                        $nodeData[] = $tmp;
                    }
                    //添加节点数据
                    $ns = M('tms_task_node')->addAll($nodeData);
                    if (empty($ns)) {
                        $res = array(
                            'status' => -1,
                            'msg'    => '创建失败'
                        );
                        $this->ajaxReturn($res);
                    }
                } else {
                    $res = array(
                        'status' => -1,
                        'msg'    => '创建失败'
                    );
                    $this->ajaxReturn($res);
                }
            }
            $res = array(
                'status' => 0,
                'msg'    => '创建成功'
            );
            $this->ajaxReturn($res);
        } else {
            $this->display('DispatchTask:add-task');
        }
    }
    /**
     * [saveFee 保存实际运费]
     * @return [type] [description]
     */
    public function saveFee() {
        $fees = I('post.fees');
        if(empty($fees)) {
            $re = array(
                'status' => -1,
                'msg'    => '数据不能为空',
            );
            $this->ajaxReturn($re);
        }
        $M = M('tms_dispatch_task');
        //遍历为每一条任务加运费
        foreach ($fees as $key => $value) {
            $s = $M->where(array('id' => $key))-> save(array('delivery_fee' => $value));
        }
        $re = array(
            'status' => 0,
            'msg'    => '保存成功',
        );
        $this->ajaxReturn($re);
    }

    /**
     * [taskDel 逻辑删除一条任务]
     * @return [type] [description]
     */
    public function taskDel()
    {
        $id = I('get.id/d', 0);
        if (empty($id)) {
            $this->error('参数错误');
        }
        $M = M('tms_dispatch_task');
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $task = $M->field('status')->where($map)->find();
        //限制可以删除的任务为待审批和未通过的
        if ($task['status'] == 1 || $task['status'] == 2 || $task['status'] == 6) {
            $data = array('is_deleted' => 1);
            $re = $M->where($map)->save($data);
            if ($re) {
                $this->success('已删除');
            } else {
                $this->error('删除操作失败，请稍后再试.');
            }
        } else {
            $this->error('该任务已完成审批流程，不能删除.');
        }
        
    }

    /**
     * [taskDetail 任务详情]
     * @return [type] [description]
     */
    public function taskDetail()
    {
        $id = I('get.id/d', 0);
        if (empty($id)) {
            $this->error('参数错误');
        }
        $M = M('tms_dispatch_task');
        $map['id'] = $id;
        $map['is_deleted'] = 0;
        $task = $M->where($map)->find();
        //根据ID查询仓库、车型、平台、用户信息等
        $task['warehouse_name'] = A('Wms/Distribution', 'Logic')->getWarehouseById($task['wh_id']);
        $task['task_type_name'] = A('Tms/Dist', 'Logic')->getCateNameById($task['task_type']);
        $task['platform_name'] = A('Tms/Dist', 'Logic')->getCateNameById($task['platform']);
        $task['expect_car_type_name']  = A('Tms/Dist', 'Logic')->getCateNameById($task['expect_car_type']);
        $task['car_type_name']  = A('Tms/Dist', 'Logic')->getCateNameById($task['car_type']);
        $task['driver']  = A('Tms/Dist', 'Logic')->getDriverInfoById($task['driver_id']);
        $this->task = $task;
        //显示轨迹
        $nodes = M('tms_task_node')->where(array('pid'=>$id))->select();
        $this->customer_count = count($nodes);
        foreach ($nodes as &$value) {
            if ($value['status'] == '2' || $value['status'] == '3' ) {
                $value['color_type'] = 3;
            } else {
                $value['color_type'] = 0;
            }
            $value['geo']     = isset($value['geo']) ? json_decode($value['geo'],true) : '';
            $value['geo_new'] = isset($value['geo']) ? json_decode($value['geo_new'],true) : '';

        }
        $code = $task['code'];
        $location = S(md5($code));
        $this->time=json_decode($task['take_time'],true);
        $this->distance = $task['distance'];
        $this->assign('address',$nodes);
        $this->assign('points',$location['points']);
        $this->display('DispatchTask:task-detail');
    }

    /**
     * [departAudit 部门审批]
     * @return [type] [description]
     */
    public function departAudit()
    {
        $id = I('post.id/d', 0);
        $approve = I('post.approve/d', 0);
        if (empty($id)) {
            $re = array(
                'status' => -1,
                'msg'    => '参数错误'
            );
            $this->ajaxReturn($re);
        }
        $M = M('tms_dispatch_task');
        $map['id'] = $id;
        $task = $M->field('status')->where($map)->find();
        //先判断当前任务状态，为1执行部门审批
        if ($task['status'] == 1) {
            $status = $approve ? 2 : 6;
            $data = array(
                'status'              => $status,
                'department_time'     => get_time(),
                'department_approver' => session('user.uid'),
            );
            $flag = $M->where($map)->save($data);
            if ($flag) {
                $re = array(
                    'status' => 0,
                    'msg'    => '操作成功'
                );
            } else {
                $re = array(
                    'status' => -1,
                    'msg'    => '操作失败'
                );
            }
        } else {
            $re = array(
                'status' => -1,
                'msg'    => '已经过部门审批，请勿重复操作'
            );
        }
        $this->ajaxReturn($re);
        
    }

    /**
     * [logisAudit 物流审批]
     * @return [type] [description]
     */
    public function logisAudit()
    {
        $id = I('post.id/d', 0);
        $approve = I('post.approve/d', 0);
        if (empty($id)) {
            $re = array(
                'status' => -1,
                'msg'    => '参数错误'
            );
            $this->ajaxReturn($re);
        }
        $M = M('tms_dispatch_task');
        $map['id'] = $id;
        $task = $M->field('status')->where($map)->find();
        //先判断当前任务状态，为1执行部门审批
        if ($task['status'] == 2) {
            $status = $approve ? 3 : 6;
            $data = array(
                'status'         => $status,
                'logistics_time' => get_time(),
                'logistics_approver' => session('user.uid'),
            );
            $flag = $M->where($map)->save($data);
            if ($flag) {
                $re = array(
                    'status' => 0,
                    'msg'    => '操作成功'
                );
            } else {
                $re = array(
                    'status' => -1,
                    'msg'    => '操作失败'
                );
            }
        } else {
            $re = array(
                'status' => -1,
                'msg'    => '只有待物流审批状态的任务才能进行物流审批...'
            );
        }
        $this->ajaxReturn($re);
        
    }

    //统一的返回方法
    protected function msgReturn($res, $msg='', $data = '', $url=''){
        $msg = empty($msg)?($res > 0 ?'操作成功':'操作失败'):$msg;
        if(IS_AJAX){
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data'=>$data,'url'=>$url));
        }
        else if($res){ 
                $this->success($msg,$url);
            }
            else{
                $this->error($msg,$url);
            }
        exit();
    }

    /**
     * [getCustomerList 获取客户信息列表]
     * @return [type] [description]
     */
    public function getCustomerList()
    {
        $searchValue = I('get.keyword');
        $map = array(
            'searchKey'    => 'shop_name',
            'searchValue'  => $searchValue,
            'fields'       => 'name,lng,lat,shop_name,mobile',
            'currentPage'  => 0,
            'itemsPerPage' => 15
        );
        $cA = A('Common/Order', 'Logic');
        $res = $cA->getCustomerList($map);
        $this->ajaxReturn($res);
    }

}