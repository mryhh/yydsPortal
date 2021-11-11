<?php
/**
 *
 * IndexController.class.php
 *
 * 后台接口模块 - 配置项初始化及管理model
 *
 * Mr.yh @ 2020.10.21
 * 
 */
 
namespace AdminApi\Model;

class AdminConfigModel{
	public function params($param='', $param_value=''){
		//获取
		if($param_value == ''){
			if($param == ''){
				// $value = '';
				$info = D(DBTYPE)->selectDb("select * from admin_config");
				foreach ($info as $key => $value) {
					$list[$value['config']] = $value;
				}
				$_init = $this->initParams();
				foreach ($_init as $key => $value) {
					$param_value[$key] = $value;
					if( isset($list[$key]) ){
						$param_value[$key] = $list[$key];
					}
				}
			}else{
				$info = D(DBTYPE)->selectDb("select * from admin_config where config = '" . $param . "'");
				if( !empty($info) && isset($info[0]['value']) ){
					$param_value = $info[0]['value'];
				}else{
					$_init = $this->initParams();
					if( isset($_init[$param]) ){
						$param_value = $_init[$param]['value'];
					}
				}
			}
		}else{

		}

		return $param_value;
	}

	public function initParams(){
		return array(
			'card_type'				=> array(
				'title'					=> '开卡约定，线上开卡同步到线下(crm)(默认)或者是线下开卡回传到线上(portal)',
				'value'					=> 'crm',
			),
			'card_unique_columns'	=> array(
				'title'					=> '开卡约定，开卡时判断是否给用户做开卡动作的约束字段',
				'value'					=> 'mobileno',
			),
			'card_unique_col_same'	=> array(
				'title'					=> '小程序和卡包字段冲突时的处理方式，以授权为依据wxmp,以卡包为依据card',
				'value'					=> 'wxunionid',
			),
			'card_sync_url'			=> array(
				'title'					=> '开卡约定，线上开卡后调用，将卡信息发送到线下的接口地址',
				'value'					=> 'http://10.100.21.82:8005/servlets/binserv/newcrm.VipAdd',
			),
			'card_sync_columns'		=> array(
				'title'					=> '开卡约定，线上开卡后发送到线下需要传递的参数,JSON串，格式为{"table1":[{"col1":""}],"table2":[{"col2":""},{"col3":""}]}',
				'value'					=> '{"crm_vip_info":[{"wxmpno":"xcx_openid","birthday":"birthday","sex":"sex","undef1":"remark","nickname":"nickname","headimg":"headimg","wxunionid":"unionid","oldcode":"cardno","shopcode":"shopcode","createdate":"createDate","wxno":"gzh_openid","mobileno":"mobile","servicesaler":"salerNo", "name":"name", "isactive":"isactive"}], "crm_rule_vip_grade":[{"id_source_1":"c_viptype_id"}]}',
			),
			'card_sync_out_url'		=> array(
				'title'					=> '开卡约定，线下进行开卡并将会员信息回传的接口地址',
				'value'					=> '',
			),
			'card_sync_out_columns'	=> array(
				'title'					=> '开卡约定，线下开卡并将会员信息回传时的参数,JSON串，格式为{"table1":[{"col1":""}],"table2":[{"col2":""},{"col3":""}]}',
				'value'					=> '{"crm_vip_info":[{"oldcode":""},{"regtime":""}]}',
			),
			'card_sync_step'		=> array(
				'title'					=> '同步到线下的阶段（线上开卡后立刻同步填入add, 使用卡号时时再传递填入use）',
				'value'					=> 'use',
			),
			'coupon_sync_out_cols'	=> array(
				'title'					=> '优惠券同步核销系统接口需要传递的字段',
				'value'					=> '{"":[{"":""},{"":""}]}',
			),
			// 'subscribe'				=> array(
			// 	'title'					=> '关注',
			// 	'value'					=> '',
			// ),
			// 'integral_source'		=> array(
			// 	'title'					=> '用户积分来源（平台自身的积分字段或者提供一个接口去获取）',
			// 	'value'					=> 'crm',
			// ),
			// 'integral_sync_url'		=> array(
			// 	'title'					=> '积分同步到线下的接口',
			// 	'value'					=> '',
			// ),
			'source_columns'		=> array(
				'title'					=> '配置某些字段需要从外部获取',
				'value'					=> '{"crm_vip_info":{"integral":{"sync_url":"http://10.100.21.82:8005/servlets/binserv/newcrm.VipPoints","sync_cols_out":[{"table":"crm_vip_info","cols":"oldcode","cols_out":"cardno"}],"sync_cols_in":"points","name":"积分", "portal_name":"收银系统"},"coupon_num":{"sync_url":"https://crm.jinianri.com/sourceApi/couponCount","sync_cols_out":[{"table":"crm_vip_info","cols":"oldcode","cols_out":"cardno"},{"table":"crm_vip_info","cols":"mobileno","cols_out":"mobileno"}],"sync_cols_in":"coupon_num","name":"优惠券数量", "portal_name":"会员中心"}}}',
			),
			'wx_card_id'			=> array(
				'title'					=> '微信卡包卡号',
				'value'					=> 'ph3KgjtWl_E4yUgJc9iueVJJkVss',
			),
			'other_coupon'			=> array(
				'title'					=> '外部有效券',
				'value'					=> '',
			),
		);
	}
}