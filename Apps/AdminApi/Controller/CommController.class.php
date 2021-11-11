<?php
/**
 *
 * CommController.class.php
 *
 * 后台接口模块基类
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class CommController extends CommsController {
	public function _initialize(){
		//记录相关信息
		// if(!IS_AJAX){
		// 	session('url', $_SERVER['REQUEST_URI']);
		// }

		//校验权限
		$this->_checkAuthor();
	}

	//后台获取菜单列表
	public function getMenu(){
		$DataBase = D(DBTYPE);

		$user = session('user');
		if($user['username'] == 'root'){
			$list = $DataBase->selectDb("
select m.*, o.html_url 
from admin_menu m
left join admin_operate o on o.id=m.admin_operate_id
where m.isactive='Y'
order by" . $DataBase->orderBy( array(array('field'=>'m.seq', 'sort'=>'asc', 'nulls'=>'last',),) )
			);
		}else{
			$authority = $DataBase->selectDb("
select o.id, o.html_url, o.operate, ga.data_authority
from admin_operate o 
left join admin_user_authority ga on ga.admin_operate_id=o.id and ga.isactive='Y' 
left join admin_user_group g on g.id=ga.admin_user_group_id and g.isactive='Y' 
left join admin_user_group_user gu on gu.admin_user_group_id=g.id and gu.isactive='Y' 
left join admin_user u on u.id=gu.admin_user_id and u.isactive='Y' 
where o.isactive='Y' and u.id=" . $user['id']);
			session('authority', $authority);

			$list = $DataBase->selectDb("
select m.*, o.html_url 
from(
	select * from admin_menu where isactive='Y' and (parent_id='0' or admin_operate_id in (" . rtrim(join_value(',', $authority, 'id'), ',') . "))
) m
left join admin_operate o on o.id=m.admin_operate_id
where m.isactive='Y'
order by" . $DataBase->orderBy( array(array('field'=>'m.seq', 'sort'=>'asc', 'nulls'=>'last',),) )
			);
		}

		$menu = get_trees($list, '0', '', '2');

		if($user['username'] != 'root'){
			foreach ($menu as $key => $value) {
				if( !isset($value['childs']) ){
					unset($menu[$key]);
				}
			}
		}

		// $company = D('AccessToken')->getAccessToken();
		$company = array('company_title'=>C('COMPANY_NAME'));
		$admin_name = array('admin_name'=>C('ADMIN_NAME'));

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('menu'=>$menu, 'admin_user'=>$user, 'company'=>$company['company_title'], 'admin_name'=>$admin_name['admin_name'], 'list'=>$list, ), ) );
	}

	private function _checkAuthor(){
		$_url = $_SERVER['REQUEST_URI'];

		$user = session('user');
		if( $user ){
			$this->_c_data_authority = '';
			if($user['username'] == 'root'){

			}else if( strpos($_url, '/adminApi/Comm/getMenu') !== false || strpos($_url, '/adminApi/comm/getMenu') !== false ){

			}else{
				if( strpos($_url, '/adminApi/Portal/index') !== false || strpos($_url, '/adminApi/portal/index') !== false ){
					$_table_name = $this->tableExplode($_SERVER['HTTP_REFERER']);
					$_url = 'Portal/table_name/' . $_table_name['table_name'] . '/index';
					$_authority_url = 'Portal/table_name/' . $_table_name['table_name'] . '/index';
				}else if( strpos($_url, '/adminApi/Portal/info') !== false || strpos($_url, '/adminApi/portal/info') !== false ){
					$_table_name = $this->tableExplode($_SERVER['HTTP_REFERER']);
					$_url = 'Portal/table_name/' . $_table_name['table_name'] . '/index';
					$_authority_url = 'Portal/table_name/' . $_table_name['table_name'] . '/edit';
				}else if( strpos($_url, '/adminApi/Portal/edit') !== false || strpos($_url, '/adminApi/Portal/editInfo') !== false || strpos($_url, '/adminApi/portal/edit') !== false || strpos($_url, '/adminApi/portal/editInfo') !== false || strpos($_url, '/adminApi/Portal/insertAll') !== false || strpos($_url, '/adminApi/portal/insertAll') !== false ){
					$_table_name = $this->tableExplode($_SERVER['HTTP_REFERER']);
					$_url = 'Portal/table_name/' . $_table_name['table_name'] . '/edit';
					$_authority_url = 'Portal/table_name/' . $_table_name['table_name'] . '/edit';
				}
				$authority = session('authority');
				$i = 0;
				foreach ($authority as $key => $value) {
					if( strpos($value['operate'], $_url) !== false || strpos($_url, $value['operate']) !== false ){
						$i ++;
					}
					if( strpos($value['operate'], $_authority_url) !== false || strpos($_authority_url, $value['operate']) !== false ){
						//数据权限控制
						$_data_authority = $value['data_authority'];
						$_data_authority = json_decode($_data_authority, true);
						$data_authority = array();
						if( is_array($_data_authority) ){
							foreach ($_data_authority as $key => $value) {
								$_val = ' ';
								// foreach ($value['val'] as $keyt => $valuet) {
									if($value['mod'] == 'in' || $value['mod'] == 'not in'){
										$_val .= $value['mod'] . " ('" . rtrim( join_value("','", $value['val'], ''), "','" ) . "')";
									}else if($value['mod'] == 'like'){
										$_val .= $value['mod'] . " '%" . $value['val'][0] . "%'";
									}else if($value['mod'] == 'eq'){
										$_val .= "='" . $value['val'][0] . "'";
									}else if($value['mod'] == 'neq'){
										$_val .= "!='" . $value['val'][0] . "'";
									}else if($value['mod'] == 'gt'){
										$_val .= ">" . $value['val'][0];
									}else if($value['mod'] == 'lt'){
										$_val .= "<" . $value['val'][0];
									}else{
										$_val .= '';
									}
								// }
								$value['where'] = $_val;
								$data_authority[$value['tab']][$value['col']] = $value;
							}
						}
						$this->_c_data_authority = $data_authority;
					}
				}

				//无权限
				if($i <= 0){
					$this->ajaxReturn( array(
						'code'		=> '-1', 
						'message'	=> '权限不足', 
						'data'		=> array(
							'user'		=> $user, 
							'url'		=> $_url, 
							'authority'	=> $authority,
						), 
					) );
				}

			}
		}else{
			$DataBase_config = C('DEFAULT_DB');
			if( $DataBase_config['DB_HOST'] == '' || $DataBase_config['DB_USER'] == '' ){
				$this->ajaxReturn( array('code'=>'110', 'message'=>'请先进行数据库配置', 'data'=>array('url'=>$_url), ) );
			}else{
				$this->ajaxReturn( array('code'=>'706', 'message'=>'请先登录', 'data'=>array('url'=>$_url), ) );
			}
		}
	}

	//返回？后面的参数的  返回数据格式：对象 array(参数名=>参数)
	private function tableExplode($str=''){
		$_table_name_pre = explode('?', $str);
		$_table_name = explode('&', $_table_name_pre[1]);
		foreach ($_table_name as $key => $value) {
			$_explode = explode('=', $value);
			$_table_name[$_explode[0]] = $_explode[1];
		}
		return $_table_name;
	}
}