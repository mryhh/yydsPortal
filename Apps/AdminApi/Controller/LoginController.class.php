<?php
/**
 *
 * LoginController.class.php
 *
 * 后台接口模块 - 登录控制器
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class LoginController extends CommsController {
	//后台接口登录
	public function login(){
		$data = $this->_checkParam( array('username'=>'', 'password'=>'',) );

		$DataBase = D(DBTYPE);
		$list = $DataBase->selectDb("select id,isactive,username,password,nickname from admin_user where username = '" . $data['username'] . "'");

		if( !empty($list) && isset($list[0]['id']) ){
			$user = $list[0];
			//校验密码
			if( $user['password'] != md5($data['password']) ){
				$this->ajaxReturn( array('code'=>'-1', 'message'=>'密码错误！', 'data'=>array(),) );
			//校验账户状态
			}else if( $user['isactive'] != 'Y' ){
				$this->ajaxReturn( array('code'=>'-1', 'message'=>'账户不存在或不可用！', 'data'=>array(),) );
			}else{
				//缓存登录信息
				unset($user['password']);
				session('user', $user);
				$this->ajaxReturn( 
					array(
						'code'		=> '1', 
						'message'	=> '登陆成功', 
						'data'		=> array(
							// 'url'		=> session('url'),
						),
					) 
				);
			}
		}else{
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'账户不存在或不可用！', 'data'=>array(),) );
		}

		$this->ajaxReturn( array('code'=>'-1', 'message'=>'异常', 'data'=>array(),) );
	}

	//后台接口登出
	public function logOut(){
		session(null);
		$this->ajaxReturn( array('code'=>'1', 'message'=>'退出成功', 'data'=>array(), ) );
	}

	//修改密码
	public function rePassword(){
		$datas = $this->_checkParam( array('password'=>'',) );

		$user = session('user');

		if( $user && !empty($user) && isset($user['id']) && isset($user['username']) ){
			// $data['password'] = md5(I('post.password'));
			$data['password'] = md5($datas['password']);
			$DataBase = D(DBTYPE);
			$res = $DataBase->updateDb("update admin_user set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $user['id']);

			if($res['error'] != ''){
				$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
			}

			$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('res'=>$res, ),) );
		}else{
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'登录状态异常！') );
		}
	}
}