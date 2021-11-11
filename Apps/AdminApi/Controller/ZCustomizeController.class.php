<?php
/**
 *
 * ZCustomizeController.class.php
 *
 * 后台 - 定制化接口
 *
 * Mr.yh @ 2020.10.21
 * 
 */

namespace AdminApi\Controller;
use Think\Controller;

class ZCustomizeController extends CommController {

	//sku查询
	public function SkuArray(){
		$params = $this->_checkParam( array('p_product_id'=>'') );

		$DataBase = D(DBTYPE);

		$all_list = $DataBase->selectDb("select id,diff_type,diff_value from diff_ext where isactive='Y'");
		// $all_array = array( array(), );

		//颜色作为外层 type=10
		foreach ($all_list as $key => $value) {
			if($value['diff_type'] == '10'){
				$all_array[$value['id']] = array(
					'value'		=> $value['diff_value'],
					'list'		=> array(),
				);
			}
		}
		if( empty($all_array) ){
			$this->ajaxReturn( array('code'=>'-1', 'message'=>'未设置任何颜色！') );
		}
		//尺寸作为内层 type=20
		foreach ($all_list as $key => $value) {
			if($value['diff_type'] == '20'){
				foreach ($all_array as $keyt => $valuet) {
					$all_array[$keyt]['list'][$value['id']] = array(
						'value'		=> $value['diff_value'],
						'id'		=> '',
					);
				}
			}
		}

// 		$list = $DataBase->selectDb("select s.*,e10.diff_value as diff_10_value,e20.diff_value as diff_20_value
// from p_product_sku s 
// left join diff_ext e10 on s.diff_10_id=e10.id 
// left join diff_ext e20 on s.diff_20_id=e20.id 
// where s.p_product_id='" . $params['p_product_id'] . "'");
		$sku_list = $DataBase->selectDb("select s.id, s.diff_10_id, s.diff_20_id, s.p_product_id
from p_product_sku s 
where s.p_product_id='" . $params['p_product_id'] . "' and isactive='Y'");

		foreach ($sku_list as $key => $value) {
			if( isset($all_array[$value['diff_10_id']]['list'][$value['diff_20_id']]['id']) ){
				$all_array[$value['diff_10_id']]['list'][$value['diff_20_id']]['id'] = $value['id'];
			}
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('all_array'=>$all_array),) );
	}
	
