<?php
/**
 *
 * OracleModel.class.php
 *
 * 后台接口模块 - Oralc查询函数Model
 *
 * Mr.yh @ 2020.10.21
 * 
 */
 
namespace AdminApi\Model;

class OracleModel{
	public $db_columns_table = 'user_tab_columns';

	private function ajaxReturn($arr){
		header('Content-Type:application/json; charset=utf-8');
		exit( json_encode($arr) );
	}

	//转Db数据
	public function selectDb($sql='', $db_name=''){
		$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'], "AL32UTF8");
		if (!$conn) {
			$e = oci_error();
			// return $data = array(
			// 	'errorCode'	=> false,
			// 	'error'		=> $e,
			// 	'message'	=> 'Db连接失败<br />' . $e['message'],
			// );
			$this->ajaxReturn(
				array(
					'code'		=> '-110',
					'error'		=> $e,
					'message'	=> 'Db连接失败<br />' . $e['message'],
				)
			);
		}
		$stmt = oci_parse($conn, $sql);
		// oci_execute($stmt);
		$executed = oci_execute($stmt);
		if($executed){

		}else{
			$e = oci_error($stmt);
			oci_close($conn);
			// return $data = array(
			// 	'errorCode'	=> false,
			// 	'error'		=> $e,
			// 	'message'	=> 'SQL错误<br />' . $e['message'],
			// );
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $e,
					'message'	=> 'SQL错误<br />' . $e['message'],
				)
			);
		}

		$nrows = oci_fetch_all($stmt, $results);
		oci_close($conn);
		//获取键名
		$data = array();
		for ($i = 0; $i < $nrows; $i++) {
			foreach ($results as $key => $value) {
				$data[$i][strtolower($key)] = isset($value[$i]) ? $value[$i] : '';
			}
		}

		return $data;
	}

	// function findDb($sql='', $db_name=''){
	// 	$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

	// 	$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'], "AL32UTF8"); 
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

		$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'], "AL32UTF8");
		if (!$conn) {
			$e = oci_error();
			// return $data = array(
			// 	'errorCode'	=> false,
			// 	'error'		=> $e,
			// 	'message'	=> 'Db连接失败<br />' . $e['message'],
			// );
			$this->ajaxReturn(
				array(
					'code'		=> '-110',
					'error'		=> $e,
					'message'	=> 'Db连接失败<br />' . $e['message'],
				)
			);
		}
		$stmt = oci_parse($conn, $sql);
		$executed = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
		if($executed){

		}else{
			$e = oci_error($stmt);
			oci_close($conn);
			// return $data = array(
			// 	'errorCode'	=> false,
			// 	'error'		=> $e,
			// 	'message'	=> 'SQL错误<br />' . $e['message'],
			// );
			$this->ajaxReturn(
				array(
					'code'		=> '-1',
					'error'		=> $e,
					'message'	=> 'SQL错误<br />' . $e['message'],
				)
			);
		}
		$committed = oci_commit($conn);
		oci_close($conn);

		return $data = array(
			'errorCode' => true,
			'error'		=> '',
			'executed'	=> $executed,
			'committed'	=> $committed,
		);
	}

	//链接Db库  实现查询语句
	public function connDb($sql='', $db_name=''){
		$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'], "AL32UTF8");
		if (!$conn) {
			$e = oci_error();
			$this->ajaxReturn(
				array(
					'code'		=> '-110',
					'error'		=> $e,
					'message'	=> 'Db连接失败<br />' . $e['message'],
				)
			);
		}
		$stmt = oci_parse($conn, $sql);
		return $stmt;
	}

	//调用存储过程
	public function procDb($query='', $value='', $db_name=''){
		$db_name != '' ? ( $db_info = C($db_name) ) : ( $db_info = C('DEFAULT_DB') );

		$conn = oci_connect($db_info['DB_USER'], $db_info['DB_PWD'], $db_info['DB_HOST'] . '/' . $db_info['DB_NAME'], "AL32UTF8");
		if (!$conn) {
			$e = oci_error();
			// return $data = array(
			// 	'errorCode'	=> false,
			// 	'error'		=> $e,
			// 	'message'	=> 'Db连接失败<br />' . $e['message'],
			// );
			$this->ajaxReturn(
				array(
					'code'		=> '-110',
					'error'		=> $e,
					'message'	=> 'Db连接失败<br />' . $e['message'],
				)
			);
		}
		
		//创建调用语句  
		$statement = oci_parse($conn,$query); 
		//提供输入参数 
		$res = 'null return';//返回值可能出现负值，所以初始化时用负值 
		//绑定变量，接收返回的参数 
		oci_bind_by_name($statement,":res", $res, 32000); 
		oci_bind_by_name($statement,":value", $value); 
		//执行 
		oci_execute($statement);
		oci_close($conn);

		return json_decode($res,true);
	}

	//$arr需要拼接成insert的数组, $auto_detection是否自动检测function
	//拼接结果字符串"()VALUES()"
	//每一个字段可以是字符串或数组，数组为了标明字段值是什么属性，不传默认为Y：字符串，N表示数值，F表示函数：第一个参数为值即函数的第一个参数、第二个参数为F、第三个参数为函数名、第四个参数为函数的第二个参数
	public function arr_str_add($arr='', $auto_detection='N'){
		$admin_user = session('user');
		$arr['ownerid'] = $admin_user['id'];
		$arr['modifierid'] = $admin_user['id'];
		$arr['creationdate'] = 'sysdate';
		$arr['modifieddate'] = 'sysdate';

		$str_value = "(";
		$str_key = "(";
		foreach ($arr as $key => $value) {
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
					//自动检测函数
					if( strpos($value, "('") !== false && strpos($value, "')") !== false ){
						$str_value = $str_value . $value . ", ";
					}else if( strpos($value, "(select") !== false && strpos($value, ")") !== false ){
						$str_value = $str_value . $value . ", ";
					}else if($value == 'sysdate'){
						$str_value = $str_value . $value . ", ";
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

		$str = $str_key . "VALUES" . $str_value;

		return $str;
	}

	//构造二维数组插入语句  批量插入
	public function arrs_str_add($arrs='', $auto_detection='N', $table=''){
		$str = 'insert all';
		foreach ($arrs as $key => $arr) {
			$str .= '
			into ' . $table . $this->arr_str_add($arr, $auto_detection);
		}
		//$str = rtrim($str, ", ");
		$str .= ' select 1 from DUAL';
		return $str;
	}

	//构造分页limit
	public function db_limit($sql='', $start='0', $end='1', $orderby=''){
		return $r_sql = "
		select * 
		from 
		(
			select * 
			from 
			(" . $sql . ") limit_table_a
			where rownum <= " . $end . "
		) limit_table_b
		where rownum > " . $start . " " . $orderby;
	}

	//类似arr_str_add
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
					//自动检测函数
					if( strpos($value, "('") !== false && strpos($value, "')") !== false ){
						$str = $str . $key . " = " . $value . ", ";
					}else if( strpos($value, "(select") !== false && strpos($value, ")") !== false ){
						$str = $str . $key . " = " . $value . ", ";
					}else if($value == 'sysdate'){
						$str = $str . $key . " = " . $value . ", ";
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

	//构建mysql建表语句
	public function createTable($arr){

		return $arr;
	}

	//排序语句
	public function orderBy($arr){
		$sql = ' ';
		foreach ($arr as $key => $value) {
			$sql .= $value['field'] . ' ' . $value['sort'];

			if( isset($value['nulls'])){
				if($value['nulls'] == 'first'){
					$sql .= " nulls first";
				}else if($value['nulls'] == 'last'){
					$sql .= " nulls last";
				}
			}

			$sql .= ',';
		}

		return rtrim($sql, ',');
	}

	//nvl
	public function nvl(){
		return 'nvl';
	}
}