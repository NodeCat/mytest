<?php
namespace Wms\Logic;

class StockInLogic{
    
    //$batch_flg 如果有值 则引进批次收货 liuguangping
    public function getInQty($inId,$code,$batch_flg = null) {
        if(empty($inId) || empty($code)) {
            return array('res'=>false,'msg'=>'必填字段不能为空。');
        }
        $pro_code = $this->getCode($code);
        if(!empty($pro_code)) {
            $code = $pro_code;
        }

        $in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);
        $detail = $this->getLine($inId,$code);
        if(empty($detail)) {
            return array('res'=>false,'msg'=>'单据中未找到该货品。');
        }

        $map['pid'] = $inId;
        $map['pro_code'] = $code;
        $prepareOnQty = 0;
        //加入批次 liuguangping
        if($batch_flg){
            $bill_in_detail_m = M('stock_bill_in_detail');
            $cate_qty_r = $bill_in_detail_m->field('(sum(expected_qty) - sum(prepare_qty)) as qtyForCanInC,sum(receipt_qty) as receipt_qty_sum')->where($map)->select();
            if ($cate_qty_r) {
                $prepareOnQty = $cate_qty_r['0']['qtyforcaninc'];
                $receipt_qty_sum =  $cate_qty_r['0']['receipt_qty_sum'];
            }
        }else{
            $bill_in_detail_info = M('stock_bill_in_detail')->group('pro_code')->where($map)->find();
            $prepareOnQty = $bill_in_detail_info['expected_qty'] - $bill_in_detail_info['receipt_qty'];
            $receipt_qty_sum =  $bill_in_detail_info['receipt_qty'];
        }

        if($prepareOnQty == 0) {
            return array('res'=>false,'msg'=>'该货品没有待入库量。');
        }

