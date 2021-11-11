<?php
/**
 *
 * IndexController.class.php
 *
 * 后台修改 配置、初始化数据库等
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class SetConfigController extends CommsController {
	//config.php   配置初始化页面
	public function index(){
		$_config = C();

		$info['ADMIN_NAME'] = array(
			'title'			=> '后台名称', 
			'value'			=> $_config['ADMIN_NAME'],
		);
		$info['COMPANY_NAME'] = array(
			'title'			=> '企业名称', 
			'value'			=> $_config['COMPANY_NAME'],
		);
		$info['DBTYPE'] = array(
			'title'			=> '数据库类型', 
			'value'			=> DBTYPE,
			'options'		=> array(
				array('id' => 'Mysql', 'title' => 'Mysql'),
				array('id' => 'Oracle', 'title' => 'Oracle'),
			),
		);
		$info['DEFAULT_DB'] = array(
			'title'			=> '数据库配置', 
			'value'			=> '',
			'list'			=> array(
				'DB_HOST'	=> array(
					'title'			=>	'数据库地址',
					'value'			=>	$_config['DEFAULT_DB']['DB_HOST'],
				),
				'DB_NAME'	=> array(
					'title'			=>	'数据库名称（Oracle不填）',
					'value'			=>	$_config['DEFAULT_DB']['DB_NAME'],
				),
				'DB_USER'	=> array(
					'title'			=>	'数据库用户名',
					'value'			=>	$_config['DEFAULT_DB']['DB_USER'],
				),
				'DB_PWD'	=> array(
					'title'			=>	'数据库密码',
					'value'			=>	$_config['DEFAULT_DB']['DB_PWD'],
				),
			),
		);
		$info['OSS'] = array(
			'title'			=> 'OSS云存储配置', 
			'value'			=> '',
			'list'			=> array(
				'TYPE'			=> array(
					'title'			=>	'OSS类型',
					'value'			=>	$_config['OSS']['TYPE'],
					'options'		=> array(
						array('id' => 'localhost', 'title' => '本地'),
						array('id' => 'TencentOSS', 'title' => '腾讯云OSS'),
					),
				),
				'LOCAL'			=> array(
					'title'			=>	'地址',
					'value'			=>	$_config['OSS']['LOCAL'],
				),
				'KEY_ID'		=> array(
					'title'			=>	'KEY',
					'value'			=>	$_config['OSS']['KEY_ID'],
				),
				'KEY_SECRET'	=> array(
					'title'			=>	'SECRET',
					'value'			=>	$_config['OSS']['KEY_SECRET'],
				),
				'BUCKET'		=> array(
					'title'			=>	'桶名称',
					'value'			=>	$_config['OSS']['BUCKET'],
				),
				'ENDPOINT'		=> array(
					'title'			=>	'访问域名',
					'value'			=>	$_config['OSS']['ENDPOINT'],
				),
				'REGION'		=> array(
					'title'			=>	'地域名称',
					'value'			=>	$_config['OSS']['REGION'],
				),
			),
		);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('info'=>$info), ) );
	}

	//config.php   配置初始化操作
	public function edit(){
		$params = $this->_checkParam( array('info'=>'') );
		$info = $params['info'];

		$filename = 'Apps/Common/Conf/config.php';

		if ( !file_exists($filename ) ){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'配置文件异常') );
		}

		$handle = fopen($filename , "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		//读取结束

		//先备份
		$_dir = 'Apps/Common/Conf';
		if (!file_exists($_dir)){
			mkdir ($_dir, 0777, true);
		}
		$handle = fopen($_dir . '/z-config-' . date('Ymd.H.i.s') . '.php' , "w");
		if($handle){
			$results = fwrite($handle, $contents);
		}else{
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'备份失败！程序已终止' . $_dir) );
		}
		fclose($handle);
		//备份结束

		$contents_arr = explode("'", $contents);
		$_config = C();

		foreach ($info as $key => $value) {
			if( is_array($value) ){
				foreach ($value as $keyt => $valuet) {
					foreach ($contents_arr as $keyf => $valuef) {
						if($valuef == $keyt && (strpos($contents_arr[$keyf+1], '=>') !== false && $contents_arr[$keyf+2] == $_config[$key][$keyt])){
							$contents_arr[$keyf+2] = trim($valuet);
						}
					}
				}
			}else{
					foreach ($contents_arr as $keyf => $valuef) {
						if($valuef == $key && (strpos($contents_arr[$keyf+1], '=>') !== false && $contents_arr[$keyf+2] == $_config[$key]) || (strpos($contents_arr[$keyf+1], ',') !== false && strpos($contents_arr[$keyf-1], 'define(') !== false)){
							$contents_arr[$keyf+2] = trim($value);
						}
					}
			}
		}

		$write_content = '';
		foreach ($contents_arr as $key => $value) {
			$write_content .= $value . "'";
		}
		$write_content = rtrim($write_content, "'");

		$handle = fopen($filename , "w");
		if($handle){
			$results = fwrite($handle, $write_content);
		}else{
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'写入失败，linux操作系统请修改 /代码根目录/Apps/Common/Conf/config.php 的权限为0777') );
		}
		fclose($handle);
		//配置文件写入结束

		//检测数据库 的 系统基本表与方法
		$DataBase = D(DBTYPE);
		$objects = D('SetConfig')->initObjects();
		$objects_str = rtrim(join_value("','", $objects, 'object_name'), ",'");
		$object_num = D('Dict')->objectCount($objects_str);

		if(!$object_num){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'数据库类型设置异常，请重新选择', ) );
		}else if( $object_num[0]['counts'] >= count($objects) ){
			$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('write_res'=>$results, 'object_name'=>'' ), ) );
		}else{
			$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('write_res'=>$results, 'object_name'=>$objects[0]['object_name'] ), ) );
		}
	}

	//创建 系统 对象（functions, tables）
	public function initObject(){
		$params = $this->_checkParam( array('object_name'=>'') );

		$objects = D('SetConfig')->initObjects();

		$DataBase = D(DBTYPE);

		$res_message = '异常，未找到配置项';
		$object_name = '';

		foreach ($objects as $key => $value) {
			//找到了对应配置项
			if($value['object_name'] == $params['object_name']){
				//如果不是最后一个配置项  则继续传参执行
				if( isset($objects[$key+1]) && is_array($objects[$key+1]) ){
					$object_name = $objects[$key+1]['object_name'];
				}

				//参数对应的配置项对应的数据库对象在数据库中是否找到
				$finished = D('Dict')->objectCount($params['object_name']);
				if(!$finished){
					$res_message = '数据库异常，请确认连接的是Oracle或Mysql数据库';
				//不存在接收参数的数据库对象
				}else if( $finished[0]['counts'] < 1 ){
					if(DBTYPE == 'Mysql'){
						if($value['object_name'] == 'GET_SEQUENCES'){
							$res_message = 'Mysql数据库，跳过sequence创建...';
							break;
						}
						//懒得改  不想构建建表语句了   直接修正Oracle为mysql语句吧...
						$value = $DataBase->oracle_mysql_create_table($value);
					}

					foreach ($value['object_value'] as $keyt => $valuet) {
						$res = $DataBase->updateDb($valuet);
						if($keyt < 1){
							if($res['executed']){
								$res_message = $value['object_type'] . " - " . $value['object_name'] . '加载完成，next...';
							}else{
								$res_message = $value['object_type'] . " - " . $value['object_name'] . 'sql执行失败' . $res['error'] . '，next...';
							}
						}else{
							$res_message .= '.';
						}
					}
				//已存在
				}else{
					$res_message = $value['object_type'] . " - " . $value['object_name'] . '已存在，next...';
				}

			}
		}

		$this->ajaxReturn( 
			array(
				'code'		=> '1',
				'message'	=> 'ok',
				'data'		=> array(
					'res_message'	=> $res_message,
					'object_name'	=> $object_name,
					'finished'		=> $finished,
				),
			)
		);
	}

	//初始化密码
	public function initPassword(){
		$params = $this->_checkParam( array('password'=>'') );

		$DataBase = D(DBTYPE);

		$user = $DataBase->selectDb("select count(1) as counts from admin_user where username='root'");

		if( $user[0]['counts'] > 0 ){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'root账户已存在不可再次初始化，如需重置密码，请登录系统内设置！', ) );
		}else{
			$insert_data = array(
				'id'		=> "get_sequences('admin_user')",
				'username'	=> "root",
				'password'	=> md5($params['password']),
				'nickname'	=> '系统管理员',
				'isactive'	=> 'Y',
			);
			$res = $DataBase->updateDb("insert into admin_user " . $DataBase->arr_str_add($insert_data, 'Y'));
			$this->ajaxReturn( array('code'=>'1', 'message'=>'设置成功，前往登录', ) );
		}
	}

	public function initInsert(){
		$params = $this->_checkParam( array('table_name'=>''), 'post', true);

		$DataBase = D(DBTYPE);

		$init_insert_data = D('SetConfig')->initInsertData();

		$res_message = '数据载入已结束...';
		$next_table_name = '';

		foreach ($init_insert_data as $key => $value) {
			if($value['table_name'] == $params['table_name']){
				$_count = $DataBase->selectDb("select count(1) as counts from " . $value['table_name'] . " where isactive='Y'");
				if( $_count[0]['counts'] < count($value['list']) ){
					$result = array();
					foreach ($value['list'] as $keyt => $valuet) {
						$insert_data = $up_data = $valuet;
						$insert_data['id'] = "get_sequences('" . $value['table_name'] . "')";

// 						$using_str = '';
// 						$on_str = '';
// 						foreach ($up_data as $keytt => $valuett) {
// 							if( strpos($valuett, "('") !== false && strpos($valuett, "')") !== false ){
// 								$using_str = $using_str . $valuett . " as " . $keytt . ", ";
// 							}else if( strpos($valuett, "(select") !== false && strpos($valuett, ")") !== false ){
// 								$using_str = $using_str . $valuett . " as " . $keytt . ", ";
// 							}else if($valuett == 'sysdate'){
// 								$using_str = $using_str . $valuett . " as " . $keytt . ", ";
// 							}else{
// 								$using_str = $using_str . "'" . $valuett . "' as " . $keytt . ", ";
// 							}
// 							$on_str .= 'a.' . $keytt . '=' . 'b.' . $keytt . ' and ';
// 							unset($up_data[$keytt]);
// 						}

// 						$result[] = $DataBase->updateDb("
// merge into " . $value['table_name'] . " a 
// using (select " . rtrim($using_str, ', ') . " from dual) b 
// on (" . rtrim($on_str, ' and ') . ") 
// when matched then 
// update set " . $DataBase->arr_str_upd($up_data, 'Y') . "
// when not matched then 
// insert " . $DataBase->arr_str_add($insert_data, 'Y')
// 						);
						$_where = $DataBase->arr_str_where($up_data, 'Y');
						$_his = $DataBase->selectDb("select id from " . $value['table_name'] . " where " . $_where);

						// if( $_his[0]['counts'] > 0 ){
						if( isset($_his[0]['id']) ){
							$result[] = $DataBase->updateDb("update " . $value['table_name'] . " set " . $DataBase->arr_str_upd($up_data, 'Y') . " where id=" . $_his[0]['id']);
						}else{
							$result[] = $DataBase->updateDb("insert into " . $value['table_name'] . $DataBase->arr_str_add($insert_data, 'Y'));
						}
					}
					$res_message = '载入数据' . count($result) . '行...';
				}else{
					$res_message = '已存在数据' . $_count[0]['counts'] . '行,跳过...';
				}

				$table_info = $DataBase->selectDb("select title from dict_tables where table_name='" . $value['table_name'] . "'");
				$res_message =  $table_info[0]['title'] . '(' . $value['table_name'] . ')数据已加载，' . $res_message;

				if( $key+1 < count($init_insert_data) ){
					$next_table_name = $init_insert_data[$key+1]['table_name'];
				}
			}
		}

		$this->ajaxReturn( 
			array(
				'code'		=> '1',
				'message'	=> 'ok',
				'data'		=> array(
					'res_message'	=> $res_message,
					'table_name'	=> $next_table_name,
					'res'			=> $result,
				),
			)
		);
	}
}