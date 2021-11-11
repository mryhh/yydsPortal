<?php
/**
 *
 * RestController.class.php
 *
 * 服务后台接口模块 - 通用表格对外查询接口
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class RestController extends Controller {
	protected function _checkParam($param, $type='post'){
		if($type == 'post'){
			$data = I('post.');
		}else if($type == 'curls'){
			$data = json_decode(file_get_contents("php://input"), true);
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
				$this->ajaxReturn( array('code'=>'-1', 'message'=>'参数错误，缺少' . $key, 'data'=>array(),) );
			}else{
				$rdata[$key] = $data[$key];
			}
		}

		//rest接口用户
		if( isset($data['user']) ){
			$user = $data['user'];
			session('user', $user);
		}

		return $rdata;
	}

	public function select(){
		$data = $this->_checkParam( array('table_name'=>'', 'cols'=>''), 'curls' );

		$table_name = $data['table_name'];
		
		$DataBase = D(DBTYPE);

		// 本表有哪些字段
		$dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value from dict_columns where isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $table_name . "')");

		// button类型的字段不进行编辑
		$cols_arr = array('id'=>'', 'isactive'=>'');
		foreach ($dict_columns as $key => $value) {
			if($value['type'] == 'button'){

			}else{
				$cols_arr[$value['columns_name']] = '';
			}
		}
		
		// $data = $this->_checkParam($cols_arr, 'post', true);
		// 找到提交的对应数据
		$where = ' where 1=1';
		foreach ($cols_arr as $key => $value) {
			if( isset($data['cols'][$key]) ){
				$where .= " and " . $key . "='" . ltrim(rtrim(htmlspecialchars_decode($data['cols'][$key]), ' '), ' ') . "'";
			}
		}
		// unset($data['cols']);

		$res = $DataBase->selectDb("select * from " . $table_name . $where);	

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>$res,) );
	}

	public function index(){
		$data = $this->_checkParam( array('table_name'=>'', 'cols'=>''), 'curls' );

		$table_name = $data['table_name'];
		unset($data['table_name']);

		$DataBase = D(DBTYPE);

		// 本表有哪些字段
		$dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value from dict_columns where isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $table_name . "')");

		// button类型的字段不进行编辑
		$cols_arr = array('id'=>'', 'isactive'=>'');
		foreach ($dict_columns as $key => $value) {
			if($value['type'] == 'button'){

			}else{
				$cols_arr[$value['columns_name']] = '';
			}
		}
		
		// $data = $this->_checkParam($cols_arr, 'post', true);
		// 找到提交的对应数据
		foreach ($cols_arr as $key => $value) {
			if( isset($data['cols'][$key]) ){
				$data[$key] = ltrim(rtrim(htmlspecialchars_decode($data['cols'][$key]), ' '), ' ');
			}else{
				$data[$key] = '';
			}
		}
		unset($data['cols']);

		$id = $data['id'];
		unset($data['id']);
		$data_sync = $data;

		// 好像和上面查重复了，有待优化
		$cols_list = D('Dict')->columns("where t.column_name not in ('creationdate', 'modifieddate', 'ownerid', 'modifierid', 'CREATIONDATE', 'MODIFIEDDATE', 'OWNERID', 'MODIFIERID') and t.table_name = '" . $table_name . "'");
		foreach ($cols_list as $key => $value) {
			$_cols_name = strtolower($value['column_name']);
			if( strtolower($value['data_type']) == 'date' && $data[$_cols_name] != '' ){
				$_cols_date = $data[$_cols_name];
				$data[$_cols_name] = "to_date('" . date('Y-m-d H:i:s', strtotime($data[$_cols_name])) . "', 'YYYY-MM-DD HH24:MI:SS')";
				$data_sync[$_cols_name] = date('Y-m-d H:i:s', strtotime($_cols_date));
			}
		}

		// 新增或者编辑
		if($id == 'new'){
			$id_res = $DataBase->selectDb("select get_sequences('" . $table_name . "') as id from dual");
			$id = $data['id'] = $id_res[0]['id'];
			
			$res = $DataBase->updateDb("insert into " . $table_name . " " . $DataBase->arr_str_add($data, 'Y'));
			$type = 'insert';
		}else{
			$res = $DataBase->updateDb("update " . $table_name . " set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
			$type = 'edit';
		}

		// 编辑失败
		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		// 执行动作定义
		$portal_active = D('Portal')->portalActive($table_name, $data_sync, $id, $type);
		if($portal_active['code'] < 1){
			$this->ajaxReturn( array('code'=>$portal_active['code'], 'message'=>$portal_active['message'], 'data'=>array('res'=>$res, 'portal_active'=>$portal_active),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, 'portal_active'=>$portal_active),) );
	}
}