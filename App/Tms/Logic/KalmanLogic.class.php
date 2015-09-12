<?php
namespace Tms\Logic;
/**
 * kalman过滤算法
 */
class KalmanLogic {

    public $x;  /* state */
    public $A;  /* x(n)=A*x(n-1)+u(n),u(n)~N(0,q) */
    public $H;  /* z(n)=H*x(n)+w(n),w(n)~N(0,r)   */
    public $q;   /* process(predict) noise convariance */
    public $r;   /* measure noise convariance */
    public $p;  /* estimated error convariance */
    public $gain;

    public function ponitFilter(&$arr,$Latlng = '')
    {   
        if ($Latlng == '') {
            return;
        }
        $k = $Latlng;
        $this->kalmanInit($arr[0][$k],0.001);
        foreach ($arr as $key => &$val) {
            if($key==0) {
                continue;
            }
            $val[$k] = $this->kalmanFilter($val[$k]);
        }
    }

    private function kalmanInit($init_x, $init_p)
    {
        $this->x = $init_x;
        $this->p = $init_p;
        $this->A = 1;
        $this->H = 1;
        $this->q = 0.003; // predict noise convariance
        $this->r = 0.01;  // predict error convariance
    }
    
    private function kalmanFilter($z_measure)
    {
        //predict
        $this->x = $this->A * $this->x;
        //p(n|n-1)=A^2*p(n-1|n-1)+q
        $this->p = $this->A * $this->A * $this->p + $this->q;
        // measurement
        $this->gain = $this->p * $this->H / ($this->p * $this->H * $this->H + $this->r);
        $this->x = $this->x + $this->gain * ($z_measure - $this->H * $this->x);
        $this->p = (1 - $this->gain * $this->H ) * $this->p ;
        return $this->x;
    }

}