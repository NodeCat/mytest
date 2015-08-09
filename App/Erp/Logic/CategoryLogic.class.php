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
namespace Erp\Logic;

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
        //获得分类
        $pmsLogic = A('Pms','Logic');
        $cats = $pmsLogic->get_SKU_category();
        if($params['second_child']){

            array_push($result, $params['second_child']);
            //第四级分类
            $cat_4 = $cats['list']['third_child'];
            $cat_data = $cat_4[$params['second_child']];
            $second_child = getSubByKey($cat_data, 'id');
            $result = array_merge($result,$second_child);

        }else{

            if(!$params['second_child'] && $params['second']){
                //一级分类
                $cat_3 = $cats['list']['second_child'];
                $cat_4 = $cats['list']['third_child'];
                $cat_data = $cat_3[$params['second']];
                $second_child = getSubByKey($cat_data, 'id');
                $result = array_merge($result,$second_child);
                //获取第四级分类
                foreach ($second_child as $key => $value) {
                    $cat_data_third_child = $cat_4[$value];
                    $second_childs = getSubByKey($cat_data_third_child, 'id');
                    $result = array_merge($result,$second_childs);
                }

                //$result = $second_child;

            }

            if(!$params['second_child'] && !$params['second'] && $params['top']){

                $cat_2 = $cats['list']['second'];
                $cat_3 = $cats['list']['second_child'];
                $cat_4 = $cats['list']['third_child'];
                $cat_data = $cat_2[$params['top']];
                $second = getSubByKey($cat_data, 'id');
                foreach ($second as $key => $value) {
                    $cat_data_second = $cat_3[$value];
                    $second_child = getSubByKey($cat_data_second, 'id');
                    $result = array_merge($result,$second_child);
                    //获取第四级分类
                    foreach ($second_child as $k => $v) {
                        $cat_data_third_child = $cat_4[$v];
                        $second_childs = getSubByKey($cat_data_third_child, 'id');
                        $result = array_merge($result,$second_childs);
                    }
                    //$result = array_merge($result,$second_child);
                }

            }
            if(!$params['second_child'] && !$params['second'] && !$params['top']){
                $cat_1 = $cats['list']['top'];
                $cat_2 = $cats['list']['second'];
                $cat_3 = $cats['list']['second_child'];
                $cat_4 = $cats['list']['third_child'];
                foreach ($cat_1 as $key => $value) {
                    $cat_data = $cat_2[$value['id']];
                    $second = getSubByKey($cat_data, 'id');
                    foreach ($second as $ky => $va) {
                        $cat_data_second = $cat_3[$va];
                        $second_child = getSubByKey($cat_data_second, 'id');
                        $result = array_merge($result,$second_child);
                        //获取第四级分类
                        foreach ($second_child as $k => $v) {
                            $cat_data_third_child = $cat_4[$v];
                            $second_childs = getSubByKey($cat_data_third_child, 'id');
                            $result = array_merge($result,$second_childs);
                        }
                        //$result = array_merge($result,$second_child);
                    }

                }


            }

        }

        return array_unique($result);
        
    }
}
/* End of file CategoryLogic.class.php */
/* Location: ./Application/Logic/CategoryLogic.class.php */