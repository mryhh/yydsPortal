<?php
/**
 *
 * CommsController.class.php
 *
 * 后台接口模块 通用方法基类
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class CommsController extends Controller {
	//获取参数
	protected function _checkParam($param, $type='post', $check=true){
		if($type == 'post'){
			$data = I('post.');
		}else{
			$data = I('get.');
			// if( empty($data) ){
			// 	foreach ($param as $key => $value) {
			// 		$data[$key] = I($key);
			// 	}
			// }
		}

		foreach ($param as $key => $value) {
			if( !isset($data[$key]) || $data[$key] == ''){
				if($check){
					$this->ajaxReturn( array('code'=>'-1', 'message'=>'参数错误，缺少' . $key, 'data'=>array(),) );
				}
			}else{
				$rdata[$key] = $data[$key];
			}
		}

		return $rdata;
	}
	
	// protected function _findParam($param, $type='post'){
	// 	if($type == 'post'){
	// 		$data = I('post.');
	// 	}else{
	// 		$data = I('get.');
	// 		// if( empty($data) ){
	// 		// 	foreach ($param as $key => $value) {
	// 		// 		$data[$key] = I($key);
	// 		// 	}
	// 		// }
	// 	}

	// 	foreach ($param as $key => $value) {
	// 		if( !isset($data[$key]) || $data[$key] == ''){
	// 			// $this->ajaxReturn( array('code'=>'-1', 'message'=>'参数错误，缺少' . $key, 'data'=>array(),) );
	// 		}else{
	// 			$rdata[$key] = $data[$key];
	// 		}
	// 	}

	// 	return $rdata;
	// }

	protected function moveImg($src='', $new_src=''){
		// $src = "./Public/Files/uploads/images/page/5ef0f130182f9.png";
		// $new_src = "./Public/Files/uploads/images/page/1/";
		$_src = "." . $src;
		$_new_src = "." . $new_src;
		if (! is_dir ( $_new_src )) {
			mkdir ( $_new_src, 0777, true );
		}

		if ( @rename($_src, $_new_src . basename($_src)) ) {
			return $new_src . basename($_src );
		}else{
			return $src;
		}
	}
}