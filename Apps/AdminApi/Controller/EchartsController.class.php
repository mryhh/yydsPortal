<?php
/**
 *
 * EchartsController.class.php
 *
 * 后台 - 定制化接口
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class EchartsController extends CommController {
	//修复总学时
	public function index(){
		$DataBase = D(DBTYPE);

		$params = I('post.');

		if( $params['seq'] != '' ){
			$list = $DataBase->selectDb("select * from admin_echarts where isactive='Y' and seq=" . $params['seq'] . " order by seq asc");
		}else{
			$list = $DataBase->selectDb("select * from admin_echarts where isactive='Y' order by seq asc");
		}


		$data_authority = $this->_c_data_authority;

		foreach ($list as $_key => $_data) {
			$table_name = $_data['table_name'];
			//
			$dict_columns = D('Dict')->columns("where t.table_name='" . $table_name . "'");

			foreach ($dict_columns as $key => $value) {
				$dict_columns[$key]['columns_name'] = strtolower($dict_columns[$key]['columns_name']);

				if( $dict_columns[$key]['id'] == '' || is_null($dict_columns[$key]['id']) ){
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
			}

			$dict_columns_key = array();
			$cols_str = '';
			foreach ($dict_columns as $key => $value) {
				$value['source_info'] = '';
				$dict_columns_key[$value['columns_name']] = $value;

				if( strtolower($value['data_type']) == 'date' && DBTYPE == 'Oracle'){
					$value['columns_name'] = "to_char(" . $value['columns_name'] . ", 'YYYY-MM-DD HH24:MI:SS') as " . $value['columns_name'];
				}
				
				$cols_str .= ', ' . $value['columns_name'];
			}
			
			//字段分组维度  有字段分组维度才做关联查询
			if($_data['group_columns'] != ''){
				//列表类 外键方法
				$index_source = D("Portal")->index_source($table_name, $data_authority, $dict_columns_key);
				$_join = $index_source['_join'];
				$all_source_joins = $index_source['all_source_joins'];
				$_join_cols_str = $index_source['_join_cols_str'];
				$dict_columns_key = $index_source['dict_columns_key'];
				$cols_source_list = $index_source['cols_source_list'];
				$_join_where = $index_source['_join_where'];
			}else{
				$_join = '';
				$_join_cols_str = '';
				$_join_where = '';
			}

			//where条件创建
			$where = $_data['wheres'];

			//group by 创建
			//字段分组维度
			$_group_col = $_group = '';
			if($_data['group_columns'] != ''){
				$_group_col = ', ' . $_data['group_columns'];
				$_group = 'group by ' . $_data['group_columns'];
			}
			//时间分组维度
			if($_data['time_column'] != ''){
				if( !$params['time_val'] || $params['time_val'] == '' ){
					$params['time_val'] = 3;
				}
				if( !$params['time_unit'] || $params['time_unit'] == '' ){
					$params['time_unit'] = 'day';
				}
				if($_group == ''){
					$_group = 'group by';
				}else{
					$_group .= ',';
				}
				if($params['time_unit'] == 'hours'){
					$where .= " and " . $_data['time_column'] . ">'" . date("Y-m-d H", strtotime(date("Y-m-d H") . ' -' . ($params['time_val'] - 1) . ' hours')) . "'" ;
					$_group_col .= ", date_format(" . $_data['time_column'] . ", '%Y-%m-%d %H') as admin_group_date";
					$_group .= " admin_group_date";
				}else if($params['time_unit'] == 'day'){
					$where .= " and " . $_data['time_column'] . ">'" . date("Y-m-d", strtotime(date("Y-m-d") . ' -' . ($params['time_val'] - 1) . ' day')) . "'" ;
					$_group_col .= ", date(" . $_data['time_column'] . ") as admin_group_date";
					$_group .= " admin_group_date";
				}else if($params['time_unit'] == 'week'){
					$where .= " and " . $_data['time_column'] . ">'" . date("Y-m-d", strtotime(date("Y-m-d") . ' -' . ($params['time_val'] - 1) . ' week last monday')) . "'" ;
					$_group_col .= ", date_format(" . $_data['time_column'] . ", '%Y-%u') as admin_group_date";
					$_group .= " admin_group_date";
				}else if($params['time_unit'] == 'month'){
					$where .= " and " . $_data['time_column'] . ">'" . date("Y-m", strtotime(date("Y-m") . ' -' . ($params['time_val'] - 1) . ' month')) . "-01'" ;
					$_group_col .= ", date_format(" . $_data['time_column'] . ", '%Y-%m') as admin_group_date";
					$_group .= " admin_group_date";
				}else if($params['time_unit'] == 'year'){
					$where .= " and " . $_data['time_column'] . ">'" . date("Y", strtotime(date("Y") . ' -' . ($params['time_val'] - 1) . ' year')) . "-01-01'" ;
					$_group_col .= ", date_format(" . $_data['time_column'] . ", '%Y') as admin_group_date";
					$_group .= " admin_group_date";
				}
			}

			//原始数据
			$sql = "
		select " . ltrim($cols_str, ',') . " 
		from " . $table_name . " 
		where 1=1 " . $where;

			if($_data['statistics_type'] == 'count' || $_data['statistics_type'] == 'sum'){
				$statistics = $_data['statistics_type'] . "(" . $table_name . "." . $_data['column_name'] . ") as " . $_data['column_name'];
			}

			$sql = "
	select " . $statistics . $_group_col . "
	from (" . $sql . "
	) " . $table_name . "
	" . $_group;

			//构建join
			$sql = "
select " . $table_name . ".*" . $_join_cols_str . " 
from (" . $sql . "
) " . $table_name . "
" . $_join . "
where 1=1 " . $_join_where . "
" . $_order_by;

			$res_sql[$_data['seq']][] = $sql;
			$_res = $DataBase->selectDb($sql);
			$_res_list[] = $_res;

			//目前先 分别构造三种图表数据
			
			if($_data['time_column'] != '' && $_data['group_columns'] != ''){
				foreach ($_res as $key => $value) {
					// $_res_data[$value['admin_group_date']][$value[$_data['group_columns']]][$dict_columns_key[$_data['group_columns']]['source_info']['source_cols_list_arr'][0]] = $value[$_data['column_name']];
					$_res_charts[$_data['seq']]['legend'][$value[$dict_columns_key[$_data['group_columns']]['source_info']['source_cols_list_arr'][0]]] = 'Y';
					$_res_charts[$_data['seq']]['yAxis'][$value['admin_group_date']] = 'Y';
					$_res_charts[$_data['seq']]['series'][$value[$dict_columns_key[$_data['group_columns']]['source_info']['source_cols_list_arr'][0]]][$value['admin_group_date']][$_key] = $value[$_data['column_name']];
				}
				$_res_charts[$_data['seq']]['type'] = array('time_column', 'group_columns');
				$_res_charts[$_data['seq']]['_time'] = array(
					'time_val'	=> $params['time_val'],
					'time_unit'	=> $params['time_unit'],
				);
			}else if($_data['time_column'] != ''){
				foreach ($_res as $key => $value) {
					// $_res_data[$value['admin_group_date']][$value[$_data['group_columns']]][$dict_columns_key[$_data['group_columns']]['source_info']['source_cols_list_arr'][0]] = $value[$_data['column_name']];
					$_res_charts[$_data['seq']]['legend'][$_data['data_name']] = 'Y';
					$_res_charts[$_data['seq']]['yAxis'][$value['admin_group_date']] = 'Y';
					$_res_charts[$_data['seq']]['series'][$_data['data_name']][$value['admin_group_date']] = $value[$_data['column_name']];
				}
				$_res_charts[$_data['seq']]['type'] = array('time_column');
				$_res_charts[$_data['seq']]['_time'] = array(
					'time_val'	=> $params['time_val'],
					'time_unit'	=> $params['time_unit'],
				);
			}else if($_data['group_columns'] != ''){
				$_res_charts[$_data['seq']] = array();
			}else{
				$_res_charts[$_data['seq']] = array();
			}

			if( $_data['title'] != '' ){
				$_res_charts[$_data['seq']]['title'] = $_data['title'];
			}
		}

		foreach ($_res_charts as $key => $value) {
			ksort($value['yAxis']);
			$_legend = $_yAxis = $_series = $_category_Axis = array();
			if( in_array('time_column', $value['type']) && in_array('group_columns', $value['type']) ){
				foreach ($value['legend'] as $keyt => $valuet) {
					$_legend[] = $keyt;
					
					$_series_data_left = $_series_data_right = array();
					foreach ($value['yAxis'] as $keytt => $valuett) {
						// if( isset($value['series'][$keyt][$keytt]) && !empty($value['series'][$keyt][$keytt]) ){
							$_series_data_left[] = ( isset($value['series'][$keyt][$keytt][1]) ) ? (0 - $value['series'][$keyt][$keytt][1]) : 0;
							$_series_data_right[] = ( isset($value['series'][$keyt][$keytt][0]) ) ? $value['series'][$keyt][$keytt][0] : 0;
						// }
					}
					$_series[] = array(
						'name'		=> $keyt,
						'type'		=> 'bar',
						'label'		=> array(
							'show'		=> true,
							'position'	=> 'left',
						),
						'emphasis'	=> array(
							'focus'		=> 'series',
						),
						'stack'		=> $keyt,
						'data'		=> $_series_data_left,
					);
					$_series[] = array(
						'name'		=> $keyt,
						'type'		=> 'bar',
						'label'		=> array(
							'show'		=> true,
						),
						'emphasis'	=> array(
							'focus'		=> 'series',
						),
						'stack'		=> $keyt,
						'data'		=> $_series_data_right,
					);
				}
				foreach ($value['yAxis'] as $keyt => $valuet) {
					$_category_Axis[] = $keyt;
				}
				$echarts_list[$key] = array(
					'_type'			=> array(
						'type'			=> 'NegativeLeftRightBar',
						'time'			=> $value['_time'],
					),
					'xAxis'			=> array(
						'type'		=> 'value',
						'data'		=> array(),
					),
					'yAxis'			=> array(
						'type'			=> 'category',
						'axisTick'		=> array(
							'show'			=> false,	
						),
						'data'			=> $_category_Axis,
					),
					'series'		=> $_series,
				);
			}else if( in_array('time_column', $value['type']) ){
				foreach ($value['legend'] as $keyt => $valuet) {
					$_legend[] = $keyt;

					$_series_data = array();
					foreach ($value['yAxis'] as $keytt => $valuett) {
						$_series_data[] = isset($value['series'][$keyt][$keytt]) ? $value['series'][$keyt][$keytt] : 0;
					}
					$_series[] = array(
						'name'		=> $keyt,
						'type'		=> 'line',
						'label'		=> array(
							'show'		=> true,
						),
						'emphasis'	=> array(
							'focus'		=> 'series',
						),
						'stack'		=> $keyt,
						'data'		=> $_series_data,
					);
				}
				foreach ($value['yAxis'] as $keyt => $valuet) {
					$_category_Axis[] = $keyt;
				}
				$echarts_list[$key] = array(
					'_type'			=> array(
						'type'			=> 'Stacked',
						'time'			=> $value['_time'],
					),
					'yAxis'			=> array(
						'type'		=> 'value',
						'data'		=> array(),
					),
					'xAxis'			=> array(
						'type'			=> 'category',
						'axisTick'		=> array(
							'show'			=> false,	
						),
						'data'			=> $_category_Axis,
					),
					'series'		=> $_series,
				);
			}
			$echarts_list[$key]['title'] = array(
				'text'		=> isset($value['title']) ? $value['title'] : '无标题',
				'textStyle'	=> array(
					'fontSize'	=> 15,
				),
			);
			$echarts_list[$key]['legend'] = array(
				'data'			=> $_legend,
				'top'			=> 20,
			);
			$echarts_list[$key]['grid'] = array(
				'containLabel'	=> true,
			);
			$echarts_list[$key]['tooltip'] = array(
				'trigger'		=> 'axis',
				'axisPointer'	=> array(
					'type'			=> 'shadow',
				),
			);
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('echarts_list'=>$echarts_list, '_res_charts'=>$_res_charts, '_res_list'=>$_res_list, 'res_sql'=>$res_sql), ) );
	}
}