        $detail['id'] = $in['id'];
        $detail['code'] = $in['code'];
        $detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
        $detail['moved_qty'] = $this->getQtyForIn($inId,$code,$batch_flg);
        $detail['expected_qty'] = $detail['expected_qty'];
        $detail['receipt_qty'] = $receipt_qty_sum;
        return array('res'=>true,'data'=>$detail);
    }

    //liuguangping 加入批次 $batch_flg 有加入批次，无就正常走
    public function getOnQty($inId,$code,$batch_flg = null) {
        if(empty($inId) || empty($code)) {
            return array('res'=>false,'msg'=>'必填字段不能为空。');
        }
        $pro_code = $this->getCode($code);
        if(!empty($pro_code)) {
            $code = $pro_code;
        }
        
        $detail = $this->getLine($inId,$code);
        if(empty($detail)) {
            return array('res'=>false,'msg'=>'单据中未找到该货品。');
        }

        $in = M('stock_bill_in')->field('id,code,type,refer_code,status')->find($inId);
        //liuguangping 改了，这方法是查待库存量
        //$qtyForOn = $this->getQtySum($in['id'],$code);//这个是按照系统分配批次上
        //加入批次条件 批次是先进先出 以前是系统分配现在是拿第一个批次上架
        $qtyForOn = $this->getQtyBatchSum($in['id'],$code);
        if(empty($qtyForOn)) {
            return array('res'=>false,'msg'=>'该货品没有待上架量。');
        }
        
        $detail['id'] = $in['id'];
        $detail['code'] = $in['code'];
        $detail['pro_names'] = $detail['pro_name'] .'（'. $detail['pro_attrs'].'）';
        $detail['batch'] = $qtyForOn['batch']?$qtyForOn['batch']:$in['code'];//如果有批次则就显示批次号没有显示到货单单号
        $detail['moved_qty'] = $qtyForOn['prepare_qty'];
        return array('res'=>true,'data'=>$detail);
    }


    public function getCode($barcode){
        $map['barcode'] = $barcode;
        $map['is_deleted'] = 0;
        $res = M('product_barcode')->field('pro_code')->where($map)->find();
        if(empty($res)) {
            return $res;
        }
        else {
            return $res['pro_code'];
        }
    }

    //上架逻辑 
    //$inId 入库单id 
    //$code pro_code
    //$qty 本次上架量
    //$location_code 上架库位
    //$status 本次上架状态
    //$product_date 生产日期
    public function on($inId,$code,$qty,$location_code,$status,$product_date){

        if(empty($inId) || empty($code)  || $location_code == '' || empty($status) || empty($product_date)) {
            return array('res'=>false,'msg'=>'必填字段不能为空。');
        }
        if(!is_numeric($qty)|| empty($qty)) {
            return array('res'=>false,'msg'=>'上架数量有误。');
        }
        //获取入库单信息
        $in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);

        if(empty($in)) {
            return array('res'=>false,'msg'=>'未找到该入库单。');
        }

        $pid = $in['id'];
        //liuguangping 改了，这方法是查待库存量
        //$qtyForOn = $this->getQtySum($pid, $code);//这个是按照系统分配批次上
        //加入批次条件 批次是先进先出 以前是系统分配现在是拿第一个批次上架
        $qtyForOn = $this->getQtyBatchSum($in['id'],$code);
        //指定批次
        $batch = $qtyForOn['batch'];
        if(empty($qtyForOn)) {
            return array('res'=>false,'msg'=>'该货品没有待上架量。');
        }
        if($qtyForOn['prepare_qty'] < $qty) {
            return array('res'=>false,'msg'=>'上架数量不能大于该货品待上架数量');
        }

        //检查库位
        $map['wh_id'] = $in['wh_id'];
        $map['code'] = $location_code;
        $map['type'] = '2';
        $map['is_deleted'] = 0;
        $res = M('location')->field('id,status')->where($map)->find();
        unset($map);
        if(empty($res)) {
            return array('res'=>false,'msg'=>'库位不存在。');
        }
        else {
            $location_id = $res['id'];
            $map['location_id'] = $location_id;
            $location = M('location_detail')->field('is_mixed_pro,is_mixed_batch')->where($map)->find();
            unset($map);
        }
        if($res['status'] != $status){
            return array('res'=>false,'msg'=>'状态不一致，目标库位的状态是：'.en_to_cn($res['status']).'状态');
        }

        //我要加入批次来出库 liuguangping 2015.7.28
        //出库详细
        $where = array();
        $where['pid'] = $pid;
        $where['pro_code'] = $code;
        //指定批次
        if($batch){
            $where['batch'] = $batch;
        }
        $where['is_deleted'] = 0;
        $bill_detail = M('stock_bill_in_detail')->where($where)->select();
        //判断目标库位是否可以 混货 混批次
        $data['dest_location_id'] = $location_id;
        $data['wh_id'] = $in['wh_id'];
        //$data['status'] = $status;
        if($batch){
            $data['batch'] = $batch;
        }else{
            $data['batch'] = $in['code'];
        }

        $data['pro_code'] = $code;
        $res = A('Stock','Logic')->checkLocationMixedProOrBatch($data);
        unset($data);

        //禁止混批次
        if($res['status'] == 0){
            return $res;
        }


        $diff = $qty;//要上架的数量
        $refer_code = $in['refer_code'];
        $wh_id = $in['wh_id'];
        //扣库存操作
        //有批次走分批次走，没有则按照原来的走
        if($batch){
            $has_source_batch=true;
        }else{
            $batch = $in['code'];
            $has_source_batch = false;
        }
        
        $this->updateStockUpStatus($inId,$code,$qty,$batch,$location_id,$status,$product_date,$refer_code,$wh_id,$has_source_batch,$inId);


        //修改erp状态
        $bill_in_r = M('stock_bill_in')->field('code,type')->find($inId);
        if($bill_in_r['type'] == 4){
            A('TransferIn','Logic')->updateTransferInStatus($bill_in_r['code'],'up');
        }

        //修改状态
        $oned = $this->checkOn($inId); 
        if($oned == 2) {
            $data['status'] = '33';
            $map['id'] = $inId;
            $map['status'] = '31';
            $map['is_deleted'] = 0;
            M('stock_bill_in')->where($map)->save($data);
            unset($map);
            unset($data);
        }

        if (intval($diff*100) == 0) {
            $qtys = $qty;
        }elseif(f_sub($cate_qty, $qty, 2) > 0) {
            $qtys = $qty;
        }elseif(f_sub($cate_qty, $qty, 2) < 0){
            $qtys = $cate_qty;
        }
        $line = $this->getLine($inId,$code);
        return array('res'=>ture,'msg'=>'库位：'.$location_code.'。数量：<strong>'.$qtys.'</strong> '.$line['pro_uom'].'。名称：['.$line['pro_code'] .'] '. $line['pro_name'] .'（'. $line['pro_attrs'].'）');

    }

    public function updateStockUpStatus($inId,$code,$qty,$batch,$location_id,$status,$product_date,$refer_code,$wh_id,$has_source_batch,$inId){
        //写库存
        $line = $this->getLine($inId,$code);
        $pro_code = $line['pro_code'];
        $pro_uom = $line['pro_uom'];
        $pro_qty = $qty;
        //管理批次号
        get_batch($batch);
        $res = A('Stock','Logic')->adjustStockByShelves($wh_id,$location_id,$refer_code,$batch,$pro_code,$pro_qty,$pro_uom,$status,$product_date,$inId,$has_source_batch);

        //更新到货单详情 正品 残品 数量
        $map['pid'] = $inId;
        $map['pro_code'] = $code;
        //判断是否是原来的
        if ($has_source_batch) {
            $map['batch'] = $batch;
        }

        if($status == 'qualified'){
            M('stock_bill_in_detail')->where($map)->setInc('qualified_qty',$qty);
        }
        if($status == 'unqualified'){
            M('stock_bill_in_detail')->where($map)->setInc('unqualified_qty',$qty);
        }
        //更新上架日期
        $stock_bill_in_detail_info = M('stock_bill_in_detail')->where($map)->find();
        if($stock_bill_in_detail_info['shelves_date'] == '0000-00-00 00:00:00'){
            $data['shelves_date'] = date('Y-m-d H:i:s');
            M('stock_bill_in_detail')->where($map)->save($data);
            unset($data);
        }
        
        //是否修改生产日期 暂定每个批次只有一个生产日期 如果有不同 取最早的生产日期

        if(strtotime($line['product_date']) > strtotime($product_date) || $line['product_date'] == '0000-00-00 00:00:00'){
            $stock_bill_in_detail = D('stock_bill_in_detail');
            $data['product_date'] = $product_date;
            $data = $stock_bill_in_detail->create($data,2);
            $stock_bill_in_detail->where($map)->save($data);
            unset($data);
        }

        //修改erp_上架量

        if ($inId) {
            $bill_in_r = M('stock_bill_in')->field('code,type')->find($inId);
            if($bill_in_r && $bill_in_r['type'] == '4'){
                //调拨入库
                $in_code = $bill_in_r['code'];
                $batch = $batch;
                $qty = $qty;
                //$is_up = up 上架量 waiting 待上架
                A('TransferIn','Logic')->updateStockInQty($in_code, $pro_code, $batch,$qty,$status,'up');

            }
        }


    }

    public function in($inId,$code,$qty) {
        if(empty($inId) || empty($code) || $qty =='') {
            return array('res'=>false,'msg'=>'必填字段不能为空。');
        }
        if(!is_numeric($qty) || empty($qty) || $qty < 0) {
            return array('res'=>false,'msg'=>'验收数量有误。');
        }
        $map['pid'] = $inId;
        $map['pro_code'] = $code;

        //出库详细表中加入批次条件，有能一个sku_code对应两个批次
        //首先判断用户要收货的数量是否大于总可验收数量；
        $bill_in_detail_m = M('stock_bill_in_detail');
        $cate_qty_r = $bill_in_detail_m->field('(sum(expected_qty) - sum(receipt_qty)) as qtyForCanInC')->where($map)->select();
        $cate_qty = 0;
        if ($cate_qty_r) {
            $cate_qty = $cate_qty_r['0']['qtyforcaninc'];
        }
        if (empty($cate_qty) || $cate_qty < $qty) {
                return array('res'=>false,'msg'=>'验收数量不能大于可验收数量。');
        }

        $bill_in_detail_info = M('stock_bill_in_detail')->where($map)->select();
        $diff = $qty;//要上架的数量
        foreach ($bill_in_detail_info as $key => $value) {
            if($diff <= 0){
                break;
            }
            //可验收数量 = 预计数量 - 实际验收数
            $qtyForCanIn = f_sub($value['expected_qty'], $value['receipt_qty'], 2);
            $qtycom = $qtyForCanIn;
            $diffcom = $diff;
            if ($qtycom == 0){
                continue; //收货完成
            } elseif ($qtycom <= $diffcom) {
                //当可以上的量小于总共上的，则这次可以把可验收数量全部数量
                $this->updateStockInStatus($inId,$code,$qtyForCanIn,$value['batch']);
                $diff = f_sub($diff, $qtyForCanIn, 2);
            } elseif ($qtycom > $diffcom) {
                $this->updateStockInStatus($inId,$code,$diff,$value['batch']);
                $diff = 0;
            }
        }

        //更新收货时间
        if($bill_in_detail_info[0]['receipt_date'] == '0000-00-00 00:00:00'){
            $data['receipt_date'] = date('Y-m-d H:i:s');
            M('stock_bill_in_detail')->where($map)->save($data);
        }

        unset($map);

        if($diff == 0) {
            $ined = $this->checkIn($inId);
            // $ined == 2 不可以验收 待上架 等于1时候 可以验收
            if($ined == 2) { //
                $data['status'] = '31';
                $map['id'] = $inId;
                $map['status'] = '21';
                $map['is_deleted'] = 0;
                M('stock_bill_in')->where($map)->save($data);
                
            }
        }

        //修改erp状态
        $bill_in_r = M('stock_bill_in')->field('code,type')->find($inId);
        if($bill_in_r && $bill_in_r['type'] == '4'){
            A('TransferIn','Logic')->updateTransferInStatus($bill_in_r['code']);
        }

        if ($diff == 0) {
            $qtys = $qty;
        }elseif(f_sub($cate_qty, $qty, 2) > 0) {
            $qtys = $qty;
        }elseif(f_sub($cate_qty, $qty, 2) < 0){
            $qtys = $cate_qty;
        }
        $line = $this->getLine($inId,$code);
        return array('res'=>true,'msg'=>'数量：<strong>'.$qtys.'</strong> '.$line['pro_uom'].'。名称：['.$line['pro_code'] .'] '. $line['pro_name'] .'（'. $line['pro_attrs'].'）');
        
    }

    //修改状态和待入库量和实际收货量 $inId 入库单id $code pro_code商品编码 $diff要改变的数量 $batch 批次 liuguangping
    public function updateStockInStatus($inId,$code,$qty,$batch=''){
        $map = array();
        //当可以上的量小于总共上的，则这次可以把可验收数量全部数量
        if($batch){
            $map['batch'] = $batch;
        }
        $line = $this->getLine($inId,$code,$bacth);
        $pro_uom = $line['pro_uom'];
        //根据pid + pro_code + pro_uom 更新stock_bill_in_detail expected_qty 减少 prepare_qty 增加
        $map['pro_uom'] = $pro_uom;
        $map['pid'] = $inId;
        $map['pro_code'] = $code;
        $res = M('stock_bill_in_detail')->where($map)->setInc('prepare_qty',$qty);
        $res = M('stock_bill_in_detail')->where($map)->setInc('receipt_qty',$qty);

        //修改erp_到货量
        if ($inId) {
            
            $bill_in_r = M('stock_bill_in')->field('code,type')->find($inId);
            if($bill_in_r && $bill_in_r['type'] == '4'){
                //调拨入库
                $in_code = $bill_in_r['code'];
                $pro_code = $code;
                $batch = $batch;
                $qty = $qty;
                A('TransferIn','Logic')->updateStockInQty($in_code, $pro_code, $batch, $qty);

            }
        }
    }

    //根据stock_bill_in_detail 检查是否已经有入库
    public function haveCheckIn($inId,$pro_code=''){
        $M = M('stock_bill_in_detail');
        $map['pid'] = $inId;
        if(!empty($pro_code)) {
            $map['pro_code'] = $pro_code;
        }
        $in = $M->group('refer_code,pro_code')->where($map)->getField('pro_code,refer_code,expected_qty,prepare_qty,receipt_qty');
        foreach($in as $k => $val){
            //如果receipt_qty 已收量不为0 则认为是已经入库了
            if($val['receipt_qty'] > 0){
                return true;
            }
        }

        return false;
    }

    public  function checkIn($inId,$pro_code=''){
        $M = M('stock_bill_in_detail');
        $map['pid'] = $inId;
        if(!empty($pro_code)) {
            $map['pro_code'] = $pro_code;
        }
        $in = $M->group('refer_code,pro_code')->where($map)->getField('pro_code,refer_code,expected_qty,prepare_qty');
        unset($map['pid']);
        foreach ($in as $key => $val) {
            if($val['expected_qty'] - $val['prepare_qty'] > 0){
                return 1;
            }
        }
        return 2;
        
    }

    public  function checkOn($inId,$pro_code=''){
        $in = M('stock_bill_in')->field('id,wh_id,code,type,refer_code,status')->find($inId);
        if(!empty($pro_code)) {
            $map['pro_code'] = $pro_code;
        }
        if($in['status']=='21') {
            return 1;
        }
        //根据pid查询stock_bill_in_detail 所有记录的prepare_qty是否为0 如果为0 则上架完毕
        $map['pid'] = $inId;
        $res = M('stock_bill_in_detail')->where($map)->select();
        foreach ($res as $key => $val) {
            if($val['prepare_qty'] != 0 ){
                return 1;
            }
        }
        return 2;
    }

    //已经移动的数量 wangshuang
    //待入库量 prepare_qty的总和 liang
    public function getQtyForIn($inId,$pro_code){
        $M = M('stock_bill_in_detail');
        $map['pid'] = $inId;
        $map['pro_code'] = $pro_code;
        //待入库量
        $in = $M->field('refer_code,pro_code,sum(prepare_qty) as qty_total')->group('pro_code')->where($map)->find();
        
        if(empty($in)) {
            return 0;
        }
        unset($map['pid']);
        return $in['qty_total'];
    }

    /**
     * getQtyForOn 获取同一批次该商品的相应数量
     * @param Int $wh_id 仓库id
     * @param String $batch 批次
     * @param String $pro_code 货物编码
     * @author liuguangping@dachuwang.com
     * @since 2015-06-13
     */
    public function getQtyForOn($batch,$pro_code,$wh_id = null){
        $map = array();
        if($wh_id !== null){
            $map['wh_id'] = $wh_id;
        }
        $map['pro_code'] = $pro_code;
        $map['refer_code'] = $batch;
        $res = M('stock_bill_in_detail')->where($map)->find();
        if(!$pro_code || !$batch || empty($res)) {
            return 0;
        }
        else {
            if(isset($map['wh_id'])){
                return $res['done_qty'];
            }else{
                return $res['prepare_qty'];
            }
        }
    }

    /**
     * getQtySum 获取同一入库单某个商品数量
     * @param Int $pid 入库单id
     * @param String $pro_code 货物编码
     * @author liuguangping@dachuwang.com
     * @since 2015-07-29
     */
    public function getQtySum($pid,$pro_code){
        $map = array();
        if ($pid) {
            $map['pid'] = $pid;
        }
        if ($pro_code) {
            $map['pro_code'] = $pro_code;
        }
        $bill_in_detail_m = M('stock_bill_in_detail');
        $cate_qty_r = $bill_in_detail_m->field('sum(prepare_qty) as qty')->where($map)->select();
        $cate_qty = 0;
        if ($cate_qty_r) {
            $cate_qty = $cate_qty_r['0']['qty'];
        }
        
        if(!$pro_code || !$pid || empty($cate_qty_r)) {
            return 0;
        } else {
            return $cate_qty;
        }
    }

    /**
     * getQtyBatchSum 获取同一入库单某个商品先进先上的第一条数量（未上架完）
     * @param Int $pid 入库单id
     * @param String $pro_code 货物编码
     * @author liuguangping@dachuwang.com
     * @since 2015-07-31
     */
    public function getQtyBatchSum($pid,$pro_code){
        $map = array();
        $result = array();
        if ($pid) {
            $map['pid'] = $pid;
        }
        if ($pro_code) {
            $map['pro_code'] = $pro_code;
        }
        $bill_in_detail_m = M('stock_bill_in_detail');
        $bill_in_detail = $bill_in_detail_m->where($map)->field('prepare_qty,batch')->order('id asc')->select();
        foreach ($bill_in_detail as $key => $value) {
            if ($value['prepare_qty']>0) {
                $result['batch'] = $value['batch'];
                $result['prepare_qty'] = $value['prepare_qty'];
                break;
            }
        }
        if(!$pro_code || !$pid || empty($bill_in_detail)) {
            return 0;
        } else {
            return $result;
        }
    }

    //到货单 实收量 已经收到的货品量
    public function getQtyForReceipt($batch,$pro_code){
        $map['pro_code'] = $pro_code;
        $map['pid'] = $batch;
        $res = M('stock_bill_in_detail')->where($map)->find();

        if(empty($res)) {
            return 0;
        }
        else {
            return $res['receipt_qty'];
        }
    }
    public function getLine($inId,$code,$batch = ''){
        $map['pid'] = $inId;
        $map['pro_code'] = $code;
        $map['is_deleted'] = '0';
        if ($batch) {
            $map['batch'] = $batch;
        }
        $detail = M('stock_bill_in_detail')
        ->field('pro_code,pro_name,pro_attrs,pro_uom,sum(expected_qty) as expected_qty,receipt_qty,product_date')
        ->group('pro_code')->where($map)->find();
        return $detail;
    }
    public function finishByPurchase($purchaseId) {
        $map['is_deleted'] = 0;
        $map['id'] = $purchaseId;
        $data['status'] = '13'; //完成
        $M = M('stock_purchase');
        //$M->where($map)->save($data);
        $purchase = $M->field('code')->find($purchaseId);
        unset($map['id']);
        $map['refer_code'] = $purchase['code'];

        $M = M('stock_bill_in');
        $M->where($map)->save($data);
        
        return true;
    }

    //加入入库单 @pass_reduce_ids 出库单id liuguangping @todoliuguangping
    public function addWmsIn($pass_reduce_ids)
    {
        if (empty($pass_reduce_ids)) {
            return false;
        }
        //查找出库单和出库单详细
        $out_m = M('stock_bill_out_container');
        $map['o.type'] = 5;//调拨单
        $map['o.status'] = 2;//已出库
        $map['o.id'] = array('in',$pass_reduce_ids);
        $map['o.is_deleted'] = 0;
        $map['c.is_deleted'] = 0;

        //查询发运后的商品
        $out_m->join(' as c left join stock_bill_out as o on o.code = c.refer_code')->where($map);
        $out_m2 = clone $out_m;//深度拷贝
        //插入stokc_bill_in_detail表
        $out_container = $out_m->field('c.batch,c.pro_code,c.qty,o.*')->select();
        //插入stock_bill_in表 根据同一个调拨单，同一件商品和批次生产一张调拨单
        $out_infos = $out_m2->field('c.batch,c.pro_code,c.qty,o.*')->group('o.refer_code')->select();
    
        if (!$out_infos) {
            return false;
        }
        $bill_in_m = M('stock_bill_in');
        $bill_in_detail_m = M('stock_bill_in_detail');
        $erp_transfer_m = M('erp_transfer');
        //插入入库单
        $stock_type = M('stock_bill_in_type');
        $type_name = $stock_type->field('type')->where(array('id' => 4))->find();
        $numbs = M('numbs');
        $name = $numbs->field('name')->where(array('prefix' => $type_name['type']))->find();
        foreach ($out_infos as $key => $value) {
            $bill_in = array();
            $bill_in['code'] = get_sn($name['name']);
            //根据调拨单获取获取入库单
            $wh_id_in_m['trf_code'] = $value['refer_code'];
            $erp_transfer_win = $erp_transfer_m->where($wh_id_in_m)->getField('wh_id_in');

            //检查是否是调拨单
            if (!$erp_transfer_win) {
                continue;
            }

            $bill_in['wh_id'] = $erp_transfer_win?$erp_transfer_win:'';//入库仓库@todo
            $bill_in['type'] = 4;
            $bill_in['company_id'] = 1;
            $bill_in['refer_code'] = $value['refer_code'];//调拨单
            $bill_in['pid'] = 0;
            //$bill_in['batch_code'] = get_batch($value['batch']);
            $bill_in['partner_id'] = '';//供应商；@todoliuguangping
            $bill_in['remark'] = '调拨入库单';
            $bill_in['updated_time'] = get_time();
            $bill_in['created_user'] = session('user.uid');
            $bill_in['updated_user'] = session('user.uid');
            $bill_in['created_time'] = get_time();
            $bill_in['status'] = 21; //状态 21待入库
            if($pid = $bill_in_m->add($bill_in)){
                $process_logic = A('Process', 'Logic');
                //插入出库单详细表
                $detail = array();
                $issetCode = array();
                $i = 0;
                foreach ($out_container as $ky => $val) {
                    if($value['refer_code'] == $val['refer_code']) {
                        if (!isset($issetCode[$val['pro_code'].'-'.$val['batch']])) {
                            //查询出库商品的属性 同一个出库单只有唯一一个商品
                            $where = array();
                            $where['pid'] = $val['id'];
                            $where['pro_code'] = $val['pro_code'];
                            $where['is_deleted'] = 0;
                            $out_detail = array();
                            if($val['pro_code']){
                                $out_detail = M('stock_bill_out_detail')->where($where)->find();
                            }
                            $detail[$i]['wh_id'] = $bill_in['wh_id'];
                            $detail[$i]['pid'] = $pid;
                            $detail[$i]['refer_code'] = $value['refer_code']?$value['refer_code']:'';
                            $detail[$i]['pro_code'] = $val['pro_code']? $val['pro_code']:'';
                            $detail[$i]['pro_name'] = $out_detail['pro_name']?$out_detail['pro_name']:'';
                            $detail[$i]['pro_attrs'] = $out_detail['pro_attrs']?$out_detail['pro_attrs']:'';
                            $detail[$i]['batch'] = $val['batch'];
                            $detail[$i]['expected_qty'] = $val['qty'];
                            $detail[$i]['pro_uom'] = $out_detail['measure_unit']?$out_detail['measure_unit']:'';
                            $detail[$i]['price_unit'] = $process_logic->get_price_by_sku($val['batch'], $val['pro_code']);//平均价
                            $detail[$i]['prepare_qty'] = 0;
                            $detail[$i]['done_qty'] = 0;
                            $detail[$i]['receipt_qty'] = 0;
                            $detail[$i]['created_user'] = session('user.uid');
                            $detail[$i]['updated_user'] = session('user.uid');
                            $detail[$i]['created_time'] = date('Y-m-d H:i:s', time());
                            $detail[$i]['updated_time'] = date('Y-m-d H:i:s', time());
                            $i++;
                        }
                        $issetCode[$val['pro_code'].'-'.$val['batch']] = 1;
                        
                    }
                }
                $bill_in_detail_m->addAll($detail);

            }
        }
        return ture;

        
        
    }

}