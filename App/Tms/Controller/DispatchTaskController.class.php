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
        $this->display('tms:dispatch-task');
    }

    /**
     * [addTask 新建任务]
     */
    public function addTask()
    {
        if (IS_POST) {
            //必选信息
            $rdata = array(
                'task_name'        => I('post.task_name', '', 'trim'),
                'wh_id'            => I('post.wh_id/d', 0),
                'task_type'        => I('post.task_type/d', 0),
                'apply_user'       => I('post.apply_user', '', 'trim'),
                'apply_mobile'     => I('post.apply_mobile', '', 'trim'),
                'apply_department' => I('post.apply_department', '', 'trim'),
                'op_time'          => I('post.op_time', '', 'trim'),
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
                'expect_car_type' => I('post.expect_car_type/d', 0),
                'expect_fee'      => I('post.expect_fee/f', 0),
                'reason'          => I('post.reason', '', 'trim'),
                'remark'          => I('post.remark', '', 'trim'),
            );
            $data = array_merge($rdata, $ndata);
            //创建人、创建时间
            $data['created_time'] = get_time();
            $data['created_user'] = session('user.user_id');
            $M = M('tms_dispatch_task');
            $id = $M->add($data);
            if ($id) {
                $res = array(
                    'status' => 0,
                    'msg'    => '创建成功'
                );
            } else {
                $res = array(
                    'status' => -1,
                    'msg'    => '创建失败'
                );
            }
            $this->ajaxReturn($res);
        } else {
            $this->display('tms:add-task');
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
}