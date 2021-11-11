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

class DictModel{
	public function tables($where){
		$DataBase = D(DBTYPE);

		if(DBTYPE == 'Oracle'){
			$tables = $DataBase->selectDb("
				select * from (
					select tc.table_name, tc.table_type, tc.comments, dt.id, dt.isactive, dt.title, dt.notes, dt.is_menu
					from user_tables t 
					left join user_tab_comments tc on t.table_name = tc.table_name
					left join dict_tables dt on t.table_name = UPPER(dt.table_name)
					union
					select tc.table_name, tc.table_type, tc.comments, dt.id, dt.isactive, dt.title, dt.notes, dt.is_menu
					from user_views t 
					left join user_tab_comments tc on t.view_name = tc.table_name
					left join dict_tables dt on t.view_name = UPPER(dt.table_name)
				) tt
				" . $where . "
				order by" . $DataBase->orderBy( array(array('field'=>'tt.id', 'sort'=>'asc', 'nulls'=>'last',),array('field'=>'tt.table_name', 'sort'=>'asc',),) )
			);
		}else if(DBTYPE == 'Mysql'){
			$tables = $DataBase->selectDb("
				select * from (
					select t.table_name, 'table' as table_type, t.table_comment as comments, dt.id, dt.isactive, dt.title, dt.notes, dt.is_menu
					from information_schema.tables t 
					left join dict_tables dt on t.table_name = UPPER(dt.table_name)
					where t.table_schema=(select database())
					union all 
					select t.table_name, 'view' as table_type, '' as comments, dt.id, dt.isactive, dt.title, dt.notes, dt.is_menu
					from information_schema.views t 
					left join dict_tables dt on t.table_name = UPPER(dt.table_name)
					where t.table_schema=(select database())
				) tt
				" . $where . "
				order by" . $DataBase->orderBy( array(array('field'=>'tt.id', 'sort'=>'asc', 'nulls'=>'last',),array('field'=>'tt.table_name', 'sort'=>'asc',),) )
			);
		}else{
			$tables = array();
		}

		return $tables;
	}

	public function columns($where){
		$DataBase = D(DBTYPE);

		if(DBTYPE == 'Oracle'){
			$columns = $DataBase->selectDb("
				select t.column_name, t.data_type, t.nullable, case when t.data_type='NUMBER' then t.data_precision else t.data_length end as data_length, t.data_type || '(' || case when t.data_type='NUMBER' then t.data_precision || ',' || t.data_scale else to_char(t.data_length) end || ')' as s_data_type, 
					   tc.comments, 
					   tm.table_type, 
					   dc.*
				from user_tab_columns t 
				left join user_col_comments tc on t.table_name=tc.table_name and t.column_name = tc.column_name
				left join user_tab_comments tm ON tm.table_name=t.table_name
				left join (
					select UPPER(t2.table_name) as table_name, UPPER(t1.columns_name) as columns_name, t1.isactive, t1.id, t1.title, t1.in_table, t1.type, t1.type_value, t1.notes, t1.dict_tables_id, t1.seq
					from dict_columns t1 
					left join dict_tables t2 on t1.dict_tables_id = t2.id
				) dc on dc.table_name=tc.table_name and dc.columns_name=t.column_name
				" . $where ."
				order by" . $DataBase->orderBy( array(array('field'=>'dc.isactive', 'sort'=>'desc', 'nulls'=>'last',),array('field'=>'dc.seq',),array('field'=>'dc.id',),) )
			);
		}else if(DBTYPE == 'Mysql'){
			if( $where == ''){
				$where = 'where 1=1';
			}
			$columns = $DataBase->selectDb("
				select t.column_name, t.data_type, t.is_nullable as nullable, ifnull(ifnull(t.character_maximum_length, t.numeric_precision), datetime_precision) as data_length, t.data_type as s_data_type, 
					   t.column_comment,
					   tm.table_type,
					   dc.*
				from information_schema.columns t 
				left join information_schema.tables tm ON tm.table_name=t.table_name
				left join (
					select UPPER(t2.table_name) as table_name, UPPER(t1.columns_name) as columns_name, t1.isactive, t1.id, t1.title, t1.in_table, t1.type, t1.type_value, t1.notes, t1.dict_tables_id, t1.seq
					from dict_columns t1 
					left join dict_tables t2 on t1.dict_tables_id = t2.id
				) dc on dc.table_name=t.table_name and dc.columns_name=t.column_name
				" . $where ." and tm.table_schema=(select database()) and t.table_schema=(select database())
				order by" . $DataBase->orderBy( array(array('field'=>'dc.isactive', 'sort'=>'desc', 'nulls'=>'last',),array('field'=>'dc.seq',),array('field'=>'dc.id',),) )
			);
		}else{
			$columns = array();
		}

		return $columns;
	}

	//查询数据库系统表包不包含某些表（以及视图、方法等对象）
	public function objectCount($object_str){
		if(DBTYPE == 'Oracle'){
			return D(DBTYPE)->selectDb("select count(1) as counts from user_objects where object_name in ('" . $object_str . "')");
		}else if(DBTYPE == 'Mysql'){
			return D(DBTYPE)->selectDb("
				select sum(counts) as counts from (
					select count(*) as counts from mysql.proc t where t.db = (select database()) and t.name in ('" . $object_str . "') 
					union all
					select count(*) as counts from information_schema.tables t where t.table_schema=(select database()) and t.table_name in ('" . $object_str . "')
				) as tt");
		}else{
			return false;
		}
	}

	//查询某表的所有的  设置的外键
	public function colsSourceList($table, $where=''){
		$DataBase = D(DBTYPE);
		
		$cols_source_list = $DataBase->selectDb("
select dc1.data_type as source_data_type,dc2.data_type, dc2.column_name,
	   t.id, t.isactive, t.table_name, t.columns_name, t.source_table_name, t.source_columns_name, t.source_cols_list
	   ,sdt.title as source_table_title
from dict_columns_source t 
left join " . $DataBase->db_columns_table . " dc1 on upper(t.source_columns_name)=dc1.column_name and t.source_table_name=dc1.table_name
left join " . $DataBase->db_columns_table . " dc2 on (upper(t.columns_name)=dc2.column_name or upper(substr(t.columns_name, 0, instr(t.columns_name, ' ')-1))=dc2.column_name) and t.table_name=dc2.table_name
left join dict_tables sdt on sdt.table_name=upper(t.source_table_name)
where (upper(t.table_name)='" . strtoupper($table) . "')" . $where);

		return $cols_source_list;
	}
}