<?php
namespace Tms\Logic;
/**
 * GPS坐标转换，路程计算
 */
class GpsLogic{
    private $PI = 3.14159265358979324;

    // 根据坐标计算路程
    public function getDistance($arr) {
        $i = 0;
        $distance = 0;
        static $wgs_pre;
        // 计算路程
        foreach ($arr as $value) {
            $wgs = $this->gcj_decrypt($value['lat'],$value['lng']);
            if($i == 0){
                $wgs_pre = $wgs;
                $i++;
                continue;
            }
            $distance_sub = $this->distance($wgs_pre['lat'],$wgs_pre['lng'],$wgs['lat'],$wgs['lng']);
            $wgs_pre      = $wgs;
            $distance    += $distance_sub;
        }
        $distance = sprintf('%.3f',$distance/1000);
        return $distance;
    }

    //计算两经纬度之间的距离
    private function distance($latA, $lngA, $latB, $lngB) {
        $earthR = 6371000.;
        $x = cos($latA * $this->PI / 180.) * cos($latB * $this->PI / 180.) * cos(($lngA - $lngB) * $this->PI / 180);
        $y = sin($latA * $this->PI / 180.) * sin($latB * $this->PI / 180.);
        $s = $x + $y;
        if ($s > 1) $s = 1;
        if ($s < -1) $s = -1;
        $alpha = acos($s);
        $distance = $alpha * $earthR;
        return $distance;//m
    }

    // GCJ-02坐标 to WGS-84坐标
    public function gcj_decrypt($gcjLat, $gcjlng) {
        if ($this->outOfChina($gcjLat, $gcjlng))
            return array('lat' => $gcjLat, 'lng' => $gcjlng); 
        $d = $this->delta($gcjLat, $gcjlng);
        return array('lat' => $gcjLat - $d['lat'], 'lng' => $gcjlng - $d['lng']);
    }

    // 判断是不是在中国的地图上
    private function outOfChina($lat, $lng) {
        if ($lng < 72.004 || $lng > 137.8347)
            return TRUE;
        if ($lat < 0.8293 || $lat > 55.8271)
            return TRUE;
        return FALSE;
    }
    private function transformLat($x, $y) {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * $this->PI) + 40.0 * sin($y / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * $this->PI) + 320 * sin($y * $this->PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
 
    private function transformlng($x, $y) {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * $this->PI) + 40.0 * sin($x / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * $this->PI) + 300.0 * sin($x / 30.0 * $this->PI)) * 2.0 / 3.0;
        return $ret;
    }

    private function delta($lat, $lng) {
        // Krasovsky 1940
        //
        // a = 6378245.0, 1/f = 298.3
        // b = a * (1 - f)
        // ee = (a^2 - b^2) / a^2;
        $a = 6378245.0;//  a: 卫星椭球坐标投影到平面地图坐标系的投影因子。
        $ee = 0.00669342162296594323;//  ee: 椭球的偏心率。
        $dLat = $this->transformLat($lng - 105.0, $lat - 35.0);
        $dlng = $this->transformlng($lng - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * $this->PI;
        $magic = sin($radLat);
        $magic = 1 - $ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtMagic) * $this->PI);
        $dlng = ($dlng * 180.0) / ($a / $sqrtMagic * cos($radLat) * $this->PI);
        return array('lat' => $dLat, 'lng' => $dlng);
    }
    
}