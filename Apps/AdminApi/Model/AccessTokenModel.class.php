<?php
/**
 *
 * AccessTokenModel.class.php
 *
 * 后台接口模块 - 获取app_info的model
 *
 * Mr.yh @ 2020.10.21
 * 
 */
 
namespace AdminApi\Model;

class AccessTokenModel{
	public function getAccessToken($param='', $value='', $type=''){
		if($type == ''){
			$type = 'WxProgram';
		}
		//获取access_token
		$result = curls("post", CORE_LOCAL . "/appInfo/access_token", urldecode(json_encode(array('company_id'=>CORE_COMPANY_ID, 'secret'=>CORE_SECRET, 'type'=>$type))));
		return json_decode($result, true);
		// //获取access_token
		// $result = curls("post", CORE_LOCAL . "/appInfo/access_token", urldecode(json_encode(array('company_id'=>CORE_COMPANY_ID, 'secret'=>CORE_SECRET,))));
		// return json_decode($result, true);
	}
}