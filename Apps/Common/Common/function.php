<?php

	function curls($method, $url, $data){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if($method == 'post'){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);		
		}
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_NOBODY, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl); 
		return $output;
	}

	/**
	* curl请求
	*/
	function bj_curls($method="", $url="", $data=""){
		$data = http_build_query($data);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded',
				'Cookie: JSESSIONID=431fl5cdb4hre',
			),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	/**
	* curl请求
	*/
	function jg_curls($method="", $url="", $data=""){
		$data = http_build_query($data);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
			),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	/**
	* curl请求
	*/
	function v3_curls($method="", $url="", $data="", $header=array()){
		$data = http_build_query($data);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => $header,
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}
	//异步curl
	// function my_curl_sync($server="10.11.114.33", $port = 8005, $path = "/servlets/binserv/PostPointsController?vipId=19999"){
	// 	//$server = "10.11.114.33";
	// 	// $server = "10.100.21.165";
	// 	//$port = 8005;
	// 	// $port = 80;

	// 	$data = "GET " . $path . " HTTP/1.0\r\n";
	// 	// $data = "GET /instore/active/test1 HTTP/1.0\r\n";
	// 	$data .= "Connection: Close\r\n";
	// 	$data .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n\r\n";

	// 	$start_time = time();
	// 	$fp = fsockopen($server, $port, '', '', 1);
	// 	if (!$fp) {
	// 		// echo "Connect Timeout.n";
	// 	} else {
	// 		stream_set_blocking($fp, 0);
	// 		stream_set_timeout($fp, 1);
	// 	}

	// 	fwrite($fp, $data);
	// 	// while (!feof($fp)) {
	// 	//	 // echo fgets($fp, 128);
	// 	// }
	// 	fclose($fp);
	// }

	/* 
	写一个二维数组排序算法函数，能够具有通用性，可以调用php内置函数 
	二维数组排序， $arr是数据，$keys是排序的健值，$order是排序规则，0是升序，1是降序 
	$k是指第二维数组的键名对应的键值来排列一维数组的位置; 
	*/  
	function array_sort($arr, $keys, $order=0) {  
		if (!is_array($arr)) {  
			return false;  
		}  
		$keysvalue = array();  
		foreach($arr as $key => $val) {  
			$keysvalue[$key] = $val[$keys];  
		}  

		if($order == 0){  
			asort($keysvalue);  
		}else{  
			arsort($keysvalue);  
		}  
		reset($keysvalue);//设定数组的内部指标到它的第一个元素  
		foreach($keysvalue as $key => $vals) {  
			$keysort[$key] = $key;  
		}  
		$new_array = array();  
		foreach($keysort as $key => $val) {  
			$new_array[$key] = $arr[$val];  
		}  
		return $new_array;  
	}

	function getsingles($data, $singles){ 
		$p = count($data); 
		$n = 0; 
		for ($i=0; $i < $p ; $i++) { 
			for($array = strtok($data, $singles); $array != ""; $array = strtok($singles)) 
			{ 
				$arr[$n] = $array; 
				$n++; 
			} 
		} 
		return $arr; 
	}

	function get_redis($name){
		$redis = new \Redis();
		//连接
		$redis->connect(RDS_HOST, RDS_PORT);
		$redis->auth(RDS_AUTH);

		return($redis->get($name));
	}

	function set_redis($name, $value, $expire){
		$redis = new \Redis();
		//连接
		$redis->connect(RDS_HOST, RDS_PORT);
		$redis->auth(RDS_AUTH);

		if($expire){
			$expire = RDS_TIME;
		}
		return($redis->set($name, $value, $expire));
	}

	function get_http_host(){
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = $protocol . $_SERVER[HTTP_HOST];

		return $url;
	}

	/**
	 * 递归方法获取 $data 中 $parents_tree 下的所有分类
	 * 
	 * @param $data				表内容
	 * @param $parents_tree		最上层分类（可多个） 数组arr={[0]=>{} [1]=>{} ...}
	 * @param $grade			等级
	 * @param $kind				返回类型 1=>一维数组， 2=>树形数组
	 * @return
	 */
	function get_trees($data, $parents, $grade='', $kind){
		//默认等级=0
		if($grade == ''){
			$grade = 0;
		}

		//当parent不是数组时   需要构造数组
		if(!is_array($parents))
		{
			$id = 0;
			$parents_tree = array();
			foreach ($data as $key => $value) 
			{
				if($value['parent_id'] == $parents)
				{
					$value['grade'] = (string)$grade;
					$parents_tree[$id] = $value;unset($data[$key]);
					$id ++;
				}
			}
		}
		else{
			$parents_tree = $parents;
		}
		
		//调用递归
		if($kind == 1){
			$type = 1;
			return arr_tree_recursion($data, $parents_tree, $type, $grade);
		}else if($kind == 2){
			return obj_tree_recursion($data, $parents_tree, $grade);
		}
	}

	function arr_tree_recursion($data, $parents_tree, $type, $grade){
		//退出
		if ($type == 0) 
			return $parents_tree;

		$new_tree = array();
		$grade++;
		$type = 0;
		$id = 0;

		foreach ($parents_tree as $key => $value) {

			$new_tree[$id] = $value;
			$id++;

			if($value['grade'] === (string)($grade-1)){
				foreach ($data as $keytwo => $valuetwo){
					if ($valuetwo['parent_id'] === $value['id']){
						$valuetwo['grade'] = (string)$grade;

						$new_tree[$id] = $valuetwo; unset($data[$keytwo]);

						$id++; $type = 1;
					}
				}
			}
		}
		return arr_tree_recursion($data, $new_tree, $type, $grade);
	}

	function obj_tree_recursion($data, $parents_tree, $grade){

		$grade++;

		foreach ($parents_tree as $key => $value) {
			$num = 0;
			foreach ($data as $keyt => $valuet) {
				if($value['id'] == $valuet['parent_id']){
					$parents_tree[$key]['childs'][] = $valuet;
					$num ++;
				}
			}
			if($num > 0){
				$parents_tree[$key]['childs'] = obj_tree_recursion($data, $parents_tree[$key]['childs'], $grade);
			}
		}
		return $parents_tree;
	}

	//模仿join对二维数组的key进行字符串拼接
	function join_key($single='', $arr=array()){
		if($single){

		}else{
			$single = ' ';
		}

		$res = '';
		foreach ($arr as $key => $value) {
			$res .= $key . $single;
		}

		return $res;
	}

	//模仿join对二维数组的value进行字符串拼接
	function join_value($single='', $arr=array(), $keys=''){
		if($single){

		}else{
			$single = ' ';
		}

		$res = '';
		foreach ($arr as $key => $value) {
			if($keys != ''){
				// foreach ($value as $keyt => $valuet) {
				// 	if( $keys == $keyt ){
				// 		$res .= $valuet . $single;
				// 	}
				// }
				if( isset($value[$keys]) ){
					$res .= $value[$keys] . $single;
				}
			}else{
				$res .= $value . $single;
			}
		}

		return rtrim($res, $single);
	}

	//二维数组的某字段 是否 是 某个字符串
	function arr_join_str($arr=array(), $str='', $keys=''){
		$num = 0;
		if($str){
			foreach ($arr as $key => $value) {
				foreach ($value as $keyt => $valuet) {
					if($keys!='' && $keyt == $keys){
						if($valuet == $str){
							$num ++;
						}
					}
				}
			}
		}
		return $num;
	}

	// 取出数组对象中的两个字符串相同的字符 和 不同的字符
	function same_str($arr=array(), $keys=''){
		if( count($arr) < 2 ){
			foreach ($arr as $key => $value) {
				$arr[$key]['same'] = '';
				$arr[$key]['different'] = '';
			}
			return $arr;
		}

		foreach ($arr as $key => $value) {
			if(isset($value[$keys])){
				$arr[$key]['different'] = mine_array_flip( preg_split('/(?<!^)(?!$)/u', $value[$keys] ) );
			}else{
				$arr[$key]['different'] = array();
			}
		}

		// $_different = $arr[0]['different'];
		foreach ($arr as $key => $value) {
			if( !isset($_different) ){
				$_different = $value['different'];
				$first_key = $key;
				break;
			}
		}

		foreach ($_different as $keyt => $valuet) {
			$_all_count = 0;
			$_same_count = 0;
			foreach ($arr as $key => $value) {
				$_all_count ++;
				if( isset($value['different'][$keyt]) ){
					$_same_count ++;
				}
				// if( $key != $first_key && isset($value['different'][$keyt]) ){
				// 	$arr[$key]['same'][$keyt] = $arr[$key]['different'][$keyt];
				// 	unset($arr[$key]['different'][$keyt]);
				// 	if( isset($_different[$keyt]) ){
				// 		$_same[$keyt] = $valuet;
				// 		unset($_different[$keyt]);
				// 	}
				// }
			}

			$logs[] = array($_all_count, $_same_count, $keyt);
			if( $_all_count == $_same_count ){
				$_same[$keyt] = $valuet;
				unset($_different[$keyt]);
				foreach ($arr as $key => $value) {
					$arr[$key]['same'][$keyt] = $arr[$key]['different'][$keyt];
					unset($arr[$key]['different'][$keyt]);
				}
			}
		}

		$arr[$first_key]['logs'] = $logs;
		$arr[$first_key]['same'] = $_same;
		$arr[$first_key]['different'] = $_different;

		// foreach ($arr as $key => $value) {
		// 	$arr[$key]['_same'] = join( mine_array_flip($arr[$key]['same']) );
		// 	$arr[$key]['_different'] = join( mine_array_flip($arr[$key]['different']) );
		// }
		
		return $arr;
	}

	//翻转 并保留所有键
	function mine_array_flip($arr=array()){	
		$res = array();
		foreach ($arr as $key => $value) {
			if( is_array($value) ){
				foreach ($value as $keyt => $valuet) {
					$res[$valuet] = $key;
				}
			}else{
				$res[$value][] = $key;
			}
		}
		return $res;
	}

	//反解多级树形数组
	function _reverse_tree($arr=array(), $child='children', $res=array()){
		$childs = array();
		foreach ($arr as $key => $value) {
			if( $value[$child] && !empty($value[$child]) ){
				$childs[] = $value[$child];
			}
			unset($value[$child]);
			// if($keys == ''){
				$res[] = $value;
			// }else if( is_array($keys) ){
			// 	// $_val = array();
			// 	// foreach ($value as $keyt => $valuet) {
			// 	// 	foreach ($keys as $keytt => $valuett) {
			// 	// 		$_val[$valuet]='';
			// 	// 	}
			// 	// }
			// }else{
			// 	$res[$value[$keys]][] = $value;
			// }
		}

		if( !empty($childs) ){
			foreach ($childs as $key => $value) {
				$res = _reverse_tree($value, $child, $res);
			}
		}
		// else{
			return $res;
		// }
	}
