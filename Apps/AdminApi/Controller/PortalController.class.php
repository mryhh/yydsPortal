<?php
/**
 *
 * UserController.class.php
 *
 * 服务后台接口模块 - 通用表格
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

// class PortalController extends Controller {
class PortalController extends CommController {
	//列表
	public function index(){
		$data = $this->_checkParam( array('table_name'=>'') );
$_time[] = microtime();
		$search_list = I('post.search_list');
		$search_page_info = I('post.search_page_info');
		$data_list = I('post.data_list');

		$DataBase = D(DBTYPE);

		// $dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value from dict_columns where in_table='Y' and isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $data['table_name'] . "')");
		// $dict_columns = D('Dict')->columns("where dc.in_table='Y' and dc.isactive='Y' and dc.dict_tables_id = (select id from dict_tables where table_name='" . $data['table_name'] . "' and dc.isactive='Y')");
		$dict_columns = D('Dict')->columns("where t.table_name='" . $data['table_name'] . "'");
$_time[] = microtime();

		//创建没有配置 却要 默认出现的 字段
		$_order_by_child = "";
		$_order_by = "";
		foreach ($dict_columns as $key => $value) {
			$dict_columns[$key]['columns_name'] = strtolower($dict_columns[$key]['columns_name']);

			if( $dict_columns[$key]['id'] == '' || is_null($dict_columns[$key]['id']) || $dict_columns[$key]['in_table'] != 'Y'){
				if($dict_columns[$key]['column_name'] == 'id'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'ID', 'columns_name'=>'id', 'type'=>'text', 'type_value'=>'', 'data_type'=>'NUMBER', 'data_length'=>'8');
				}else if($dict_columns[$key]['column_name'] == 'creationdate'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'创建时间', 'columns_name'=>'creationdate', 'type'=>'text', 'type_value'=>'', 'data_type'=>'DATE', 'data_length'=>'4');
				}else if($dict_columns[$key]['column_name'] == 'modifieddate'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'编辑时间', 'columns_name'=>'modifieddate', 'type'=>'text', 'type_value'=>'', 'data_type'=>'DATE', 'data_length'=>'4');
				}else if($dict_columns[$key]['column_name'] == 'isactive'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'可用', 'columns_name'=>'isactive', 'type'=>'radio', 'type_value'=>'', 'data_type'=>'CHAR', 'data_length'=>'1');
				}else{
					unset($dict_columns[$key]);
				}
			}

			if($dict_columns[$key]['column_name'] == 'id'){
				$_order_by_child = " order by id desc";
				$_order_by = "order by " . $data['table_name'] . ".id desc";
			}

			if( $dict_columns[$key]['type'] == 'select' && $dict_columns[$key]['type_value'] != '' ){
				$_type_value = json_decode($dict_columns[$key]['type_value'], true);
				foreach ($_type_value as $keyt => $valuet) {
					$dict_columns[$key]['type_value_arr'][$valuet['select_option_value']] = $valuet['select_option_title'];
				}
			}
			
		}
		// if($dict_columns[0]['table_type'] == 'TABLE' || $dict_columns[0]['table_type'] == 'table'){
		// 使用strpos 兼容mysql
		// if(strpos($dict_columns[0]['table_type'], 'table') !== false || strpos($dict_columns[0]['table_type'], 'TABLE') !== false){
		// 	$_order_by_child = " order by id desc";
		// 	$_order_by = "order by " . $data['table_name'] . ".id desc";
		// }else{
		// 	$_order_by_child = "";
		// 	$_order_by = "";
		// }
		//创建结束
$_time[] = microtime();

		foreach ($dict_columns as $key => $value) {
			$value['source_info'] = '';
			$dict_columns_key[$value['columns_name']] = $value;

			if( strtolower($value['data_type']) == 'date' && DBTYPE == 'Oracle'){
				$value['columns_name'] = "to_char(" . $value['columns_name'] . ", 'YYYY-MM-DD HH24:MI:SS') as " . $value['columns_name'];
			}
			if( $value['type'] == 'singleUpload' ){
				$_OSS = C('OSS');
				if(DBTYPE == 'Oracle'){
					$value['columns_name'] = "'" . $_OSS['LOCAL'] . "' || " . $value['columns_name'] . " as " . $value['columns_name'];
				}else if(DBTYPE == 'Mysql'){
					$value['columns_name'] = "concat('" . $_OSS['LOCAL'] . "', " . $value['columns_name'] . ") as " . $value['columns_name'];
				}
			}
			
			$cols_str .= ', ' . $value['columns_name'];
		}
$_time[] = microtime();

		//权限控制
		$data_authority = $this->_c_data_authority;

		//列表类 外键方法
		$index_source = D("Portal")->index_source($data['table_name'], $data_authority, $dict_columns_key);
		$_join = $index_source['_join'];
		$all_source_joins = $index_source['all_source_joins'];
		$_join_cols_str = $index_source['_join_cols_str'];
		$dict_columns_key = $index_source['dict_columns_key'];
		$cols_source_list = $index_source['cols_source_list'];
		$_join_where = $index_source['_join_where'];
		// array('_join'=>$_join, 'all_source_joins'=>$all_source_joins, '_join_cols_str'=>$_join_cols_str, 'dict_columns_key'=>$dict_columns_key);
		
$_time[] = microtime();

		if($data_list == 'Y'){
			$where = '';

			if( $search_list == '' ){
				$search_list = array();
				if( !isset($search_list['isactive']) || $search_list['isactive'] == '' ){
					$search_list['isactive'] = 'Y';
				}
			}
			if( $search_page_info == '' ){
				$search_page_info = array();
				if( !isset($search_page_info['page_count']) || $search_page_info['page_count'] == '' ){
					$search_page_info['page_count'] = '100';
				}
			}

			foreach ($search_list as $key => $value) {
				if( isset($dict_columns_key[strtolower($key)]) && $value != ''){
					if( strpos($value, ';') === false ){
						if( strpos($value, '=') === 0 ){
							$where .= " and " . $key . " = '" . ltrim($value, '=') . "'";
						}else{
							$where .= " and " . $key . " like '%" . $value . "%'";
						}
					}else{
						if( strpos($value, '=') === 0 ){
							$value = str_replace(';', "','", ltrim($value, '='));
							$value = rtrim($value, "','");
							$where .= " and " . $key . " in ('" . $value . "')";
						}else{
							$value = rtrim($value, ';');
							$where .= " and (" . $key . " like '%" . str_replace(";", "%' or " . $key . " like '%", $value) . "%')";
						}
					}
				}
			}

			//权限
			if( isset($data_authority[$data['table_name']]) && !empty($data_authority[$data['table_name']]) ){
				foreach ($data_authority[$data['table_name']] as $key => $value) {
					$where .= " and " . $key . " " . $value['where'];
				}
			}

			$where_count = $where;
			// dump($where);die;
			if($where == '' || $where == " and isactive = 'Y'"){
				$where .= " and id>(select max(id)-" . $search_page_info['page_count'] * 2 . " from " . $data['table_name'] . ")";
			}

			//原始数据
			$sql = "
				select " . ltrim($cols_str, ',') . " 
				from " . $data['table_name'] . " 
				where 1=1 " . $where . $_order_by_child . "";
$_time[] = microtime();

			//构建limit
			if(DBTYPE == 'Oracle'){
				$sql = "
	select * 
	from (
		select * 
		from (
			select " . $data['table_name'] . "_rnt" . ".*, rownum
			from (" . $sql . "
				) " . $data['table_name'] . "_rnt" . "
			)
		where rownum <= " . $search_page_info['page_count'] . "
		)
	where rownum>0";
			}else if(DBTYPE == 'Mysql'){
				$sql .= " limit 0, " . $search_page_info['page_count'];
			}

			//构建join
			$sql = "
select " . $data['table_name'] . ".*" . $_join_cols_str . " 
from (" . $sql . "
) " . $data['table_name'] . "
" . $_join . "
where 1=1 " . $_join_where . "
" . $_order_by;

			$list = $DataBase->selectDb($sql);

			$sql_count = "
	select count(1) as count 
	from " . $data['table_name'] . " 
	where 1=1 " . $where_count . "";

			$count_res = $DataBase->selectDb($sql_count);
			$count = $count_res[0]['count'];
		}else{
			$list = array();
			$count = 0;
		}

		if($list['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$list['message'], 'data'=>array('res'=>$list, ),) );
		}else if( empty($list) ){
			// $this->ajaxReturn( array('code'=>'-1', 'message'=>'查无数据', 'data'=>array('res'=>$list, ),) );
		}else{

		}
$_time[] = microtime();

		// $this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('dict_columns'=>$dict_columns, 'list'=>$list, 'sql'=>"select id, isactive" . $cols_str . " from " . $data['table_name']), ) );
		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('dict_columns'=>$dict_columns_key, 'list'=>$list, 'count'=>$count, 'source_list'=>$cols_source_list, 'search_list'=>$search_list, 'all_source_joins'=>$all_source_joins ), 'sql'=>$sql, 'sql_count'=>$sql_count, 'time'=>$_time) );
	}

	public function edit(){
		$data = $this->_checkParam( array('table_name'=>'', 'id'=>'') );

		$DataBase = D(DBTYPE);

		$dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value from dict_columns where in_table='Y' and isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $data['table_name'] . "')");

		$cols_arr = array('id'=>'', 'isactive'=>'');
		foreach ($dict_columns as $key => $value) {
			if($value['type'] == 'button'){

			}else{
				$cols_arr[$value['columns_name']] = '';
			}
		}

		$data = $this->_checkParam($cols_arr);

		$id = $data['id'];
		unset($data['id']);

		if($id == 'new'){
			$data['id'] = "get_sequences('" . $data['table_name'] . "')";
			$res = $DataBase->updateDb("insert into " . $data['table_name'] . " " . $DataBase->arr_str_add($data, 'Y'));
		}else{
			$res = $DataBase->updateDb("update " . $data['table_name'] . " set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
		}

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, ),) );
	}

	//详情
	public function info($table_name=''){
		$params = $this->_checkParam( array('table_name'=>'') );

		$params['id'] = I('post.id');
		$params['event'] = I('post.event');

		//权限控制
		$data_authority = $this->_c_data_authority;
		// dump($data_authority);die;

		$rdata = D("Portal")->info($params, true, true, $data_authority);
		
		$this->ajaxReturn($rdata);
	}

	//编辑详情
	public function editInfo(){
		$data = $this->_checkParam( array('table_name'=>'', 'cols'=>'') );

		$table_name = $data['table_name'];
		unset($data['table_name']);

		$DataBase = D(DBTYPE);

		//本表有哪些字段
		$dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value from dict_columns where isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $table_name . "')");

		//button类型的字段不进行编辑
		$cols_arr = array('id'=>'', 'isactive'=>'');
		foreach ($dict_columns as $key => $value) {
			if($value['type'] == 'button'){

			}else{
				$cols_arr[$value['columns_name']] = '';
			}
		}
		
		// $data = $this->_checkParam($cols_arr, 'post', true);
		//找到提交的对应数据
		foreach ($cols_arr as $key => $value) {
			if( isset($data['cols'][$key]) ){
				$data[$key] = ltrim(rtrim(htmlspecialchars_decode($data['cols'][$key]), ' '), ' ');
			}
		}
		unset($data['cols']);

		$id = $data['id'];
		unset($data['id']);
		$data_sync = $data;

		//好像和上面查重复了，有待优化
		$cols_list = D('Dict')->columns("where t.column_name not in ('creationdate', 'modifieddate', 'ownerid', 'modifierid', 'CREATIONDATE', 'MODIFIEDDATE', 'OWNERID', 'MODIFIERID') and t.table_name = '" . $table_name . "'");
		foreach ($cols_list as $key => $value) {
			$_cols_name = strtolower($value['column_name']);
			if( strtolower($value['data_type']) == 'date' ){
				if( $data[$_cols_name] != '' ){
					$_cols_date = $data[$_cols_name];
					if(DBTYPE == 'Oracle'){
						$data[$_cols_name] = "to_date('" . date('Y-m-d H:i:s', strtotime($data[$_cols_name])) . "', 'YYYY-MM-DD HH24:MI:SS')";
					}else if(DBTYPE == 'Mysql'){
						$data[$_cols_name] = "DATE_FORMAT('" . date('Y-m-d H:i:s', strtotime($data[$_cols_name])) . "', '%y-%m-%d %H:%i:%s')";
					}
					$data_sync[$_cols_name] = date('Y-m-d H:i:s', strtotime($_cols_date));
				}else{
					unset($data[$_cols_name]);
				}
			}
		}

		//新增或者编辑
		if( $id == 'new' || strpos($id, 'copy') !== false ){
			//mysql的id在插入之后获取
			if(DBTYPE == 'Oracle'){
				$id_res = $DataBase->selectDb("select get_sequences('" . $table_name . "') as id from dual");
				$id = $data['id'] = $id_res[0]['id'];
			}

			if( strpos($id, 'copy') !== false ){
				$dict_columns = D('Dict')->columns("where t.table_name='" . $table_name . "'");
				$copy_info = $DataBase->selectDb('select ' . join_value(',', $dict_columns, 'column_name') . ' from ' . $table_name . ' where id = ' . str_replace('copy', '', $id));
				if( empty($copy_info) ){
					$this->ajaxReturn( array('code'=>'-1', 'message'=>'未找到源数据', 'data'=>array('copy_info'=>$copy_info, ),) );
				}
				foreach ($copy_info[0] as $key => $value) {
					if( !isset($data[strtolower($key)]) ){
						$data[$key] = $value;
					}
				}
				unset($data['id']);
			}
			
			$res = $DataBase->updateDb("insert into " . $table_name . " " . $DataBase->arr_str_add($data, 'Y'));
			$type = 'insert';

			if(DBTYPE == 'Mysql' && $res['error'] == ''){
				$id = $data['id'] = $res['lastInsID'];
			}
		//单个更新
		}else if( strpos($id, ';') === false ){
			$res = $DataBase->updateDb("update " . $table_name . " set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
			$type = 'edit';
		//批量更新
		}else{
			foreach ($data as $key => $value) {
				if($value == ''){
					unset($data[$key]);
				}
			}
			$all_id = rtrim($id, ';');
			$all_id = str_replace(';', ',', $all_id);
			$res = $DataBase->updateDb("update " . $table_name . " set " . $DataBase->arr_str_upd($data, 'Y') . " where id in (" . $all_id . ")");
			$type = 'edit';
		}

		//编辑失败
		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		//执行动作定义
		$portal_active = D('Portal')->portalActive($table_name, $data_sync, $id, $type);
		if($portal_active['code'] < 1){
			$this->ajaxReturn( array('code'=>$portal_active['code'], 'message'=>$portal_active['message'], 'data'=>array('res'=>$res, 'portal_active'=>$portal_active),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, 'id'=>$id, 'portal_active'=>$portal_active),) );
	}

	public function insertAll(){
		$params = $this->_checkParam( array('table_name'=>'', 'source_table_name'=>'', 'source_table_data_id'=>'', 'list'=>'',) );

		$DataBase = D(DBTYPE);

		//外键
		$source_info = $DataBase->selectDb("select id,table_name,columns_name,upper(source_table_name) as source_table_name,source_columns_name 
from dict_columns_source 
where (upper(table_name)='" . strtoupper($params['table_name']) . "') 
and (upper(source_table_name)='" . strtoupper($params['source_table_name']) . "') 
and isactive='Y'");

		if( empty($source_info) ){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'未找到对应外键关系！') );
		}

		$find_source_cols = '';
		$insert_cols = array();
		foreach ($source_info as $key => $value) {
			$find_source_cols .= $value['source_columns_name'] . ',';

			$_insert_cols = explode(' ', $value['columns_name']);
			$insert_cols[$_insert_cols[0]] = $value['source_columns_name'];
		}

		$source_data_list = $DataBase->selectDb("select " . rtrim($find_source_cols, ',') . " from " . $params['source_table_name'] . " where isactive='Y' and id=" . $params['source_table_data_id'] );
		$source_data_info = $source_data_list[0];

		$list = $params['list'];

		foreach ($list as $key => $value) {
			foreach ($insert_cols as $keyt => $valuet) {
				$insert_list[$key] = $value;
				$insert_list[$key][$keyt] = $source_data_info[$valuet];
				$insert_list[$key]['id'] = "get_sequences('" . $params['table_name'] . "')";
			}
		}

		$insert_sql = $DataBase->arrs_str_add($insert_list, 'Y', $params['table_name']);

		$res = $DataBase->updateDb($insert_sql);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('list'=>$list, 'insert_list'=>$insert_list, 'sql'=>$insert_sql, 'res'=>$res, ), ) );
	}
}
