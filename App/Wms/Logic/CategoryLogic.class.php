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

class CategoryLogic{
    /**
     * 根据父级分类获取子级分类
     * getPidBySecondChild
     *  
     * @param Array $params 三级分类
     * @author liuguangping@dachuwang.com
     * @return Array $data;
     * 
     */
    public function getPidBySecondChild($params = array()) {
        
        $result = array();
        if($params['second_child']){

            array_push($result, $params['second_child']);

        }else{

            //获得分类
            $pmsLogic = A('Pms','Logic');
            $cats = $pmsLogic->get_SKU_category();

            if(!$params['second_child'] && $params['second']){
                //一级分类
                $cat_3 = $cats['list']['second_child'];
                $cat_data = $cat_3[$params['second']];
                $second_child = getSubByKey($cat_data, 'id');
                $result = $second_child;

            }

            if(!$params['second_child'] && !$params['second'] && $params['top']){

                $cat_2 = $cats['list']['second'];
                $cat_3 = $cats['list']['second_child'];
                $cat_data = $cat_2[$params['top']];
                $second = getSubByKey($cat_data, 'id');
                foreach ($second as $key => $value) {
                    $cat_data_second = $cat_3[$value];
                    $second_child = getSubByKey($cat_data_second, 'id');
                    $result = array_merge($result,$second_child);
                }

            }
            if(!$params['second_child'] && !$params['second'] && !$params['top']){
                $cat_1 = $cats['list']['top'];
                $cat_2 = $cats['list']['second'];
                $cat_3 = $cats['list']['second_child'];
                foreach ($cat_1 as $key => $value) {
                    $cat_data = $cat_2[$value['id']];
                    $second = getSubByKey($cat_data, 'id');
                    foreach ($second as $ky => $va) {
                        $cat_data_second = $cat_3[$va];
                        $second_child = getSubByKey($cat_data_second, 'id');
                        $result = array_merge($result,$second_child);
                    }

                }

            }

        }

        return array_unique($result);
        
    }
}
/* End of file CategoryLogic.class.php */
/* Location: ./Application/Logic/CategoryLogic.class.php */