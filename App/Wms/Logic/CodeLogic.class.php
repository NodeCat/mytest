<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Logic;

class CodeLogic{

    public function getProCodeByEna13code($ena13code = '') {
        $ena13code = trim($ena13code);
        if(!$ena13code){
            return $ena13code;
        } 
        $codeArr = array($ena13code);
        $pms = A('Pms','Logic');
        $pro_code_ena13 = $pms->get_SKU_by_ena_code($codeArr);
        if(is_array($pro_code_ena13) && $pro_code_ena13 && isset($pro_code_ena13['list'])){

            if( $pro_code_ena13['status'] == 0){

                $pro_code = $pro_code_ena13['list']['0']['sku_number'];

                $sku_number = $pro_code?$pro_code:$ena13code;

            }else{

                $sku_number = $ena13code;
            }

        }else{

            $sku_number = $ena13code;
        }
        
        return $sku_number;
    }
}
/* End of file WaveLogic.class.php */
/* Location: ./Application/Logic/WaveLogic.class.php */