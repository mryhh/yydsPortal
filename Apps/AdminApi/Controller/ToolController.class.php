<?php
/**
 *
 * IndexController.class.php
 *
 * 后台接口模块 工具类
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

// require_once "./Library/TencentOssV53/src/Qcloud/Cos/Client.php";

class ToolController extends CommController {
	/**
	 * 上传图片
	 */
	public function uploadImg(){
		// // $path = I('post.path');
		// $data = $this->_checkParam( array('path'=>'') );
		// $file = $_FILES['file']
		// //上传图片到本地
		// $upload = new \Think\Upload();								// 实例化上传类
		// $upload->maxSize = 20 * 1024 * 1024;							// 设置附件上传大小
		// $upload->exts = array('jpg', 'gif', 'png', 'jpeg');			// 设置附件上传类型
		// $upload->rootPath = './Public';								// 文件上传保存的根路径   
		// $upload->savePath = "/uploads/" . $data['path'] . "/";		// 设置附件上传目录
		// $upload->autoSub = false;									// 不生成时间目录
		// $info = $upload->uploadOne($file);
		// if (!$info) {
		// 	//上传错误提示错误信息
		// 	// return array('url'=>'err:'.$upload->getError());
		// 	$this->ajaxReturn( array('code'=>'-1', 'message'=>$upload->getError()) );
		// } else {
		// 	// return array('url'=>'/' . $info['savepath'] . $info['savename']);
		// 	$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('url'=>'/' . $info['savepath'] . $info['savename']),) );
		// }

		//支持批量上传
		$files = $_FILES;
		$data = $this->_checkParam( array('id'=>'', 'path'=>'',) );
		$file_path = '/Public/Files/uploads/images/';

		if ( !is_dir ( '.' . $file_path ) ) {
			mkdir('.' . $file_path, 0777, true);
		}

		if( empty($files) ){
			$this->ajaxReturn(array('code'=>'-1', 'message'=>'未检测到上传文件', array(),));
		}
		//上传图片到本地
		$upload = new \Think\Upload();									// 实例化上传类
		$upload->maxSize = 20 * 1024 * 1024;							// 设置附件上传大小
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');				// 设置附件上传类型
		$upload->rootPath = '.' . $file_path;							// 文件上传保存的根路径
		// $upload->subName = $data['path'];
		if( $data['id'] != 'new' ){
			$upload->autoSub = false;									// 不生成时间目录
			$upload->savePath = $data['path'] . "/" . $data['id'] ."/";	// 设置附件上传目录
		}else{
			$upload->autoSub = false;									// 不生成时间目录
			// $rand = date("YmdHis")."_".rand(1000,9999);
			$rand = date("Ymd");
			$upload->savePath = $data['path'] . "/_temp/" . $rand . "/";	// 设置附件上传目录
		}

		$info = $upload->upload();
		if (!$info) {// 上传错误提示错误信息
			//处理上传错误信息
			// $this->error($upload->getError());
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$upload->getError(), 'data'=>'') );
		}else{
			$_config = C();
			foreach ($info as $key => $value) {
				$info[$key]['src'] = $file_path . $value['savepath'] . $value['savename'];
				// $info[$key]['all_src'] = ROOT_PATH . $info[$key]['src'];
				//添加到oss
				if( $_config['OSS']['REGION'] != ''){
					if( $_config['OSS']['TYPE'] == 'TencentOSS' ){
						$this->TencentOss($info[$key]['src']);
					}
				}
			}
			$this->ajaxReturn( array('code'=>'1', 'message'=>'图片上传成功', 'data'=>array('infos'=>$info, 'files'=>$files,),) );
		}
	}

	//创建目录
	// private function directory($dir){
	// 	if ( is_dir($dir) ) { //查看目录是否已经存在或尝试创建，加一个@抑制符号是因为第一次创建失败，会报一个“父目录不存在”的警告。
	// 		return true;
	// 		//echo $dir . "创建成功<br>";  //输出创建成功的目录
	// 	} else {
	// 		$dir_arr = explode('/', $dir); //当子目录没创建成功时，试图创建父目录，用explode()函数以'/'分隔符切割成一个数组
	// 		mkdir($dir_arr[0], 0777);
	// 		// array_pop($dir_arr); //将数组中的最后一项（即子目录）弹出来，
	// 		unset($dir_arr[0]);
	// 		$new_dir = implode('/', $dir_arr); //重新组合成一个文件夹字符串
	// 		$this->directory($new_dir); //试图创建父目录
	// 	}
	// }

	//主动判断是否HTTPS
	private function isHTTPS(){
		if (!isset($_SERVER)) return FALSE;
		if (!isset($_SERVER['HTTPS'])) return FALSE;
		if ($_SERVER['HTTPS'] === 1) {  //Apache
			return TRUE;
		} elseif ($_SERVER['HTTPS'] === 'on') { //IIS
			return TRUE;
		} elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
			return TRUE;
		}
		return FALSE;
	}

	// private function _OssUpload($local_path){
	// 	$_oss = C('OSS');
	// }
	//腾讯云OSS
	private function TencentOss($local_path){
		$_oss = C('OSS');

		require "./Library/TencentCos/cos-php-sdk-v5/vendor/autoload.php";

		$cosClient = new \Qcloud\Cos\Client(
			array(
				'region'		=> $_oss['REGION'],
				'schema'		=> 'https', //协议头部，默认为http
				'credentials'	=> array(
					'secretId'		=> $_oss['KEY_ID'],
					'secretKey'		=> $_oss['KEY_SECRET'],
				)
			)
		);

		try {
			$result = $cosClient->upload(
				$bucket = $_oss['BUCKET'], //格式：BucketName-APPID
				$key = $local_path,
				$body = fopen( '.' . $local_path, 'rb')
			);

			// 请求成功
			// dump( $result );
			return $result;
		} catch (\Exception $e) {
			// 请求失败
			// dump( $e );
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'服务器备份成功，OSS上传失败' . $e) );
		}
	}
}