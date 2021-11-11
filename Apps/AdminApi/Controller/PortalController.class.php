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
		// $is_columns_id = 'N';
		$_order_by_child = "";
		$_order_by = "";
		foreach ($dict_columns as $key => $value) {
			$dict_columns[$key]['columns_name'] = strtolower($dict_columns[$key]['columns_name']);

			if($dict_columns[$key]['id'] == ''){
				if($dict_columns[$key]['columns_name'] == 'id'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'可用', 'columns_name'=>'id', 'type'=>'text', 'type_value'=>'', 'data_type'=>'NUMBER', 'data_length'=>'8');
				}else if($dict_columns[$key]['columns_name'] == 'creationdate'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'可用', 'columns_name'=>'creationdate', 'type'=>'text', 'type_value'=>'', 'data_type'=>'DATE', 'data_length'=>'4');
				}else if($dict_columns[$key]['columns_name'] == 'modifieddate'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'可用', 'columns_name'=>'modifieddate', 'type'=>'text', 'type_value'=>'', 'data_type'=>'DATE', 'data_length'=>'4');
				}else if($dict_columns[$key]['columns_name'] == 'isactive'){
					$dict_columns[$key] = array('id'=>'', 'isactive'=>'Y', 'title'=>'可用', 'columns_name'=>'isactive', 'type'=>'radio', 'type_value'=>'', 'data_type'=>'CHAR', 'data_length'=>'1');
				}else{
					unset($dict_columns[$key]);
				}
			}

			if($dict_columns[$key]['columns_name'] == 'id'){
				$_order_by_child = " order by id desc";
				$_order_by = "order by " . $data['table_name'] . ".id desc";
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
				$value['columns_name'] = "'" . $_OSS['LOCAL'] . "' || " . $value['columns_name'] . " as " . $value['columns_name'];
			}
			
			$cols_str .= ', ' . $value['columns_name'];
		}
$_time[] = microtime();

		//权限控制
		$data_authority = $this->_c_data_authority;

		//外键
		$all_source_joins = array();

		$source_list = D('Dict')->colsSourceList($data['table_name'], " and t.isactive='Y' and dc1.data_type=dc2.data_type");
$_time[] = microtime();
		
		$_join = '';
		$_join_cols_str = '';
		$_join_where = '';
		if( !empty($source_list) ){
			//mysql系统表中表名是小写导致url中表名是小写，保险期间把表名转一下  但是后面是不是好像不用判断表名是否相同...
			$_table_name = strtoupper($data['table_name']);
			//循环所有的外键表构建所需的数据
			foreach ($source_list as $key => $value) {
				$columns_name_info = explode( ' ', trim($value['columns_name']) );
				$value['columns_name'] = strtolower($columns_name_info[0]);
				if( isset($dict_columns_key[strtolower($value['columns_name'])]) ){
					// $dict_columns_key[$value['columns_name']]['source_info'] = $value;
					if($value['table_name'] == $_table_name && $value['source_cols_list'] != '' && $value['source_data_type'] == $value['data_type']){
						$_join .= "
left join " . $value['source_table_name'] . " " . $value['source_table_name'] . "_" . $key . " on " . $value['source_table_name'] . "_" . $key . "." . $value['source_columns_name'] . "=" . $value['table_name'] . "." . $value['columns_name'];
						$_soure_cols_list = explode(';', $value['source_cols_list']);
						foreach ($_soure_cols_list as $keyt => $valuet) {
							$_source_source = explode('.', $valuet);
							if(count($_source_source) > 1){
								$_source_joins = D('Portal')->_source_table_tree($value['source_table_name'], $_source_source[0], 0, $value['source_table_name'] . "_" . $key, $data_authority);
								$all_source_joins[] = $_source_joins;
								$_join .= $_source_joins['join'];
								$_join_cols_str .= $_source_joins['select_cols'];
								// $value['source_cols_list_arr'] = array_merge($value['source_cols_list_arr'], $_source_joins['select_cols_list']);
								// $value['source_cols_list_arr'] = $_source_joins['select_cols_list'];
								foreach ($_source_joins['select_cols_list'] as $keytt => $valuett) {
									$value['source_cols_list_arr'][] = $valuett;
								}
								foreach ($_source_joins['where_list'] as $keytt => $valuett) {
									$_join_where .= $valuett;
								}
							}else{
								$_soure_cols_name = strtolower($value['source_table_name'] . "_" . $key) . "_" . $valuet;
								$_join_cols_str .= ',' . $value['source_table_name'] . "_" . $key . "." . $valuet . " as " . $_soure_cols_name;
								$value['source_cols_list_arr'][] = $_soure_cols_name;
							}
						}
					}
					$dict_columns_key[$value['columns_name']]['source_info'] = $value;
				}
			}
		}
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
							$where .= " and " . $key . " = '" . $value . "'";
						}else{
							$where .= " and " . $key . " like '%" . $value . "%'";
						}
					}else{
						$value = str_replace(';', "','", $value);
						$value = rtrim($value, "','");
						$where .= " and " . $key . " in ('" . $value . "')";
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
		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('dict_columns'=>$dict_columns_key, 'list'=>$list, 'count'=>$count, 'source_list'=>$source_list, 'search_list'=>$search_list, 'all_source_joins'=>$all_source_joins ), 'sql'=>$sql, 'sql_count'=>$sql_count, 'time'=>$_time) );
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
			if( strtolower($value['data_type']) == 'date' && $data[$_cols_name] != '' ){
				$_cols_date = $data[$_cols_name];
				$data[$_cols_name] = "to_date('" . date('Y-m-d H:i:s', strtotime($data[$_cols_name])) . "', 'YYYY-MM-DD HH24:MI:SS')";
				$data_sync[$_cols_name] = date('Y-m-d H:i:s', strtotime($_cols_date));
			}
		}

		//新增或者编辑
		if($id == 'new'){
			//mysql的id在插入之后获取
			if(DBTYPE == 'Oracle'){
				$id_res = $DataBase->selectDb("select get_sequences('" . $table_name . "') as id from dual");
				$id = $data['id'] = $id_res[0]['id'];
			}
			
			$res = $DataBase->updateDb("insert into " . $table_name . " " . $DataBase->arr_str_add($data, 'Y'));
			$type = 'insert';

			if(DBTYPE == 'Mysql' && $res['error'] == ''){
				$id = $data['id'] = $res['lastInsID'];
			}
		//单个更新
		}else if( $id === false ){
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
