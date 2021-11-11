<?php
/**
 *
 * OperateController.class.php
 *
 * 后台接口模块 - 后台操作管理控制器
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class UserAuthorityController extends CommController {
	//操作权限列表
	public function authority(){
		//必填项
		$params = $this->_checkParam( array('admin_user_group_id'=>'', ) );

		$DataBase = D(DBTYPE);

		$res = $DataBase->selectDb("
select " . $DataBase->nvl() . "(a.id, '') as id, a.admin_user_group_id, 
	   o.id as admin_operate_id, o.html_url, o.operate, o.title 
from admin_operate o
left join admin_user_authority a on a.admin_operate_id=o.id and a.isactive='Y' and a.admin_user_group_id=" . $params['admin_user_group_id'] . "
where o.isactive='Y'
order by o.id desc");

		//分组
		$obj = array();
		foreach ($res as $key => $value) {
			$_operate = getsingles($value['operate'], '/');

			if($_operate[0] == 'Portal' && $_operate[1] == 'table_name'){
				$value['data_authority'] = 'Y';
				$obj[$_operate[2]][$_operate[3]] = $value;
			}else{
				$obj[$_operate[0]][$_operate[1]] = $value;
				$value['data_authority'] = 'N';
			}
		}

		foreach ($obj as $key => $value) {
			if( count($value) > 1 ){
				$_same_str = same_str($value, 'title');
				// dump($_same_str);
				foreach ($_same_str as $keyt => $valuet) {
					$obj[$key][$keyt]['_same_title'] = join( mine_array_flip($valuet['same']) );
					$obj[$key][$keyt]['_different_title'] = join( mine_array_flip($valuet['different']) );
					// $obj[$key][$keyt]['same'] = $valuet['same'];
					// $obj[$key][$keyt]['different'] = $valuet['different'];
				}
			}
		}
		foreach ($obj as $key => $value) {
			asort($obj[$key]);
		}

		// $obj = ksort($obj);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('obj'=>$obj, 'res'=>$res, ), ) );
	}

	//保存操作权限
	public function editAuthority(){
		$params = $this->_checkParam( array('admin_user_group_id'=>'', 'list'=>''), 'post', false );

		$new_authority_list = $params['list'];

		$params = $this->_checkParam( array('admin_user_group_id'=>'', ) );

		$DataBase = D(DBTYPE);
		
		//已经存在的条码
		$authority_list = $DataBase->selectDb("select t.id, t.admin_user_group_id, t.admin_operate_id, t.isactive
from admin_user_authority t 
where t.admin_user_group_id='" . $params['admin_user_group_id'] . "'");
		
		//处理旧数组为二维对象 方便快速定位
		$old_authority_obj = array();
		foreach ($authority_list as $key => $value) {
			$old_authority_obj[$value['admin_operate_id']] = $value;
		}

		//
		$update_isactive_id = array(
			'Y'	=> array(),
			'N'	=> array(),
		);

		$insert_data = array();
		foreach ($new_authority_list as $key => $value) {
			//如果旧的存在
			if( isset($old_authority_obj[$value['admin_operate_id']]) ){
				//如果是不可用  先记录  等会更新可用状态
				if( $old_authority_obj[$value['admin_operate_id']]['isactive'] != 'Y' ){
					$update_isactive_id['Y'][] = $old_authority_obj[$value['admin_operate_id']]['id'];
				}
				//移除新旧两个 新的数组做新增（存在即不新增），旧的数组做作废（新的也有责不作废）
				unset($old_authority_obj[$value['admin_operate_id']]);
				unset($new_authority_list[$key]);
			}else{
				$insert_data[$key]['id'] = "get_sequences('admin_user_authority')";
				$insert_data[$key]['isactive'] = "Y";
				$insert_data[$key]['admin_user_group_id'] = $params['admin_user_group_id'];
				$insert_data[$key]['admin_operate_id'] = $value['admin_operate_id'];
			}
		}

		//循环剩余的旧对象 找出需要作废的id
		foreach ($old_authority_obj as $key => $value) {
			// foreach ($value as $keyt => $valuet) {
				// $update_isactive_id['N'][] = $valuet['id'];
			// }
			$update_isactive_id['N'][] = $value['id'];
		}

		if( !empty($insert_data) ){
			$res['insert_sql'] = $DataBase->arrs_str_add($insert_data, 'Y', 'admin_user_authority');
			$res['insert_sql_res'] = $DataBase->updateDb($res['insert_sql']);
		}
		if( !empty($update_isactive_id['Y']) ){
			$res['upd_data_Y'] = "update admin_user_authority set isactive='Y' where id in (" . join($update_isactive_id['Y'], ',') . ')';
			$res['upd_data_Y_res'] = $DataBase->updateDb($res['upd_data_Y']);
		}
		if( !empty($update_isactive_id['N']) ){
			$res['upd_data_N'] = "update admin_user_authority set isactive='N' where id in (" . join($update_isactive_id['N'], ',') . ')';
			$res['upd_data_N_res'] = $DataBase->updateDb($res['upd_data_N']);
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'保存成功', 'data'=>array('res'=>$res), ) );
	}


	//查询数据权限
	public function dataAuthority(){
		$params = $this->_checkParam( array('admin_user_group_id'=>'', 'admin_operate_id'=>'') );

		$DataBase = D(DBTYPE);

		$res = $DataBase->selectDb("
select a.id, a.admin_user_group_id, a.data_authority, 
	   o.id as admin_operate_id, o.html_url, o.operate, o.title 
from admin_operate o
left join admin_user_authority a on a.admin_operate_id=o.id and a.isactive='Y' and a.admin_user_group_id=" . $params['admin_user_group_id'] . "
where o.isactive='Y' and o.id=" . $params['admin_operate_id']);

		$info = $res[0];

		$_operate = getsingles($info['operate'], '/');

		if($_operate[0] == 'Portal' && $_operate[1] == 'table_name'){
			$info['_table_name'] = $_table_name = $_operate[2];
			$info['_type'] = $_type = $_operate[3];
		}

		$_Portal = D('Portal');
		if($_type == 'index'){
			$tree = $_Portal->_source_table_trees($_table_name);
		}

		if($_type == 'edit'){
			$tree = array();
		}

		// $_tree_obj = array();
		// foreach ($tree as $keyt => $valuet) {
		// 	$_tree_obj[$valuet['col']] = $valuet;
		// }

		$_table_info = $_Portal->info( array('table_name'=>$_table_name), true, false );
		$all_tree = $_table_info['data']['dict_columns'];
		$res_tree = array();
		foreach ($all_tree as $key => $valuet) {
			$valuet['table_name'] = $_table_name;
			$valuet['_table_title'] = $info['title'];
			if( isset($tree[$valuet['columns_name']]) ){
				$valuet['title'] = $valuet['title'] . '(' . $tree[$valuet['columns_name']][0]['_table_title'] . ')';
				$valuet['children'] = $tree[$valuet['columns_name']];
				$valuet['spread'] = true;
			}
			$res_tree[] = $valuet;
		}

		if($info['data_authority'] == ''){
			$info['data_authority'] = array();
		}else{
			$_data_authority = json_decode($info['data_authority'], true);
			unset($info['data_authority']);
			foreach ($_data_authority as $key => $value) {
				$data_authority[$value['tab']][$value['col']] = $value;
			}

			//反解多级树形数组
			$_reverse_tree = _reverse_tree($res_tree, 'children');
			foreach ($_reverse_tree as $key => $value) {
				if( isset($data_authority[$value['table_name']][$value['columns_name']]) ){
					$value['_mod'] = $data_authority[$value['table_name']][$value['columns_name']]['mod'];
					$value['_val'] = $data_authority[$value['table_name']][$value['columns_name']]['val'];
					$info['data_authority'][] = $value;
				}
			}
		}
		

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('info'=>$info, 'all_tree'=>$res_tree, ), ) );
	}

	

	//编辑数据权限
	public function editDataAuthority(){
		$params = $this->_checkParam( array('admin_user_group_id'=>'', 'admin_operate_id'=>'', 'sub_arr'=>'') );

		$obj = array();

		foreach ($params['sub_arr'] as $key => $value) {
			$value['where_value_mode'] = trim($value['where_value_mode']);
			// $_val = ' ';
			// foreach ($value['where_value'] as $keyt => $valuet) {
				// if($value['where_value_mode'] == 'in' || $value['where_value_mode'] == 'not in'){
				// 	$_val .= $value['where_value_mode'] . " ('" . rtrim( join_value("','", $value['where_value'], ''), "','" ) . "')";
				// }else if($value['where_value_mode'] == 'like'){
				// 	$_val .= $value['where_value_mode'] . " '%" . $value['where_value'][0] . "%'";
				// }else if($value['where_value_mode'] == 'eq'){
				// 	$_val .= "='" . $value['where_value'][0] . "'";
				// }else if($value['where_value_mode'] == 'neq'){
				// 	$_val .= "!='" . $value['where_value'][0] . "'";
				// }else if($value['where_value_mode'] == 'gt'){
				// 	$_val .= ">" . $value['where_value'][0];
				// }else if($value['where_value_mode'] == 'lt'){
				// 	$_val .= "<" . $value['where_value'][0];
				// }else{
				// 	$_val .= '';
				// }
			// }
			$obj[] = array(
				'tab'	=> $value['table_name'],
				'col'	=> $value['columns_name'],
				// 'val'	=> addcslashes($_val, "'"),
				'mod'	=> $value['where_value_mode'],
				'val'	=> $value['where_value'],
			);
		}

		$DataBase = D(DBTYPE);
		$up_data = array(
			'data_authority'	=> json_encode($obj),
		);
		$res = $DataBase->updateDb("update admin_user_authority set " . $DataBase->arr_str_upd($up_data, 'Y') . " where admin_user_group_id=" . $params['admin_user_group_id'] . " and admin_operate_id=" . $params['admin_operate_id']);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('obj'=>$obj, 'res'=>$res, ), ) );
	}
}