<?php
/**
 *
 * MysqlModel.class.php
 *
 * 后台接口模块 - Mysql查询函数Model
 *
 * Mr.yh @ 2020.10.21
 * 
 */
 
namespace AdminApi\Model;

class MysqlModel{
	private $_db_name = '';
	private $_sql = '';

	public $db_columns_table = '(select * from information_schema.columns where table_schema=(select database()))';

	private function ajaxReturn($arr){
		header('Content-Type:application/json; charset=utf-8');
		exit( json_encode($arr) );
	}

	public function select(){
		$this->selectDb($);
	}

	//转Db数据
	public function selectDb($sql='', $db_name=''){
		$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		//连接
		$Model = M('', '', 'mysql://' . $db_info['DB_USER'] . ':' . $db_info['DB_PWD'] . '@' . $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'] . '#utf8mb4');
		if (!$Model) {
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $Model,
					'message'	=> 'Db连接失败<br />' . $Model,
				)
			);
		}
		
		//执行
		$data = $Model->query($sql);

		if(isset($data['error']) && $data['error'] != ''){
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $data,
					'message'	=> $data['error'],
				)
			);
		}
		//返回结果集
		return $data;
	}

	// function findDb($sql='', $db_name=''){
	// 	$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

	// 	$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'], "AL32UTF8"); 
	// 	if (!$conn) {
	// 		$e = oci_error();
	// 		echo 'Db连接失败<br />';
	// 		exit($e['message']);
	// 	}
	// 	$stmt = oci_parse($conn, $sql);
	// 	oci_execute($stmt);
	// 	$nrows = oci_fetch_all($stmt, $results);
	// 	oci_close($conn);
	// 	//获取键名
	// 	$data = array();
	// 	foreach ($results as $key => $value) {
	// 		$data[strtolower($key)] = $value[0];
	// 	}

	// 	return $data;
	// }

	public function updateDb($sql='', $db_name=''){
		$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		//连接
		$Model = M('', '', 'mysql://' . $db_info['DB_USER'] . ':' . $db_info['DB_PWD'] . '@' . $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'] . '#utf8');
		if (!$Model) {
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $Model,
					'message'	=> 'Db连接失败<br />' . $Model,
				)
			);
		}

		//执行
		$data = $Model->execute($sql);

		if(isset($data['error']) && $data['error'] != ''){
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $data,
					'message'	=> $data['error'],
				)
			);
		}
		//
		return $data;
	}

	//链接Db库  实现查询语句
	public function connDb($sql='', $db_name=''){
		// $db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		// $conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'], "AL32UTF8");
		// if (!$conn) {
		// 	$e = oci_error();
		// 	$this->ajaxReturn(
		// 		array(
		// 			'code'		=> '-1',
		// 			'error'		=> $e,
		// 			'message'	=> 'Db连接失败<br />' . $e['message'],
		// 		)
		// 	);
		// }
		// $stmt = oci_parse($conn, $sql);
		// return $stmt;
	}

	//调用存储过程
	public function procDb($query='', $value='', $db_name=''){
		// $db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		// $conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'], "AL32UTF8");
		// if (!$conn) {
		// 	$e = oci_error();
		// 	// return $data = array(
		// 	// 	'errorCode'	=> false,
		// 	// 	'error'		=> $e,
		// 	// 	'message'	=> 'Db连接失败<br />' . $e['message'],
		// 	// );
		// 	$this->ajaxReturn(
		// 		array(
		// 			'code'		=> '-1',
		// 			'error'		=> $e,
		// 			'message'	=> 'Db连接失败<br />' . $e['message'],
		// 		)
		// 	);
		// }
		
		// //创建调用语句  
		// $statement = oci_parse($conn,$query); 
		// //提供输入参数 
		// $res = 'null return';//返回值可能出现负值，所以初始化时用负值 
		// //绑定变量，接收返回的参数 
		// oci_bind_by_name($statement,":res", $res, 32000); 
		// oci_bind_by_name($statement,":value", $value); 
		// //执行 
		// oci_execute($statement);
		// oci_close($conn);

		// return json_decode($res,true);
	}

	//构建insert语句
	//$arr需要拼接成insert语句的对象, $auto_detection是否自动检测function
	//拼接结果字符串"()VALUES()"  不包含insert into tables_name
	//每一个字段可以是字符串或数组，数组为了标明字段值是什么属性，不传默认为Y：字符串，N表示数值，F表示函数：第一个参数为值即函数的第一个参数、第二个参数为F、第三个参数为函数名、第四个参数为函数的第二个参数
	public function arr_str_add($arr='', $auto_detection='N', $key_value='Y'){
		$admin_user = session('user');
		$arr['ownerid'] = $admin_user['id'];
		$arr['modifierid'] = $admin_user['id'];
		$arr['creationdate'] = 'sysdate';
		$arr['modifieddate'] = 'sysdate';

		$str_value = "(";
		$str_key = "(";
		foreach ($arr as $key => $value) {
			if($key == 'id' && strpos($value, 'get_sequences(') !== false){
				continue;
			}

			$str_key = $str_key . $key . ", ";
			if(is_array($value)){
				//不是字符串
				if($value[1] == 'N'){
					$str_value = $str_value . $value[0] . ", ";
				//是字符串
				}elseif($value[1] == 'Y'){
					$str_value = $str_value . "'" . $value[0] . "', ";
				//是函数
				}elseif($value[1] == 'F'){
					//有参数
					if($value[3]){
						$str_value = $str_value . $value[2] . "('" . $value[0] . "', '" . $value[3] . "'), ";
					}
					//没有参数
					else{
						$str_value = $str_value . $value[2] . "('" . $value[0] . "'), ";
					}
				}
			//默认是字符串
			}else{
				//自动检测
				if($auto_detection == 'Y'){
					$value = (string)$value;
					//自动检测函数
					if( strpos($value, "('") !== false && strpos($value, "')") !== false ){
						$str_value = $str_value . $value . ", ";
					}else if( strpos($value, "(select") !== false && strpos($value, ")") !== false ){
						$str_value = $str_value . $value . ", ";
					}else if($value == 'sysdate'){
						//sysdate针对mysql加了()
						$str_value = $str_value . $value . "(), ";
					}else{
						$str_value = $str_value . "'" . $value . "', ";
					}
				//不自动检测,全部当做字符串处理
				}else{
					$str_value = $str_value . "'" . $value . "', ";
				}
			}
		}
		$str_value = rtrim($str_value, ", ");
		$str_value = $str_value . ")";

		$str_key = rtrim($str_key, ", ");
		$str_key = $str_key . ")";

		if( $key_value == 'Y'){
			$str = $str_key . "VALUES" . $str_value;
		}else{
			$str = array('key'=>$str_key, 'value'=>$str_value, );
		}

		return $str;
	}

	//构造二维数组插入语句  批量插入
	//mysql语法为 insert into tablename () values (),(),()...
	public function arrs_str_add($arrs='', $auto_detection='N', $table=''){
		$str = 'insert into ' . $table;
		$num = 0;
		foreach ($arrs as $key => $arr) {
			$arr_str_add = $this->arr_str_add($arr, $auto_detection, 'N');

			if($num < 1){
				$str .= ' 
' . $arr_str_add['key'] . ' 
values
' . $arr_str_add['value'];
			}else{
				$str .= ',
' . $arr_str_add['value'];
			}

			$num ++;
		}
		//$str = rtrim($str, ", ");
		// $str .= ' select 1 from DUAL';
		return $str;
	}

	//构造分页limit
	public function db_limit($sql='', $start='0', $end='1', $orderby=''){
		// return $r_sql = "
		// select * 
		// from 
		// (
		// 	select * 
		// 	from 
		// 	(" . $sql . ") limit_table_a
		// 	where rownum <= " . $end . "
		// ) limit_table_b
		// where rownum > " . $start . " " . $orderby;
	}

	//类似arr_str_add 构建update语句 同样不包含update table_name 及 where条件 
	public function arr_str_upd($arr, $auto_detection='N'){
		$admin_user = session('user');
		$arr['modifierid'] = $admin_user['id'];
		$arr['modifieddate'] = 'sysdate';

		$str = "";
		foreach ($arr as $key => $value) {
			if(is_array($value)){
				if($value[1] == 'N'){
					$str = $str . $key . " = " . $value[0] . ", ";
				}elseif($value[1] == 'Y'){
					$str = $str . $key . " = '" . $value[0] . "', ";
				}elseif($value[1] == 'F'){
					if($value[3]){
						$str = $str . $key . " = " . $value[2] . "('" . $value[0] . "', '" . $value[3] . "'), ";
					}
					else{
						$str = $str . $key . " = " . $value[2] . "('" . $value[0] . "'), ";
					}
				}
			}else{
				//自动检测
				if($auto_detection == 'Y'){
					$value = (string)$value;
					//自动检测函数
					if( strpos($value, "('") !== false && strpos($value, "')") !== false ){
						$str = $str . $key . " = " . $value . ", ";
					}else if( strpos($value, "(select") !== false && strpos($value, ")") !== false ){
						$str = $str . $key . " = " . $value . ", ";
					}else if($value == 'sysdate'){
						$str = $str . $key . " = " . $value . "(), ";
					}else{
						$str = $str . $key . " = '" . $value . "', ";
					}
				//不自动检测,全部当做字符串处理
				}else{
					$str = $str . $key . " = '" . $value . "', ";
				}
			}
		}
		$str = rtrim($str, ", ");

		return $str;
	}

	//类似arr_str_add 构建where语句 
	public function arr_str_where($arr, $auto_detection='N'){
		$str = "";
		foreach ($arr as $key => $value) {
			if(is_array($value)){
				if($value[1] == 'N'){
					$str = $str . $key . " = " . $value[0] . " and ";
				}elseif($value[1] == 'Y'){
					$str = $str . $key . " = '" . $value[0] . "' and ";
				}elseif($value[1] == 'F'){
					if($value[3]){
						$str = $str . $key . " = " . $value[2] . "('" . $value[0] . "', '" . $value[3] . "') and ";
					}
					else{
						$str = $str . $key . " = " . $value[2] . "('" . $value[0] . "') and ";
					}
				}
			}else{
				//自动检测
				if($auto_detection == 'Y'){
					$value = (string)$value;
					//自动检测函数
					if( strpos($value, "('") !== false && strpos($value, "')") !== false ){
						$str = $str . $key . " = " . $value . " and ";
					}else if( strpos($value, "(select") !== false && strpos($value, ")") !== false ){
						$str = $str . $key . " = " . $value . " and ";
					}else if($value == 'sysdate'){
						// $str = $str . $key . " = " . $value . "() and ";
					}else{
						$str = $str . $key . " = '" . $value . "' and ";
					}
				//不自动检测,全部当做字符串处理
				}else{
					$str = $str . $key . " = '" . $value . "' and ";
				}
			}
		}
		$str = rtrim($str, " and ");

		return $str;
	}

	//构建mysql建表语句  //没写完..
	public function createTable($arr){
		$str = '';

		$str .= 'create table ' . $arr['object_name'] . '
(';
		foreach ($arr['columns_list'] as $key => $value) {
			$_data_type = ' ' . $this->oracle_mysql_data_type($value['data_type']);
			$_primary_key = (isset($value['primary_key']) ? ' primary key auto_increment' : '');
			$_comment =  (isset($value['primary_key']) ? (" COMMENT '" . $value['comment'] . "'") : '');

			$str .= '
	' . $value['columns_name'] . $_data_type . $_primary_key . $_comment . ',';

		}

		$str = rtrim($str, ',');

		$str .= '
)';
		$arr['object_value'] = array($str,);
		return $arr;
	}

	//排序语句
	public function orderBy($arr){
		$sql = ' ';
		foreach ($arr as $key => $value) {
			if( isset($value['nulls'])){
				if($value['nulls'] == 'first'){
					$sql .= "IF(ISNULL(" . $value['field'] . "),0,1),";
				}else if($value['nulls'] == 'last'){
					$sql .= "IF(ISNULL(" . $value['field'] . "),1,0),";
				}
			}

			if( !isset($value['sort']) ){
				$value['sort'] = 'asc';
			}
			$sql .= $value['field'] . ' ' . $value['sort'] . ',';
		}

		return rtrim($sql, ',');
	}

	//nvl
	public function nvl(){
		return 'ifnull';
	}


	//oralce转mysql 的方法开始
	//oracle转mysql 转数据类型
	public function oracle_mysql_data_type($oracle_data_type){
		//转大写
		$oracle_data_type = strtoupper($oracle_data_type);

		//数字类型
		if( strpos($oracle_data_type, 'NUMBER') !== false ){
			$mysql_data_type = 'INT';
			if( strpos($oracle_data_type, ',') !== false ){
				$mysql_data_type = str_replace('NUMBER', 'DECIMAL', $oracle_data_type);
			}
		//字符串类型
		}else if( strpos($oracle_data_type, 'VARCHAR2') !== false ){
			$mysql_data_type = str_replace('VARCHAR2', 'VARCHAR', $oracle_data_type);
		//时间类型
		}else if( strpos($oracle_data_type, 'DATE') !== false ){
			$mysql_data_type = 'DATETIME';
		//文本
		}else if( strpos($oracle_data_type, 'TEXT') !== false ){
			$mysql_data_type = 'TEXT';
		//其他
		}else{
			$mysql_data_type = $oracle_data_type;
		}

		return $mysql_data_type;
	}

	//oracle转mysql 修改建表语句
	public function oracle_mysql_create_table($arr){
		if($arr['object_type'] == 'TABLE'){
			//
			$create_sql = '';
			$create_key = 0;
			$create_sql_arr = array();
			foreach ($arr['object_value'] as $key => $value) {
				//建表语句
				if( strpos($value, 'create table ' . $arr['object_name']) !== false ){
					//依次换掉VARCHAR2, NUMBER(10), NUMBER(10,2)
					$value = str_replace('VARCHAR2', 'VARCHAR', $value);
					$value = str_replace('DATE', 'DATETIME', $value);
					$value = str_replace('NUMBER(10)', 'INT', $value);
					$value = str_replace('NUMBER', 'DECIMAL', $value);
					//替换自增
					$value = str_replace('constraint pk_' . strtolower($arr['object_name']), 'auto_increment', $value);
					//为comment做准备
					$create_key = $key;
					$create_sql_arr = explode(',', $value);
				//注释语句
				}else if( strpos($value, 'comment on column ' . $arr['object_name'] . '.') !== false ){
					//comment on column DICT_TABLES.table_name is '表名'"
					//ALTER TABLE student MODIFY COLUMN column_name VARCHAR(100) COMMENT '姓名';
					//select t.column_type from information_schema.columns t where t.table_schema=(select database()) and t.table_name='' and t.column_name=''
					//拆分获得字段名和注释内容
					$_comment_str = str_replace('comment on column ' . $arr['object_name'] . '.', '', $value);
					$_comment_arr = explode(" is ", $_comment_str);
					$_column_comment = '';
					foreach ($_comment_arr as $keyt => $valuet) {
						if($keyt < 1){
							$_column_name = $valuet;
						}else{
							$_column_comment .= $valuet;
						}
					}
					//将对应的注释放到create table 语句中
					foreach ($create_sql_arr as $keyt => $valuet) {
						//去除最后一个')'
						if(($keyt+1) == count($create_sql_arr)){
							$valuet = rtrim($valuet, ')');
						}
						//添加defult
						if( (strpos($valuet, 'VARCHAR2(') !== false || strpos($valuet, 'CHAR(') !== false) && strpos($valuet, 'not null') === false ){
							$valuet .= " default '' not null";
						}
						//添加comment
						if( strpos($valuet, '\t' . strtolower($_column_name) . ' ') !== false ||  strpos($valuet, '	' . strtolower($_column_name) . ' ') !== false  ||  strpos($valuet, ' ' . strtolower($_column_name) . ' ') !== false ){
							$valuet .= ' comment ' . $_column_comment;
							unset($arr['object_value'][$key]);
						}
						//补最后一个')'
						if(($keyt+1) == count($create_sql_arr)){
							$valuet .= ')';
						}

						$create_sql_arr[$keyt] = $valuet;
					}
					// $value = "alter table " . $arr['object_name'] . " modify column " . $_column_name . " (select t.column_type from information_schema.columns t where t.table_schema=(select database()) and t.table_name='" . $arr['object_name'] . "' and t.column_name='" . $_column_name . "') " . " comment " . $_column_comment;
				}
			}

			foreach ($create_sql_arr as $key => $value) {
				$create_sql .= $value . ',';
			}
			$create_sql = rtrim($create_sql, ',');
			$arr['object_value'][$create_key] = $create_sql;
		}

		return $arr;
	}
}