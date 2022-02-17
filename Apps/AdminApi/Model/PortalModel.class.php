<?php
/**
 *
 * IndexController.class.php
 *
 * 后台接口模块 - 数据字典model
 *
 * Mr.yh @ 2020.10.21
 * 
 */
 
namespace AdminApi\Model;

class PortalModel{
	public function info($data=array(), $source=true, $source_reverse=true, $data_authority=''){
		$DataBase = D(DBTYPE);

		// $dict_columns = $DataBase->selectDb("select id,isactive,title,LOWER(columns_name) as columns_name,type,type_value,notes from dict_columns where isactive='Y' and dict_tables_id = (select id from dict_tables where table_name='" . $data['table_name'] . "')");
		$dict_columns = D('Dict')->columns("where dc.dict_tables_id = (select id from dict_tables where table_name='" . $data['table_name'] . "' and dc.isactive='Y')");

		$cols_str = "id, isactive";
		$dict_columns_key = array('id'=>array('title'=>'id', 'columns_name'=>'id'), 'isactive'=>array('title'=>'可用', 'columns_name'=>'isactive', 'type'=>'radio'), );
		foreach ($dict_columns as $key => $value) {
			$dict_columns[$key]['columns_name'] = $_cols_name = $value['columns_name'] = strtolower($value['columns_name']);
			if($value['data_type'] == 'DATE'){
				$cols_str .= ", to_char(" . $_cols_name . ", 'YYYY-MM-DD HH24:Mi:SS') as " . $_cols_name;
			}else{
				if($_cols_name != 'id' && $_cols_name != 'isactive'){
					$cols_str .= ', ' . $_cols_name;
				}
			}

			//如果是下拉框、复选框等类型
			if( $value['type'] == 'select' || $value['type'] == 'checkbox' || $value['type'] == 'multiUpload'){
				if($value['type_value'] != ''){
					$value['type_value_arr'] = json_decode($value['type_value'], true);
				//默认选项，在下面外键中再次匹配一次
				}else{
					$value['type_value_arr'] = array( array('select_option_value'=>'', 'select_option_title'=>'请配置下拉框选项或者外键关系'), );
				}
			}
			
			$dict_columns_key[$value['columns_name']] = $value;
			$dict_columns_init[$value['columns_name']] = '';
		}

		//有id且不带分号
		if($data['id'] && strpos($data['id'], ';') === false){
			$list = $DataBase->selectDb("select " . $cols_str . " from " . $data['table_name'] . " where id=" . $data['id']);

			if($list['error'] != ''){
				return array('code'=>'-1', 'message'=>$list['message'], 'data'=>array('res'=>$list, ),);
				// $this->ajaxReturn( array('code'=>'-1', 'message'=>$list['message'], 'data'=>array('res'=>$list, ),) );
			}else if( empty($list) ){
				return array('code'=>'-1', 'message'=>'查无数据', 'data'=>array('res'=>$list, ),);
				// $this->ajaxReturn( array('code'=>'-1', 'message'=>'查无数据', 'data'=>array('res'=>$list, ),) );
			}else{

			}
			$info = $list[0];
			//处理数据格式
			foreach ($dict_columns_key as $key => $value) {
				if( isset($value['type']) && $value['type'] == 'input' ){
					$info[$key] = htmlspecialchars($info[$key]);
				}
				//如果是图片路径
				if( isset($value['type']) && $value['type'] == 'singleUpload' ){
					$_OSS = C('OSS');
					$info[$key] = array(
						'value'		=> $info[$key],
						'option' 	=> $_OSS['LOCAL'] . $info[$key],
					);
				}
			}
		}else{
			$dict_columns_init['id'] = 'new';
			$dict_columns_init['isactive'] = 'Y';
			$info = $dict_columns_init;
			
			//有id且 带分号  为批量编辑
			if( $data['id'] && strpos($data['id'], ';') !== false ){
				$info['id'] = $data['id'];
			}
			//处理数据格式
			foreach ($dict_columns_key as $key => $value) {
				if( isset($value['type']) && ($value['type'] == 'text' || $value['type'] == '') ){
					$dict_columns_key[$key]['type'] = 'input';
				}
			}
		}

		//外键
		if( $source ){
			$source_list = $DataBase->selectDb("select id,table_name,columns_name,upper(source_table_name) as source_table_name,source_columns_name,source_cols_list from dict_columns_source where (upper(table_name)='" . strtoupper($data['table_name']) . "') and isactive='Y'");
			if( !empty($source_list) ){
				foreach ($source_list as $key => $value) {
					//如果匹配到外键字段
					$columns_name_info = explode( ' ', trim($value['columns_name']) );
					$value['columns_name'] = strtolower($columns_name_info[0]);
					$_where = '';
					if( isset($columns_name_info[1]) ){
						unset($columns_name_info[0]);
						foreach ($columns_name_info as $keyt => $valuet) {
							$_where .= ' ' . $valuet;
						}
					}
					// dump($value['columns_name']);
					if( isset($dict_columns_key[$value['columns_name']]) ){
						$dict_columns_key[$value['columns_name']]['source_info'] = $value;

						//再次 匹配下拉框、复选框等类型
						if( $dict_columns_key[$value['columns_name']]['type'] == 'select' || $dict_columns_key[$value['columns_name']]['type'] == 'checkbox' || $dict_columns_key[$value['columns_name']]['type'] == 'multiUpload'){
							if($dict_columns_key[$value['columns_name']]['type_value'] != ''){
								// $dict_columns_key[$value['columns_name']]['type_value_arr'] = json_decode($dict_columns_key[$value['columns_name']]['type_value'], true);
							}else{
								//权限
								if( $data_authority != '' && isset($data_authority[strtoupper($data['table_name'])][$value['columns_name']]) ){
									$_where .= " and " . $value['source_columns_name'] . $data_authority[strtoupper($data['table_name'])][$value['columns_name']]['where'];
								}
								$_source_table_info = $value;
								$_source_cols_str = $_source_table_info['source_columns_name'] . ' as select_option_value,' . str_replace(";", ',', $_source_table_info['source_cols_list']) . ' as select_option_title';
								$dict_columns_key[$value['columns_name']]['sql'] = "select " . $_source_cols_str . " from " . $_source_table_info['source_table_name'] . " where isactive='Y'" . $_where;
								$dict_columns_key[$value['columns_name']]['type_value_arr'] = $DataBase->selectDb($dict_columns_key[$value['columns_name']]['sql']);
								if( $dict_columns_key[$value['columns_name']]['type'] != 'checkbox' ){
									$dict_columns_key[$value['columns_name']]['type_value_arr'][] = array('select_option_value'=>'', 'select_option_title'=>'无');
								}
							}
						}
					}
				}
			}
		}

		//反向外键 找到子表
		$source_list_reverse = array( array(), );
		if( $source_reverse ){
			$_source_list_reverse = $DataBase->selectDb("select a.*,b.title from (select id,upper(table_name) as table_name,columns_name,upper(source_table_name) as source_table_name,source_columns_name,seq," . $DataBase->nvl() . "(groups, 0) as groups from dict_columns_source where (upper(source_table_name)='" . strtoupper($data['table_name']) . "') and isactive='Y') a left join dict_tables b on upper(a.table_name)=b.table_name order by" . $DataBase->orderBy( array(array('field'=>'a.seq', 'sort'=>'asc', 'nulls'=>'last',),) )
			);

			if( empty($_source_list_reverse) ){
				
			}else{
				$source_list_reverse = array();
				foreach ($_source_list_reverse as $key => $value) {
					$value['cols_value']  = $info[strtolower($value['source_columns_name'])];
					$source_list_reverse[$value['groups']][] = $value;
				}
			}
		}

		// $this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('dict_columns'=>$dict_columns, 'dict_columns_key'=>$dict_columns_key, 'list'=>$list[0], 'sql'=>"select id, isactive" . $cols_str . " from " . $data['table_name']), ) );
		$rdata = array('code'=>'1', 'message'=>'ok', 'data'=>array('dict_columns'=>$dict_columns_key, 'info'=>$info, 'source_list'=>$source_list, 'source_list_reverse'=>$source_list_reverse, 'sql'=>"select " . $cols_str . " from " . $data['table_name'] . " where id=" . $data['id']), );

		return $rdata;
	}

