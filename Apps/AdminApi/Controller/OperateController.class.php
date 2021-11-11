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

class OperateController extends CommController {
	public function index(){
		$where = '';
		$keywords = I('post.keywords');
		$portal_ctl = I('post.portal_ctl');

		$where = ' where 1=1';
		$where_portal = ' and 1=1';
		foreach ($keywords as $key => $value) {
			// if($value != ''){
			// 	$where .= " and " . $key . " like '%" . $value . "%'"; 
			// }
			if($key == 'table_name'){
				$where_portal = " and (table_name like '%" . strtoupper($value) . "%' or table_name like '%" . strtolower($value) . "%')";
			}
		}

		//已记录的操作
		$DataBase = D(DBTYPE);
		$list = $DataBase->selectDb("select * from admin_operate" . $where);

		if( I('post.step') == 'over' ){
			$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('list'=>$list,),) );
		}

		$list_select = array();
		foreach ($list as $key => $value) {
			$list_select[$value['operate']] = $value;
		}

		//获取所有接口文件的class和function作为权限的基本单位
		$dir = "./Apps/AdminApi/Controller/";
		// Open a known directory, and proceed to read its contents
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' &&  $file != '..'){
						$file_name[] = $file;
					}
				} 
				closedir($dh);
			}
		}

		$dict_tables = $DataBase->selectDb("select * from dict_tables where isactive='Y'" . $where_portal . " order by id desc");
		if( !is_array($dict_tables) ){
			$dict_tables = array();
		}
		$authority_array = array();
		foreach ($file_name as $key => $value) {
			$myfile = fopen($dir . $value, "r") or die("Unable to open file!");

			$text = urldecode( fread($myfile,filesize($dir . $value)) );
			$closefile = fclose($myfile);

			//class
			$_class_contrl = explode('class ', $text);
			$_class_all = explode('Controller', $_class_contrl[1]);
			$_class = $_class_all[0];
			if($_class == 'Portal'){
				if($portal_ctl != 'page'){
					foreach ($dict_tables as $keytt => $valuett) {
						//mysql查出来是小写表名
						$_table_name = strtoupper($valuett['table_name']);
						$authority_array[$_class . '/table_name/' . $_table_name]['class'] = $_class . '/table_name/' . $_table_name;

						//function
						$_function_array = explode('public function ', $text);
						foreach ($_function_array as $keyt => $valuet) {
							if( strpos($valuet, '){') !== false && strpos($valuet, "'){'") === false ){
								$_function = strtok($valuet, '(');
								if( ($_function == 'index' || $_function == 'edit') ){
									$_function_value = '';
									if( isset($list_select[$_class . "/table_name/" . $_table_name . '/' . $_function]) ){
										$_function_value = $list_select[$_class . "/table_name/" . $_table_name . '/' . $_function];
									}
									$authority_array[$_class . '/table_name/' . $_table_name]['function'][$_function] = $_function_value;
								}
							}
						}

						//相同class名下的静态html
						$_html = array();
						$_dir = "./Public/Admin/" . $_class . "/";
						// Open a known directory, and proceed to read its contents
						if (is_dir($_dir)) {
							if ($dh = opendir($_dir)) {
								while (($file = readdir($dh)) !== false) {
									if($file != '.' &&  $file != '..'){
										$_html[] = $_class . "/" . $file . '?table_name=' . $_table_name;
									}
								} 
								closedir($dh);
							}
						}	
						$authority_array[$_class . '/table_name/' . $_table_name]['html'] = $_html;
					}
				}
			}else if( $portal_ctl == 'page' || ($portal_ctl == 'all' && $keywords['table_name'] == '') ){
				if( $_class != 'Comm' && $_class != 'Comms' && $_class != 'Login' && $_class != 'SetConfig' ){
					$authority_array[$_class]['class'] = $_class;
					
					//function
					$_function_array = explode('public function ', $text);
					foreach ($_function_array as $keyt => $valuet) {
						if( strpos($valuet, '){') !== false && strpos($valuet, "'){'") === false ){
							$_function = strtok($valuet, '(');
							$_function_value = '';
							if( isset($list_select[$_class . '/' . $_function]) ){
								$_function_value = $list_select[$_class . '/' . $_function];
							}
							$authority_array[$_class]['function'][$_function] = $_function_value;
						}
					}

					//相同class名下的静态html
					$_html = array();
					$_dir = "./Public/Admin/" . $_class . "/";
					// Open a known directory, and proceed to read its contents
					if (is_dir($_dir)) {
						if ($dh = opendir($_dir)) {
							while (($file = readdir($dh)) !== false) {
								if($file != '.' &&  $file != '..'){
									$_html[] = $_class . "/" . $file;
								}
							} 
							closedir($dh);
						}
					}	
					$authority_array[$_class]['html'] = $_html;
				}
			}
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('list'=>$authority_array, 'where'=>$where, 'where_portal'=>$where_portal, 'dict_tables'=>$dict_tables),) );
	}

	public function edit(){
		//必填项
		$data = $this->_checkParam( array('id'=>'', 'operate'=>'', 'title'=>'') );

		//非必填项
		$data['html_url'] = I('post.html_url');

		$id = $data['id'];
		unset($data['id']);

		$isactive = I('post.isactive');
		if($isactive != ''){
			$data['isactive'] = $isactive;
		}

		$DataBase = D(DBTYPE);
		if($id == 'new'){
			$data['id'] = "get_sequences('admin_operate')";
			$res = $DataBase->updateDb("insert into admin_operate " . $DataBase->arr_str_add($data, 'Y'));
		}else{
			$res = $DataBase->updateDb("update admin_operate set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
		}

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, ),) );
	}

	public function menu(){
		$DataBase = D(DBTYPE);

		$list = $DataBase->selectDb("
			select t.title as operate_title, t.html_url, m.title, m.parent_id, m.icon, m.id, t.id as admin_operate_id, m.isactive, m.seq, m.show_type
			from admin_operate t 
			left join admin_menu m on t.id=m.admin_operate_id
			where t.html_url is not null and t.html_url not like 'Portal/info.html?table_name=%'
			order by" . $DataBase->orderBy( array(array('field'=>'m.isactive', 'sort'=>'desc', 'nulls'=>'last',),array('field'=>'m.parent_id', 'sort'=>'asc',),array('field'=>'m.seq', 'sort'=>'asc', 'nulls'=>'last',),) )
		);

		// $list1 = $DataBase->selectDb("select * from dict_tables where is_menu=Y' and isactive='Y'");
		// foreach ($list1 as $key => $value) {
			// $list[] = array(
			// 	'operate_title'	=> ,
			// 	'html_url'	=> ,
			// 	'title'	=> ,
			// 	'parent_id'	=> ,
			// 	'operate_title'	=> ,
			// );
		// }

		$parent = $DataBase->selectDb("select * from admin_menu where parent_id = 0 and isactive='Y' order by" . $DataBase->orderBy( array(array('field'=>'seq', 'sort'=>'asc', 'nulls'=>'last',),) )
		);

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('list'=>$list, 'parent'=>$parent), ) );
	}

	public function editMenu(){
		//必填项
		$data = $this->_checkParam( array('id'=>'', 'title'=>'', 'parent_id'=>'') );

		//非必填项
		$data['icon'] = I('post.icon');
		$data['seq'] = I('post.seq');

		$isactive = I('post.isactive');
		if($isactive != ''){
			$data['isactive'] = $isactive;
		}

		$show_type = I('post.show_type');
		if($show_type != ''){
			$data['show_type'] = $show_type;
		}

		$data['admin_operate_id'] = I('post.admin_operate_id');
		if($data['parent_id'] > 0 && $data['admin_operate_id'] == ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'参数错误！') );
		} 

		$id = $data['id'];
		unset($data['id']);

		//插入/更新
		$DataBase = D(DBTYPE);
		if($id == 'new'){
			$data['id'] = "get_sequences('admin_menu')";
			$res = $DataBase->updateDb("insert into admin_menu " . $DataBase->arr_str_add($data, 'Y'));
		}else{
			$res = $DataBase->updateDb("update admin_menu set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id);
		}

		if($res['error'] != ''){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>$res['message'], 'data'=>array('res'=>$res, ),) );
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'提交成功', 'data'=>array('res'=>$res, 'ee'=>"update admin_menu set " . $DataBase->arr_str_upd($data, 'Y') . " where id = " . $id),) );
	}
}