	//sku生成
	public function editSkuArray(){
		$params = $this->_checkParam( array('p_product_id'=>'', 'list'=>'') );

		$new_sku_list = $params['list'];
		// $new_sku_obj = array();
		// foreach ($params['list'] as $key => $value) {
		// 	$new_sku_obj[$value['row']][$value['col']] = $value;
		// }

		$DataBase = D(DBTYPE);
		
		//已经存在的条码
		$sku_list = $DataBase->selectDb("select s.id, s.diff_10_id, s.diff_20_id, s.p_product_id, s.isactive
from p_product_sku s 
where s.p_product_id='" . $params['p_product_id'] . "'");
		
		//处理旧数组为二维对象 方便快速定位
		$old_sku_obj = array();
		foreach ($sku_list as $key => $value) {
			$old_sku_obj[$value['diff_10_id']][$value['diff_20_id']] = $value;
		}

		//
		$update_isactive_id = array(
			'Y'	=> array(),
			'N'	=> array(),
		);

		$insert_data = array();
		foreach ($new_sku_list as $key => $value) {
			//如果旧的存在
			if( isset($old_sku_obj[$value['row']][$value['col']]) ){
				//如果是不可用  先记录  等会更新可用状态
				if( $old_sku_obj[$value['row']][$value['col']]['isactive'] != 'Y' ){
					$update_isactive_id['Y'][] = $old_sku_obj[$value['row']][$value['col']]['id'];
				}
				//移除新旧两个 新的数组做新增（存在即不新增），旧的数组做作废（新的也有责不作废）
				unset($old_sku_obj[$value['row']][$value['col']]);
				unset($new_sku_list[$key]);
			}else{
				$insert_data[$key]['id'] = "get_sequences('p_product_sku')";
				$insert_data[$key]['isactive'] = "Y";
				$insert_data[$key]['p_product_id'] = $params['p_product_id'];
				$insert_data[$key]['diff_10_id'] = $value['row'];
				$insert_data[$key]['diff_20_id'] = $value['col'];
			}
		}

		//循环剩余的旧对象 找出需要作废的id
		foreach ($old_sku_obj as $key => $value) {
			foreach ($value as $keyt => $valuet) {
				$update_isactive_id['N'][] = $valuet['id'];
			}
		}

		if( !empty($insert_data) ){
			$res['insert_sql'] = $DataBase->arrs_str_add($insert_data, 'Y', 'p_product_sku');
			$res['insert_sql_res'] = $DataBase->updateDb($res['insert_sql']);
		}
		if( !empty($update_isactive_id['Y']) ){
			$res['upd_data_Y'] = "update p_product_sku set isactive='Y' where id in (" . join($update_isactive_id['Y'], ',') . ')';
			$res['upd_data_Y_res'] = $DataBase->updateDb($res['upd_data_Y']);
		}
		if( !empty($update_isactive_id['N']) ){
			$res['upd_data_N'] = "update p_product_sku set isactive='N' where id in (" . join($update_isactive_id['N'], ',') . ')';
			$res['upd_data_N_res'] = $DataBase->updateDb($res['upd_data_N']);
		}

		$this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array('res'=>$res), ) );
	}

	//活动cdk生成
	public function cdk(){
		$data = $this->_checkParam( array('promo_type'=>'', 'id'=>'') );

		if(session('cdk_sess')){
			$cdk_sess = session('cdk_sess');
			if($cdk_sess == 'Y'){
				session('cdk_sess', 'N');
				$this->ajaxReturn(array('code'=>'-1', 'message'=>'查询中，请稍后'));
			}
		};
		session('cdk_sess', 'Y');

		$DataBase = D(DBTYPE);

		if($data['promo_type'] == 'CRM_PROMO_LOTTERY'){
			$cdk_counts_res = $DataBase->selectDb("select (select sum(coupon_qty) from crm_promo_lottery_coupon where crm_promo_lottery_id=" . $data['id'] . ") - (select count(1) from crm_promo_cdk where crm_promo_type='CRM_PROMO_LOTTERY' and crm_promo_id=" . $data['id'] . ") as counts from dual");
			$cdk_counts = $cdk_counts_res[0]['counts'];

			if($cdk_counts > 0){
				$cdk_arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
				$cdk_arr_count = count($cdk_arr);

				$_cdk_nums = 1;
				while ($_cdk_nums <= $cdk_counts) {
					usleep(128);
					// $_time = microtime(true) / 10;
					$_time = microtime(true);
					// $r_time1[] = $_time;
					$_time_explode = explode('.', $_time);
					$_time = $_time_explode[1] . time() . $data['id'];
					// $r_time[] = $_time;
					$_cdk_str = '';
					while ( $_time >= $cdk_arr_count ) {
						$_remainder = $_time % $cdk_arr_count;
						$_time = floor($_time / $cdk_arr_count);
						$_cdk_str .= $cdk_arr[$_remainder];
						if( strlen($_cdk_str) < 16 && $_time < $cdk_arr_count ){
							$_time = $cdk_arr_count + mt_rand(0, $cdk_arr_count);
						}else if( strlen($_cdk_str) >= 16 ){
							$_time = $cdk_arr_count - 1;
						}
					}

					$cdks[] = $_cdk_str;
					$_cdk_nums ++;
				}

				$add_arr = array();
				foreach ($cdks as $key => $value) {
					// else{
						$add_arr[] = array(
							'id'				=> "get_sequences('crm_promo_cdk')",
							'isactive'			=> 'Y',
							'use_type'			=> 'N',
							'crm_promo_type'	=> $data['promo_type'],
							'crm_promo_id'		=> $data['id'],
							'cdk'				=> $value,
							'cdk_4'				=> substr($value, 0, 4) . '-' . substr($value, 4, 4) . '-' . substr($value, 8, 4) . '-' . substr($value, 12, 4),
						);
					// }
					if( (($key+1) % 100 == 0) || ($key+1) == count($cdks) ){
						$res[] = $DataBase->updateDb($DataBase->arrs_str_add($add_arr, 'Y', 'crm_promo_cdk'));
						$add_arr = array();
					}
				}
			}

			$list = $DataBase->selectDb("select id,isactive,use_type,crm_promo_type,crm_promo_id,cdk,cdk_4 from crm_promo_cdk where crm_promo_id=" . $data['id'] . " and crm_promo_type='" . $data['promo_type'] . "' order by id asc");
		}

		session('cdk_sess', 'N');

		$this->ajaxReturn( 
			array(
				'code'		=> '1', 
				'message'	=> '保存成功', 
				'data'		=> array(
					'add_arr'	=> $add_arr,
					'res'		=> isset($res) ? $res : array(),
					'list'		=> $list,
				),
			)
		);
		// $this->ajaxReturn( array('code'=>'1', 'message'=>'ok', 'data'=>array($cdks, $r_time, $r_time1)) );
	}
}