	//执行动作定义
	//table_name执行的表， data_sync执行的数据， id回写依据，type执行类型
	public function portalActive($table_name, $data_sync, $id, $type){
		$DataBase = D(DBTYPE);
		$sync_res = '';
		$success_datas = '';
		$active_success_res = '';
		//动作定义开始
		$active = $DataBase->selectDb("select * from dict_active where isactive='Y' and (upper(table_name)='" . strtoupper($table_name) . "') order by id asc");
		if( !empty($active) ){
			foreach ($active as $key => $value) {
				if( ($value['active_site'] == 'insert' && $type == 'insert') || ($value['active_site'] == 'jedit' && $type == 'edit') || $value['active_site'] == 'edit' ){
					$sync_datas = array();
					$sync_datas_key = 'data';
					$sync_datas_array = 'Y';
					//如果配置了同步参数
					if($value['active_data']){
						$_active_data = json_decode($value['active_data'], true);
						if( isset($_active_data['_active_data']) && $_active_data['_active_data'] != '' ){
							$active_data = $_active_data['_active_data'];
						}else{
							$active_data = $_active_data;
						}
						if( isset($_active_data['_active_data_key']) && $_active_data['_active_data_key'] != '' ){
							$sync_datas_key = $_active_data['_active_data_key'];
						}else if(isset($_active_data['_active_data_key']) && $_active_data['_active_data_key'] == ''){
							$sync_datas_key = '';
						}
						if( isset($_active_data['_active_data_array']) && $_active_data['_active_data_array'] != '' ){
							$sync_datas_array = $_active_data['_active_data_array'];
						}

						foreach ($active_data as $keyt => $valuet) {
							//设计初始留下的坑 valuet是个二维数组没有设计成对象 在这里进行处理成一维对象！！！
							$_valuet = array();
							foreach ($valuet as $keytt => $valuett) {
								foreach ($valuett as $keyttt => $valuettt) {
									$_valuet[strtolower($keyttt)] = $valuettt;
								}
							}
							//目前仅支持传递本表, 已支持5层唯一外键
							if($keyt == strtoupper($table_name) || $keyt == strtolower($table_name) ){
								foreach ($_valuet as $keytt => $valuett) {
									// foreach ($valuett as $keyttt => $valuettt) {
									// 	$sync_datas[$valuettt] = $data_sync[strtolower($keyttt)];
									// }
									$sync_datas[$valuett] = $data_sync[$keytt];
								}
							}else{
								// $cols_source = $DataBase->selectDb("select * from dict_columns_source where (table_name='" . strtolower($table_name) . "' or table_name='" . strtoupper($table_name) . "') and (source_table_name='" . strtolower($keyt) . "' or source_table_name='" . strtoupper($keyt) . "')");
								// if( empty($cols_source) || !isset($cols_source[0]['id']) ){
								// 	$this->ajaxReturn( array('code'=>'-1', 'message'=>'动作定义:发送的数据构建异常，请确认表' . $table_name . '与' . $table_name . '的外键关系', 'data'=>array(), ) );
								// }
								// $cols_source = $cols_source[0];
								$_source_table_tree = $this->_source_table_tree($table_name, $keyt);
								if( $_source_table_tree == '' ){
									return array('code'=>'-1', 'message'=>'动作定义:发送的数据构建异常，请确认表' . $table_name . '到' . $keyt . '的外键关系', 'data'=>array('_source_table_tree'=>$_source_table_tree), );
								}
								$_source_table_cols = '';
								foreach ($_valuet as $keytt => $valuett) {
									$_source_table_cols .= $keyt . '.' . $keytt . ',';
								}
								$_source_table_info = $DataBase->selectDb("select " . rtrim($_source_table_cols, ',') . " from " . $_source_table_tree['join'] . " where " . $_source_table_tree['s_tab'] . "." . $_source_table_tree['s_col'] . "='" . $data_sync[strtolower($_source_table_tree['col'])] . "'");

								if( isset($_source_table_info['message']) || empty($_source_table_info) ){
									return array(
										'code'=>'-1', 
										'message'=>'动作定义:发送的数据' . rtrim(join(',',$_source_table_cols), ',') . '查询为空', 
										'data'=>array(
											$_source_table_cols,
											$keyt,
											$_valuet,
											$_source_table_info,
											$_source_table_tree,
											"select " . rtrim($_source_table_cols, ',') . " from " . $_source_table_tree['join'] . " where " . $_source_table_tree['s_col'] . "='" . $data_sync[strtolower($_source_table_tree['col'])] . "'",
										),
									);
								}else if(count($_source_table_info) > 1){
									return array(
										'code'=>'-1', 
										'message'=>'动作定义:发送的数据' . rtrim(join(',',$_source_table_cols), ',') . '为多行', 
										'data'=>array(
											$_source_table_cols,
											$keyt,
											$_valuet,
											$_source_table_info,
											$_source_table_tree,
											"select " . rtrim($_source_table_cols, ',') . " from " . $_source_table_tree['join'] . " where " . $_source_table_tree['s_col'] . "='" . $data_sync[strtolower($_source_table_tree['col'])] . "'",
										),
									);
								}
								foreach ($_source_table_info[0] as $keytt => $valuett) {
									$sync_datas[$_valuet[$keytt]] = $valuett;
								}
							}
						}
					}

					//完善数据结构
					if( $sync_datas_array == 'Y' ){
						$sync_datas = array($sync_datas);
					}
					if($sync_datas_key != ''){
						$post_sync_data[$sync_datas_key] = json_encode( $sync_datas );
					}else{
						// $post_sync_data = json_encode( $sync_datas );
						$post_sync_data = $sync_datas;
					}

					//开始同步
					$sync_res = bj_curls('POST', $value['active_main'], $post_sync_data);
					$sync_res_arr = json_decode($sync_res, true);
					if( !isset($sync_res_arr['Msg']) || $sync_res_arr['Msg'] != 'SUCCESS'){
						return array('code'=>'-1', 'message'=>'动作定义[ ' . $value['title'] . ' ]执行失败：' . $sync_res_arr['Msg'], 'data'=>array('post_sync_data'=>$post_sync_data, 'sync_res'=>$sync_res),);
					}

					$success_datas = array();
					//如果配置了成功回写参数
					if($value['active_success'] != ''){
						$active_success = json_decode($value['active_success'], true);
						foreach ($active_success as $keyt => $valuet) {
							//目前仅支持传递本表
							if($keyt == strtoupper($table_name) || $keyt == strtolower($table_name) ){
								foreach ($valuet as $keytt => $valuett) {
									foreach ($valuett as $keyttt => $valuettt) {
										$success_datas[$keyttt] = isset($sync_res_arr['Data'][$valuettt]) ? $sync_res_arr['Data'][$valuettt] : $sync_res_arr['Data'][0][$valuettt];
									}
								}
							}
						}
						//开始写入应答数据
						$active_success_res = $DataBase->updateDb("update " . $table_name . " set " . $DataBase->arr_str_upd($success_datas, 'Y') . " where id = " . $id);
					}

					//失败执行动作
				}
			}
		}
		return array('code'=>'1', 'message'=>'ok', 'data'=>array('sync_res'=>$sync_res, 'success_datas'=>$success_datas, 'active_success_res'=>$active_success_res),);
	}

