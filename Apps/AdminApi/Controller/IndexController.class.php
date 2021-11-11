<?php
/**
 *
 * IndexController.class.php
 *
 * 后台接口模块默认地址
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class IndexController extends CommsController {
	public function index(){
		// $this->ajaxReturn( array('code'=>'706', 'message'=>'请登录') );
		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array(), ) );
		// dump($_SERVER['DOCUMENT_ROOT']);
		// dump(1);
	}

	//用于后台首页的权限控制
	public function home(){
		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok') );
	}

	public function init(){

	}


	public function test(){
		$nums1 = array(2,3);
		$nums2 = array(1,2);
		if( empty($nums1) && empty($nums2) ){
            $obj = array();
        }else if( empty($nums1) ){
            $_nums2 = array_flip($nums2);
            $obj = $_nums2;
        }else if( empty($nums2) ){
            $_nums1 = array_flip($nums1);
            $obj = $_nums1;
        }else{
            $_nums1 = array_flip($nums1);
            $_nums2 = array_flip($nums2);

            $obj = $_nums1 + $_nums2;
            ksort($obj);
        }

        $arr = array();
        foreach($obj as $key => $value){
            $arr[] = $key;
        }
        
        $counts = count($arr);
        $counts_half = $counts / 2;
        if( is_int($counts_half) ){
            $half_num = ($arr[$counts_half] + $arr[$counts_half-1]) / 2;
        }else{
            $half_num = $arr[floor($counts_half)];
        }

        echo $half_num;
	}
}