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
        $len = strlen($ena13code);
        if($len != 13){
            return $ena13code;
        }

        $codeArr = array($ena13code);
        $pms = A('Pms','Logic');
        $pro_code_ena13 = $pms->get_SKU_by_ena_code();
        dump($pro_code_ena13);die;
    }
}
/* End of file WaveLogic.class.php */
/* Location: ./Application/Logic/WaveLogic.class.php */