	//递归找出两个表的外键关联
	public function _source_table_tree($table, $source_table, $num=0, $table_join_name='', $data_authority=''){
		$DataBase = D(DBTYPE);

		$num ++;
		if($num > 5){
			return '';
		}

// 		$cols_source_list = $DataBase->selectDb("
// select dc1.data_type as source_data_type,dc2.data_type, 
// 	   t.id, t.isactive, t.table_name, t.columns_name, t.source_table_name, t.source_columns_name, t.source_cols_list
// from dict_columns_source t 
// left join " . $DataBase->db_columns_table . " dc1 on upper(t.source_columns_name)=dc1.column_name and t.source_table_name=dc1.table_name
// left join " . $DataBase->db_columns_table . " dc2 on (upper(t.columns_name)=dc2.column_name or upper(substr(t.columns_name, 0, instr(t.columns_name, ' ')-1))=dc2.column_name) and t.table_name=dc2.table_name
// where (upper(t.table_name)='" . strtoupper($table) . "') and t.isactive='Y' and dc1.data_type=dc2.data_type");
		$cols_source_list = D('Dict')->colsSourceList($table, " and t.isactive='Y'");

		//如果当前表没有关联表 或者层数太多 返回false
		if( empty($cols_source_list) ){
			return '';
		}
		//如果关联上目标表 就返回 需要的数据
		$rdata = array();
		foreach ($cols_source_list as $key => $value) {
			if( $value['source_data_type'] !== $value['data_type'] && strpos($value['data_type'], $value['source_data_type']) === false && strpos($value['source_data_type'], $value['data_type']) === false ){
				unset($cols_source_list[$key]);
				continue;
			}
			// if( strtolower($value['source_table_name']) == strtolower($source_table) && $value['source_data_type'] == $value['data_type'] ){
			if( strtolower($value['source_table_name']) == strtolower($source_table) ){
				if(	!isset($rdata) ){
					$rdata = array(
						'tab'				=> $value['table_name'], 
						'col'				=> $value['columns_name'], 
						's_tab'				=> $value['source_table_name'], 
						's_col'				=> $value['source_columns_name'], 
						'join'				=> '', 
						'select_cols'		=> '',
						'select_cols_list'	=> array(),
						'where_list'		=> array(),
					);
				}
				//如果当前表是 主表
				if($num <= 1 && $table_join_name == ''){
					$rdata['join'] .= "
	" . $source_table;
					// $rdata['select_cols'] .= ', ' . $value['source_cols_list'];
					// $rdata['select_cols_list'][] = $value['source_cols_list'];
				}else{
					//别名
					$_table_as = strtolower($source_table . "_" . $num . "_" . $key);
					$_columns_name = str_replace(strtoupper($source_table), $_table_as, $value['columns_name']);

					//权限
					if( $data_authority != '' && isset($data_authority[$source_table]) ){
						foreach ($data_authority[$source_table] as $keyt => $valuet) {
							$rdata['where_list'][] = " and " . $_table_as . "." . $keyt . $valuet['where'];
						}
					}

					$_soure_cols_list = explode(';', $value['source_cols_list']);
					$_soure_cols_list = $_soure_cols_list[0];
					$_soure_cols_list = explode('.', $_soure_cols_list);
					if( count($_soure_cols_list) > 1 ){
						$_soure_cols_list = $_soure_cols_list[1];
					}else{
						$_soure_cols_list = $_soure_cols_list[0];
					}

					$rdata['join'] .= "
		left join " . $source_table . " " . $_table_as . " on " . $_table_as . "." . $value['source_columns_name'] . "=" . $table_join_name . "." . $_columns_name;
					$rdata['select_cols'] .= ', ' . $_table_as . "." . $_soure_cols_list . ' as ' . $_table_as . "_" . $_soure_cols_list;
					$rdata['select_cols_list'][] = $_table_as . "_" . $_soure_cols_list;
				}

				$r_type = true;
			}
		}
		if( !empty($rdata) ){
			return $rdata;
		}

		//有关联表但是没有目标表 则 继续执行递归
		foreach ($cols_source_list as $key => $value) {
			$value['_source_table_name'] = strtoupper($value['source_table_name'] . "_" . $num . '_' . $key);

			$_source_table_tree = $this->_source_table_tree($value['source_table_name'], $source_table, $num, $value['_source_table_name'], $data_authority);
			if($_source_table_tree != ''){
				$rdata = array('tab'=>$value['table_name'], 'col'=>$value['columns_name'], 's_tab'=>$value['source_table_name'], 's_col'=>$value['source_columns_name']);

				if($num <= 1 && $table_join_name == ''){
					$rdata['join'] = "
	" . $value['source_table_name'] . " " . $value['_source_table_name'] . $_source_table_tree['join'];
				}else{
					$rdata['join'] = "
	left join " . $value['source_table_name'] . " " . $value['_source_table_name'] . " on " . $value['_source_table_name'] . "." . $value['source_columns_name'] . "=" . $table_join_name . "." . $value['columns_name'] . $_source_table_tree['join'];
				}

				//权限
				$rdata['where_list'] = array();
				if( $data_authority != '' && isset($data_authority[$value['source_table_name']]) ){
					foreach ($data_authority[$value['source_table_name']] as $keyt => $valuet) {
						$rdata['where_list'][] = " and " . $value['source_table_name'] . "." . $keyt . $valuet['where'];
					}
				}
				$rdata['where_list'] = array_merge($rdata['where_list'], $_source_table_tree['where_list']);

				$rdata['select_cols'] = $_source_table_tree['select_cols'];
				$rdata['select_cols_list'] = $_source_table_tree['select_cols_list'];
				return $rdata;
			}
		}
		return '';
	}

