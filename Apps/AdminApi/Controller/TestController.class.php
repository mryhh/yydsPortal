<?php
/**
 *
 * TestController.class.php
 *
 * 后台接口模块 测试
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class TestController extends CommController {
	public function index(){
		$this->TencentOss('/Public/Files/uploads/images/P_PRODUCT_IMG/1/6108f6d058061.jpg');
	}

	public function TencentOss($local_path){
		$_oss = C('OSS');

		// require "./Library/TencentCos/cos-php-sdk-v4/include.php";

		// $cosApi = new \qcloudcos\Cosapi( 
		// 	array(
		// 		'app_id'		=> '1253870195',
		// 		'secret_id'		=> $_oss['KEY_ID'],
		// 		'secret_key'	=> $_oss['KEY_SECRET'],
		// 		'region'		=> $_oss['REGION'],
		// 		'timeout'		=> 60,
		// 	)
		// );
// dump( fopen($local_path, 'rb') );die;
		// $result = $cosApi->upload($_oss['BUCKET'], $local_path, $local_path);
		// dump($result);die;

		// require "./Library/TencentCos/1.3.0/cos-sdk-v5.phar";
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
			// $result = $cosClient->putObject( 
			// 	array(
			// 		'Bucket'	=> $_oss['BUCKET'], //格式：BucketName-APPID
			// 		'Key'		=> $local_path,
			// 		'Body'		=> fopen($local_path, 'rb'),
			// 	)
			// );
			// 请求成功
			dump( $result );
		} catch (\Exception $e) {
			// 请求失败
			dump( $e );
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'服务器备份成功，OSS上传失败' . $e) );
		}
	}
}