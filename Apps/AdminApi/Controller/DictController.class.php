<?php
/**
 *
 * DictController.class.php
 *
 * 后台接口模块 - 数据字典控制器
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class DictController extends CommController {
	public function tables(){
		$data = $this->_checkParam( array('keywords'=>''), 'post', true );

		$where = 'where 1=1';
		foreach ($data['keywords'] as $key => $value) {
			if($key != '' && $value != ''){
				$where .= " and tt." . $key . " like '%" . strtoupper($value) . "%'";
				// $where .= " and (t." . $key . " like '%" . $value . "%' or t." . $key . " like '%" . strtoupper($value) . "%' or t." . $key . " like '%" . strtolower($value) . "%')";
			}
		}

		$list = D('Dict')->tables($where);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>$list, 'where'=>$where) );
	}

	public function columns(){
		$data = $this->_checkParam( array('table_name'=>'') );

		$table_name = I('post.table_name');

		$list = D('Dict')->columns("where t.column_name not in ('creationdate', 'modifieddate', 'ownerid', 'modifierid', 'CREATIONDATE', 'MODIFIEDDATE', 'OWNERID', 'MODIFIERID') and t.table_name = '" . $table_name . "'");

		foreach ($list as $key => $value) {
			if( $value['type_value'] && $value['type_value'] != '' ){
				$list[$key]['type_value'] = htmlspecialchars($value['type_value']);
				$list[$key]['original_type_value'] = $value['type_value'];
			}
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>$list) );
	}

	public function editTable(){
		$data = $this->_checkParam( array('id'=>'', 'title'=>'', 'table_name'=>'') );

		$id = $data['id'];
		unset($data['id']);

		//非必填项
		$data['notes'] = I('post.notes');
		$isactive = I('post.isactive');
		if($isactive != ''){
			$data['isactive'] = $isactive;
		}
		$is_menu = I('post.is_menu');
		if($is_menu != ''){
			$data['is_menu'] = $is_menu;
		}

		$DataBase = D(DBTYPE);
		if($id == 'new'){
			$data['id'] = "get_sequences('dict_tables')";
			$res = $DataBase->updateDb("insert into dict_tables " . $DataBase->arr_str_add($data, 'Y'));
		}else{
			$res = $DataBase->updateDb("update dict_tables set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
		}

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, ),) );
	}

	public function editColumns(){
		$data = $this->_checkParam( array('id'=>'', 'title'=>'', 'type'=>'', 'column_name'=>'', 'table_name'=>'') );

		$id = $data['id'];
		unset($data['id']);

		$data['columns_name'] = $data['column_name'];
		unset($data['column_name']);

		//非必填项
		$data['notes'] = I('post.notes');
		$isactive = I('post.isactive');
		if($isactive != ''){
			$data['isactive'] = $isactive;
		}
		$in_table = I('post.in_table');
		if($in_table != ''){
			$data['in_table'] = $in_table;
		}
		// $type_value = I('post.type_value');
		$type_value = I('post.original_type_value');
		if($type_value != ''){
			$data['type_value'] = str_replace("'", "''", htmlspecialchars_decode( $type_value ) );
		}
		$col_seq = I('post.seq');
		if($col_seq != ''){
			$data['seq'] = $col_seq;
		}

		$DataBase = D(DBTYPE);
		if($id == 'new'){
			$data_dict_tables_id = $DataBase->selectDb("select id from dict_tables where table_name='" . $data['table_name'] . "'");

			if($data_dict_tables_id[0]['id']){
				$data['dict_tables_id'] = $data_dict_tables_id[0]['id'];
				
				$data['id'] = "get_sequences('dict_columns')";
				$res = $DataBase->updateDb("insert into dict_columns " . $DataBase->arr_str_add($data, 'Y'));
			}else{
				$this->ajaxReturn( array('code'=>'-1', 'message'=>'请先维护' . $data['table_name'] . "表", 'data'=>array(),) );
			}
		}else{
			$res = $DataBase->updateDb("update dict_columns set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
		}

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, 'data'=>$data, ),) );
	}
}