	//递归找出某个表的所有外键
	public function _source_table_trees($table, $source_table='', $num=0){
		$DataBase = D(DBTYPE);

		$num ++;
		if($num > 5){
			return array();
		}

// 		$cols_source_list = $DataBase->selectDb("
// select dc1.data_type as source_data_type,dc2.data_type, dc2.column_name,
// 	   t.id, t.isactive, t.table_name, t.columns_name, t.source_table_name, t.source_columns_name, t.source_cols_list
// 	   ,sdt.title as source_table_title
// from dict_columns_source t 
// left join " . $DataBase->db_columns_table . " dc1 on upper(t.source_columns_name)=dc1.column_name and t.source_table_name=dc1.table_name
// left join " . $DataBase->db_columns_table . " dc2 on (upper(t.columns_name)=dc2.column_name or upper(substr(t.columns_name, 0, instr(t.columns_name, ' ')-1))=dc2.column_name) and t.table_name=dc2.table_name
// left join dict_tables sdt on sdt.table_name=upper(t.source_table_name)
// where (upper(t.table_name)='" . strtoupper($table) . "') and t.isactive='Y' and dc1.data_type=dc2.data_type");
		$cols_source_list = D('Dict')->colsSourceList($table, " and t.isactive='Y'");

		//如果当前表没有关联表 或者层数太多 返回false
		if( empty($cols_source_list) ){
			return array();
		}

		$rdata = array();
		$Portal = D("Portal");
		foreach ($cols_source_list as $key => $value) {
			if( $value['source_data_type'] !== $value['data_type'] && strpos($value['data_type'], $value['source_data_type']) === false && strpos($value['source_data_type'], $value['data_type']) === false ){
				unset($cols_source_list[$key]);
				continue;
			}
			// if( !isset($rdata[$value['source_table_name']]) ){
				$_source_table_trees = $this->_source_table_trees($value['source_table_name'], $source_table, $num);

				$_table_info = $Portal->info( array('table_name'=>$value['source_table_name']), true, false );

				// $rdata[$value['columns_name']] = $_table_info['data']['dict_columns'];
				foreach ($_table_info['data']['dict_columns'] as $keyt => $valuet) {
					// $valuet['_columns_name'] = $value['columns_name'];
					$valuet['table_name'] = $value['source_table_name'];
					$valuet['_table_title'] = $value['source_table_title'];
					if( isset($_source_table_trees[$valuet['columns_name']]) ){
						$valuet['title'] = $valuet['title'] . '(' . $_source_table_trees[$valuet['columns_name']][0]['_table_title'] . ')';
						$valuet['children'] = $_source_table_trees[$valuet['columns_name']];
						$valuet['spread'] = true;
					}
					$rdata[$value['columns_name']][] = $valuet;
				}
				// $rdata[$value['source_table_name']] = array(
				// $rdata[] = array(
				// 	'tab'			=> $value['table_name'], 
				// 	'col'			=> $value['columns_name'], 
				// 	's_tab'			=> $value['source_table_name'], 
				// 	's_col'			=> $value['source_columns_name'], 
				// 	'children'		=> $_source_table_trees,
				// 	'table_info'	=> $_table_info['data']['dict_columns'],
				// );
			// }
		}

		return $rdata;
	}
}