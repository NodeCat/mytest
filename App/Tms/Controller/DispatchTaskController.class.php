<?php
/**
 *  TMS派车任务控制器
 *  @author    pengyanlei@dachuwang.com
 */
namespace Tms\Controller;
use Think\Controller;

class DispatchTaskController extends Controller
{
    protected function _initialize(){}

    public function index()
    {
        $this->display('tms:dispatch-task');
    }

    public function addTask()
    {
        $warehouses = A('Wms/Distribution', 'Logic')->getAllWarehouse();
        // $task_types = 
        // $car_types  = 
        $this->display('tms:add-task');
    }

}