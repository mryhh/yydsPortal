<?php
/******************************************************************
 *     _____ ____  ________________________  __________  __  ___  *
 *    / ___// __ \/ ____/_  __/ ____/ ____/ / ____/ __ \/  |/  /  *
 *    \__ \/ / / / /_    / / / /   / /_    / /   / / / / /|_/ /   *
 *   ___/ / /_/ / __/   / / / /___/ __/  _/ /___/ /_/ / /  / /    *
 *  /____/\____/_/     /_/  \____/_/    (_)____/\____/_/  /_/ 	  *
 ******************************************************************
 *
 * 功   能：全国驾培计时平台公共类
 * 创作时期：2017-01-04 09:31
 * 最后更新：2020-12-05 02:19 - Sam
 * 作   者: Sam<1245337070@qq.com>
 */

namespace Softcf\Train;
use Softcf\Curl\Curl;

class Train{
	private $version;
	private $timestamp;
	private $sign;
	private $user;
	private $curl;
	private $provinceUrl;

	/**
	 * 初始化参数
	 *
	 * @param array $options   
	 * @param $options['version']    	
	 * @param $options['timestamp'] 
	 * @param $options['sign']    	
	 * @param $options['user']   
	 *  $this->user = '1588B949DF9';	 //北京测试证书序列号
	 *	$this->user = '15BF08D85E8';     //新疆正式证书序列号
	 *	$this->user = '176316DC3C3';   	 //北京正式证书序列号
	 *	$this->user = '16A060C944F';   	 //内蒙通辽正式证书序列号	
	 *	$this->user = '16C21F9C588';  	 //内蒙兴安盟正式证书序列号		
	 *	$this->user = '177C8F409C1'; 	 //北京正式货运证书序列号
	 */
	public function __construct($options=null) {
		$this->version = '1.0.0.e1';
		$this->timestamp = getFormatSecond();
		$this->curl = new Curl();
		$this->curl->setHeader('Content-Type', 'application/json');

		if ($_SESSION['user'][ERP_NAME]['signConfig']['countryAddress']) {
			$this->countryUrl = $_SESSION['user'][ERP_NAME]['signConfig']['countryAddress'];
		}else{
			$this->countryUrl = 'http://114.55.89.156:8085/top/';
		}
		$this->userTruck = '177C8F409C1';   	
		$sing_config = array('6','7');
		if (in_array($_SESSION['user'][ERP_NAME]['signConfig']['id'], $sing_config, TRUE)){
			$this->sign = new \Softcf\Sign\Sign();
			$this->user = '16C21F9C588';   	 
		}else{
			$this->sign = new \Softcf\Sign\Sign('AHSL.pfx','20201205');
			$this->user = '176316DC3C3'; 
		}
	}

	/**
	*获取sign 
	*/
	function getSign($data,$type)
	{
		// unset($data["pdfid"]);
		// unset($data["esignature"]);
		// $sign = new \Softcf\Sign\Sign($pfxPath,$password);
		return $this->sign->getSign($data,$this->timestamp,$type);
	}

	/**
	*获取sign 
	*/
	function getTruckSign($data,$type)
	{
		$sign = new \Softcf\Sign\Sign('anhuiyunzheng.pfx','AHYZ2314');
		return $sign->getTruckSign($data,$this->timestamp,$type);
	}

	/**
	*获取key公钥
	*/
	function getPublicKey($key,$pwd,$keyname)
	{
		// $sign   = new \Softcf\Sign\Sign();
		$pubkey = $this->sign->getPublicKey($key,$pwd,$keyname);
		return $pubkey;
	}

	/**
	*加盖pdf公章
	*/
	function getPdf($data)
	{
		// $sign   = new \Softcf\Sign\Sign();
		$pdf = $this->sign->getPdf($data);
		$result = json_decode($pdf,true);
		return $result;
	}

	/**
	*base64换成图片
	*/
	function getBase64($base64,$path)
	{
		// $sign   = new \Softcf\Sign\Sign();
		$result = $this->sign->getBase64($base64,$path);
		return $result;
	}

	//---------------------------------------文件上传相关START---------------------------------------
	/**
	 * 科目二三文件资料信息上传返回照片文件ID
	 * type 文件业务类型
	 * picpath 	 	图片路径
	 * picname 	 	图片名称
	 * servname	 	文件业务类型
	 * domain 		1全国平台 2省平台
	 */
	function getFile($picpath,$picname,$servname,$domain=2,$signconfigid,$code)
	{	
		if($_SESSION['user'][ERP_NAME]['school']['is_platform']==2){
			$photo['errorcode'] = 0;
			$photo['data']['id'] = 0;
			return $photo;
		}
		if($domain==2){
			// echo $domain = $this->provinceUrl.'imageup/';die;
			if($_SESSION['user'][ERP_NAME]['signConfig']['id']==3||$signconfigid==3){
				$domain = 'http://117.157.231.122:1236/jgptjk/imageup/'.$servname;
				$code = $code?$code:substr($_SESSION['user'][ERP_NAME]["school"]["cityid"], 0 , 4);
			}elseif($signconfigid==12){
				$domain = 'http://113.56.172.195:8080/imageup/';
				$code = -1;
			}else{
				$domain = $_SESSION['user'][ERP_NAME]['signConfig']['address'].'imageup/';
				$code = -1;
			}
		}else{
			if($_SESSION['user'][ERP_NAME]['signConfig']['id']==3||$signconfigid==3){
				$domain = 'http://117.157.231.123:1236/tybh/imageup/';
				$code = $code?$code:substr($_SESSION['user'][ERP_NAME]["school"]["cityid"], 0 , 4);
			}elseif($signconfigid==12){
				$domain = 'http://114.55.58.112:8085/imageup/';
				$code = -1;
			}else{
				$domain = $_SESSION['user'][ERP_NAME]['signConfig']['countryAddress'].'top/imageup/';
				$code = -1;
			}
		}

		$photo  = $this->sign->getFile($picpath,$picname,$servname,$this->timestamp,$domain,$this->user,'SHA1',$code);
		$result = json_decode($photo,true);
		// if($signconfigid==12){
		// 	echo '$picpath:'.$picpath.'<br/>';
		// 	echo 'picname:'.$picname.'<br/>';
		// 	echo 'domain:'.$domain.'<br/>';
		// 	echo 'timestamp:'.$this->timestamp.'<br/>';
		// 	echo 'user:'.$this->user.'<br/>';
		// 	echo 'code:'.$code.'<br/>';
		// 	print_r($result);
		// 	die;
		// }
		
		return $result;
	}

	/**
	 * 科目一四文件资料信息上传返回照片文件ID
	 * type 文件业务类型
	 * picpath 	 	图片路径
	 * picname 	 	图片名称
	 * servname	 	文件业务类型
	 * domain 		1全国平台 2省平台
	 */
	function getFileSourse($picpath,$picname,$servname,$domain=2,$signconfigid,$code)
	{	
		if($_SESSION['user'][ERP_NAME]['school']['is_platform']==2){
			$photo['errorcode'] = 0;
			$photo['data']['id'] = 0;
			return $photo;
		}
		$this->user = '176316DC3C3';
		if($domain==2){
			// echo $domain = $this->provinceUrl.'imageup/';die;
			if($_SESSION['user'][ERP_NAME]['signConfig']['id']==3||$signconfigid==3){
				$domain = 'http://117.157.231.122:1236/jgptjk/imageup/'.$servname;
				$code = $code?$code:substr($_SESSION['user'][ERP_NAME]["school"]["cityid"], 0 , 4);
			}else if($_SESSION['user'][ERP_NAME]['signConfig']['id']==12 || $signconfigid ==12){
				$domain = 'http://113.56.172.195:8080/imageup/';
				$code = -1;
			}else{
				$domain = 'http://47.98.237.105/imageup/';
				$this->user = '16C21F9C588';
				$code = -1;
			}
		}else{
			if($_SESSION['user'][ERP_NAME]['signConfig']['id']==3||$signconfigid==3){
				$domain = 'http://117.157.231.123:1236/tybh/imageup/';
				$code = $code?$code:substr($_SESSION['user'][ERP_NAME]["school"]["cityid"], 0 , 4);
			}else{
				$domain = 'http://114.55.58.112:8085/top/imageup/';
				$this->user = '16C21F9C588';
				$code = -1;
			}
		}
		$this->sign = new \Softcf\Sign\Sign();
		$photo  = $this->sign->getFile($picpath,$picname,$servname,$this->timestamp,$domain,$this->user,'SHA1',$code);
		$result = json_decode($photo,true);
		return $result;
	}

	/**
	 * 温州科目一四文件资料信息上传返回照片文件ID
	 * type 文件业务类型
	 * picpath 	 	图片路径
	 * picname 	 	图片名称
	 * servname	 	文件业务类型
	 * domain 		1全国平台 2省平台
	 */
	function getFileTrainimg($data,$servname,$domain=2,$scid)
	{	
		if($domain==2){
			$domain = $scid->address;
			$code = -1;
		}else{
			$domain = $scid->countryAddress.'top/';
			$code = -1;
		}
		$result = $this->postCurl("trainimginfo",$data,3,"post",1,"",$domain,$code);
		return $result;
	}

	/**
	 * 驾培机器人
	 * timestamp 	时间戳
	 * data 	 	数据
	 * pass	 		终端口令
	 * cert 	    终端证书
	 */
	public function getTrainsign($timestamp,$data,$pass,$cert)
	{
		// $sign   = new \Softcf\Sign\Sign();
		$result = $this->sign->getTrainsign($timestamp,$data,$pass,$cert);
		return $result;
	}
	//---------------------------------------文件上传相关END---------------------------------------

	//---------------------------------------培训机构相关、教学区域、收费标准START---------------------------------------
	/**
	 *新增培训机构(POST方法)
	 */
	public function createInstitution($data,$signConfig,$address="",$code){
		if ($signConfig->countryAddress) {
			$this->countryUrl = $signConfig->countryAddress;
		}else{
			$this->countryUrl = 'http://114.55.89.156:8085/top/';
		}
		$address = $signConfig->countryAddress;

		$result = $this->postCurl("institution",$data,1,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *备案及修改培训机构(POST方法)
	 *
	 */
	public function editInstitution($data,$address="",$code){
		$result = $this->postCurl("institution",$data,3,'post',1,'',$address,$code);
		return $result;
	}

	/**
	 *新增教学区域(POST方法)
	 */
	public function createRegion($data,$address="",$code){
		$result = $this->postCurl("region",$data,3,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *删除教学区域(DELETE方法)
	 *inscode 培训机构编号
	 *seq 教学区域编号
	 */
	public function Regiondel($data){
		$result = $this->postCurl($data["inscode"]."-region-".$data["seq"],$data,3,"delete",2,"region");
		return $result;
	}

	/**
	 *教学区域审核结果查询(GET方法)
	 *inscode 培训机构编号
	 *seq 教学区域编号(审核结果通知消息中的编号)
	 */
	public function RegionReview($data){
		$result = $this->postCurl($data["inscode"]."-regionreview-".$data["seq"],$data,3,"get",2,"regionreview");
		return $result;
	}


	/**
	 *备案及修改收费标准(POST方法)
	 */
	public function CharStandard($data){
		$result = $this->postCurl("CharStandard",$data,3);
		return $result;
	}
	
	/**
	 *删除收费标准(DELETE方法)
	 *inscode 培训机构编号
	 *seq 收费标准编号
	 */
	public function delCharStandard($data){
		$result = $this->postCurl($data["inscode"]."-CharStandard-".$data["seq"],$data,3,"delete",2,"charstandard");
		return $result;
	}


	//---------------------------------------培训机构相关、教学区域、收费标准相关END---------------------------------------



	//---------------------------------------教练员相关START---------------------------------------
	/**
	 *新增教练员(POST方法)
	 *如果有 (coachnum) 参数 -> 备案及修改教练员
	 */
	public function createCoach($data,$address="",$code){
		$result = $this->postCurl("coach",$data,1,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *编辑教练员(POST方法)
	 *如果有 (coachnum) 参数 -> 备案及修改教练员
	 */
	public function editCoach($data,$address="",$code){
		$result = $this->postCurl("coach",$data,3,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *删除教练员(DELETE方法)
	 *coachnum 教练员编号
	 */
	public function delCoach($data,$address="",$code){
		$result = $this->postCurl("coach-".$data["coachnum"],$data,3,"delete",2,"coach",$address,$code);
		return $result;
	}

	/**
	 *IC卡申请(POST方法)
	 *inscode 培训机构编号
	 *objtype 1学员	2教练
	 *objnum  对象全国编号
	 *cardid  物理卡ID
	 *fileid  申请表的ID
	 */
	public function applyCard($data){
		$result = $this->postCurl("applyCard",$data,3);
		return $result;
	}
	//---------------------------------------教练员相关END---------------------------------------


	//---------------------------------------考核员相关START---------------------------------------
	/**
	 *新增考核员(POST方法)
	 */
	public function createExaminer($data){
		$result = $this->postCurl("examiner",$data);
		return $result;
	}

	/**
	 *新增考核员(POST方法)
	 */
	public function editExaminer($data){
		$result = $this->postCurl("examiner",$data,3);
		return $result;
	}

	/**
	 *删除考核员(DELETE方法)
	 *examnum 考核员编号
	 */
	public function delExaminer($data){
		$result = $this->postCurl("examiner-".$data["examnum"],$data,3,"delete",2,"examiner");
		return $result;
	}
	//---------------------------------------考核员相关END---------------------------------------



	//---------------------------------------安全员相关START---------------------------------------
	/**
	 *新增安全员(POST方法)
	 */
	public function createSecurityguard($data){
		$result = $this->postCurl("securityguard",$data);
		return $result;
	}

	/**
	 *修改安全员(POST方法)
	 */
	public function editSecurityguard($data){
		$result = $this->postCurl("securityguard",$data,3);
		return $result;
	}

	/**
	 *删除安全员(DELETE方法)
	 */
	public function delSecurityguard($data){
		$result = $this->postCurl("securityguard-".$data["secunum"],$data,3,"delete",2,"securityguard");
		return $result;
	}
	//---------------------------------------安全员相关EDD---------------------------------------



	//---------------------------------------教练车相关START---------------------------------------
	/**
	 *新增教练车(POST方法)
	 */
	public function createTrainingCar($data,$address="",$code){
		$result = $this->postCurl("trainingcar",$data,1,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *修改教练车(POST方法)
	 */
	public function editTrainingCar($data,$address="",$code){
		$result = $this->postCurl("trainingcar",$data,3,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *删除教练车(DELETE方法)
	 *carnum 教练车编号
	 */
	public function delTrainingCardel($data,$address="",$code){
		$result = $this->postCurl("trainingCar-".$data["carnum"],$data,3,"delete",2,"trainingcar",$address,$code);
		return $result;
	}
	//---------------------------------------教练车相关END---------------------------------------



	//---------------------------------------计时终端相关START---------------------------------------
	/**
	 *新增计时终端(POST方法)
	 */
	public function createDevice($data,$address="",$code){
		$result = $this->postCurl("device",$data,1,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *修改计时终端(POST方法)
	 */
	public function editDevice($data,$address="",$code){
		$result = $this->postCurl("device",$data,3,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *删除计时终端(DELETE方法)
	 *devnum 计时终端编号
	 */
	public function delDevice($data,$address="",$code){
		$result = $this->postCurl("device-".$data["devnum"],$data,3,"delete",2,"device",$address,$code);
		return $result;
	}

	/**
	 *车载计程计时终端绑定(POST方法)
	 */
	public function devassign($data,$address="",$code){
		$result = $this->postCurl("devassign",$data,3,'post',1,"",$address,$code);
		return $result;
	}

	/**
	 *车载计程计时终端解绑(POST方法)
	 */
	public function devRembinding($data,$address="",$code){
		$result = $this->postCurl("devRembinding",$data,3,'post',1,"",$address,$code);
		return $result;
	}	

	//---------------------------------------计时终端相关END---------------------------------------




	//---------------------------------------学员相关START---------------------------------------
	/**
	 *新增学员(POST方法)
	 */
	public function createStudent($data,$address="",$code){
		$result = $this->postCurl("student",$data,1,"post",1,"",$address,$code);
		return $result;
	}

	/**
	 *修改学员(POST方法)
	 */
	public function editStudent($data,$address="",$code){
		$result = $this->postCurl("student",$data,3,"post",1,"",$address,$code);
		return $result;
	}

	/**
	 *查询学员编号(GET方法)
	 * cardnum 证件号码
	 * name 姓名
	 */
	public function stuinfo($data){
		$result = $this->postCurl(urlencode($data["cardnum"])."-stuinfo-".urlencode($data["name"]),$data,2,"get",2,"stuinfo");
		return $result;
	}

	/**
	 *注销学员(GET方法)
	 * stunum 学员统一编号
	 */
	public function delstudent($data,$address="",$code){
		$result = $this->postCurl("student-".urlencode($data["stunum"]),$data,2,"get",2,"delstudent",$address,$code);
		return $result;
	}
	/**
	 *电子合同上传接口定义(POST方法)
	 *stunum		学员统一编号
	 *inscode		培训机构统一编号
	 *totalamount	学驾总金额
	 *unitprice		单价学时
	 */
	public function editcontractUpload($data){
		$result = $this->postCurlFormData("contractUpload",$data,3);
		return $result;
	}

	//---------------------------------------学员培训过程信息START---------------------------------------
	/**
	 *学员资料查询(GET方法)
	 * stunum 学员编号
	 */
	public function stuinfoQuery($data){
		$result = $this->postCurl("stuinfoquery-".$data["stunum"],$data,3,"get",2,"stuinfoquery");
		return $result;
	}

	/**
	 *查询学员编号(GET方法)
	 * stunum 学员编号
	 */
	public function traininfo($data){
		$result = $this->postCurl("traininfo-".$data["stunum"],$data,3,"get",2,"traininfo");
		return $result;
	}

	/**
	 *学员跨培训机构备案(POST方法)
	 */
	public function transfer($data){
		$result = $this->postCurl("transfer",$data,3);
		return $result;
	}

	/**
	 *电子教学日志(POST方法)
	 */
	public function classRecord($data,$address="",$code){
		$result = $this->postCurl("classrecord",$data,3,"post",1,"",$address,$code);
		return $result;
	}

	/**
	 *阶段培训记录上报(POST方法)
	 */
	public function stageTrainningTime($data){
		$result = $this->postCurl("stagetrainningtime",$data,3);
		return $result;
	}

	/**
	 *补传三联单接口(POST方法)
	 * stunum  学员编号
	 * subject 培训科目
	 */
	// public function stuImagePdf($data){
	// 	$result = $this->postCurlFormData($data["stunum"]."-imagePdf-".$data["subject"],$data,3);
	// 	return $result;
	// }


	/**
	 *阶段学时审核结果查询(GET方法)
	 * stunum 学员编号
	 * subject 培训部分
	 */
	public function stageTrainningTimeReview($data,$address){
		$result = $this->postCurl($data["stunum"]."-stagetrainningtimereview-".$data["subject"],$data,3,"get",2,"stagetrainningtimereview",$address);
		return $result;
	}


	/**
	 *结业信息(POST方法)
	 */
	public function graduation($data){
		$result = $this->postCurl("graduation",$data,3);
		return $result;
	}

	/**
	 *结业信息查询接口(GET方法)
	 */
	// public function gradinfoQuery($data){
	// 	$result = $this->postCurlGradinfoQuery($data["name"]."-gradinfoquery-".$data["idcard"]."-".$data["traintype"],$data,2,"get",2,"gradinfoquery");
	// 	return $result;
	// }
	
	/**
	 *培训过程图片资料(POST方法)
	 */
	public function trainimgInfo($data){
		$result = $this->postCurl("trainimginfo",$data,3);
		return $result;
	}

	/**
	 *培训过程视频资料(POST方法)
	 */
	public function videoRecord($data){
		$result = $this->postCurl("videoRecord",$data,3);
		return $result;
	}

	//---------------------------------------培训过程相关END---------------------------------------




	//---------------------------------------评价信息、投诉信息START---------------------------------------
	/**
	 *评价信息交换(POST方法)
	 */
	public function evaluation($data){
		$result = $this->postCurl("evaluation",$data,3);
		return $result;
	}

	/**
	 *评价信息查询(GET方法)
	 * inscode 培训机构编号
	 * querydate 查询日期 YYYYMMDD
	 */
	public function evaluationQuery($data){
		$result = $this->postCurl($data["inscode"]."-evaluationquery-".$data["querydate"],$data,3,"get",2,"evaluationquery");
		return $result;
	}

	/**
	 *投诉信息交换(POST方法)
	 */
	public function complaint($data){
		$result = $this->postCurl("complaint",$data,3);
		return $result;
	}

	/**
	 *评价信息查询(GET方法)
	 * inscode 培训机构编号
	 * querydate 查询日期 YYYYMMDD
	 */
	public function complaintQuery($data){
		$result = $this->postCurl($data["inscode"]."-complaintquery-".$data["querydate"],$data,3,"get",2,"complaintquery");
		return $result;
	}

	//---------------------------------------评价信息、投诉信息END---------------------------------------

	//---------------------------------------审核结果消息通知、备案关系变更消息通知START---------------------------------------
	/**
	 *审核结果消息通知(POST方法)
	 */
	public function Reviewmsg($data){

	}

	/**
	 *备案关系变更消息通知(POST方法)
	 */
	public function recordChangeMsg($data){
		$result = $this->postCurl("recordchangemsg",$data,2);
		return $result;
	}

	//---------------------------------------审核结果消息通知、备案关系变更消息通知END---------------------------------------
	//--------------------------------------- 新增接口---------------------------------------
	/**
	 * 计时培训图片上传
	 * 2019-6-11
	 */
	public function jsimageup($data,$address="",$code){
		$result = $this->postCurl("Jsimageup/image",$data,3,"post",1,"",$address,$code);
		return $result;
	}

	//---------------------------------------甘肃结果查询START-------------------------
	/**
	 * 教练员通知结果查询
	 * 2020-9-7
	 */
	public function gscoach($data,$address="",$code){
		$result = $this->postCurl($data["coachnum"]."-coachreview",$data,3,"get",2,"coach",$address,$code);
		return $result;
	}

	public function gscar($data,$address="",$code){
		$result = $this->postCurl($data["carnum"]."-carreview",$data,3,"get",2,"car",$address,$code);
		return $result;
	}
	//---------------------------------------甘肃结果查询END-------------------------
	/**
	 *$this->postCurl方法
	 * name 	服务名
	 * data 	数据
	 * urltype  发送方（全国平台、省平台）
	 * type 	HTTP方法
	 * domain   参数/表域
	 * servname 业务名称
	 */
	function postCurl($name,$data="",$urltype=1,$type="post",$domain=1,$servname="",$address="",$code){
		// print_r($data);
		if($_SESSION['user'][ERP_NAME]['school']['is_platform']==2){
			$ret->errorcode = 0;
			return $ret;
		}
		$arr = array("post","get","delete","put","imgpost");
		if($address == 'http://47.98.237.105/'){
			$this->user = '16C21F9C588';
		}

		if(in_array($type,$arr)){

			$urlarr = array(
				//兴安盟
				1 => 'http://114.55.58.112:8085/top/',	//全国平台新增   获取唯一编号
				2 => 'http://114.55.58.112:8085/',		//全国平台查询
				3 => $_SESSION['user'][ERP_NAME]['signConfig']['address'] ? $_SESSION['user'][ERP_NAME]['signConfig']['address'] :  $address//省平台
			);
			if($_SESSION['user'][ERP_NAME]['signConfig']['id']==3||$address=='http://117.157.231.122:1236/jgptjk/'||$address=='http://117.157.231.123:1236/tybh/'){
				$urlarr = array(
					//甘肃
					1 => 'http://117.157.231.123:1236/tybh/',	//全国平台新增   获取唯一编号
					2 => 'http://117.157.231.123:1236/',		//全国平台查询
					3 => $_SESSION['user'][ERP_NAME]['signConfig']['address'] ? $_SESSION['user'][ERP_NAME]['signConfig']['address'] :  $address//省平台
				);
				if($urltype==3){
					if($code){
						$datacode = $code;
					}else{
						$datacode = substr($_SESSION['user'][ERP_NAME]["school"]["cityid"], 0 , 4);
					}
				}
			}

			if($domain != 1){
				$data = $this->doServiceName($servname,$data);
			}
			$url = $urlarr[$urltype].$name."?v=".$this->version."&ts=".$this->timestamp."&sign=".$this->getSign($data,$domain)."&user=".$this->user;
			
			if($datacode){
				$url = $url.'&code='.$datacode;
			}

			// if($address=='http://117.157.231.122:1236/jgptjk/'||$_SESSION['user'][ERP_NAME]['signConfig']['id']==3){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// }
			// if($name=='institution'){
			// 	echo $_SESSION['user'][ERP_NAME]['signConfig']['id'];
			// 	die;
			// }
			// if($name=='institution'){
				
			// 	print_r($data);
			// }
			// if($servname=='device'){
			// 	echo $url;
			// 	echo '<br/>';
			// 	echo json_encode($data);
			// 	echo '<br/>';
			// 	print_r($this->curl->response);
			// 	die;
			// }
			$rs = $this->curl;
			if($domain == 1){
				$rs->$type($url,$data);

			}else{
				$rs->$type($url);
			}
			// device student devassign institution stuinfoquery
			// if($name=='devRembinding'){
			// 	echo $url;
			// 	echo '<br/>';
			// 	echo json_encode($data);
			// 	echo '<br/>';
			// 	print_r($rs->response);
			// }
			
			if(($_SESSION['user'][ERP_NAME]['school']['cityid']=='420100'&&$urltype!=1)||$code=='420100'){
				$whrs = new Curl();
				$url = 'http://api.wuhanjpw.cn/'.$name."?v=".$this->version."&ts=".$this->timestamp."&sign=".$this->getSign($data,$domain)."&user=".$this->user;
				if($domain == 1){
					$whrs->$type($url,$data);

				}else{
					$whrs->$type($url);
				}

				// echo $url;
				// echo "'".json_encode($data)."'";

				// print_r($whrs->response);
			}

			// if($data['stunum']=='1591670469350219'){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// 	print_r($this->curl->response);
			// }


			// if($address=='http://117.157.231.122:1236/jgptjk/'||$_SESSION['user'][ERP_NAME]['signConfig']['id']==3){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// 	print_r($this->curl->response);
			// }
			// echo $url;
			// echo "'".json_encode($data)."'";
			// print_r($this->curl->response);
			// print_r($_SESSION['user'][ERP_NAME]['signConfig']);
			
			// if($_SESSION['user'][ERP_NAME]['signConfig']['id'] == 11){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// 	print_r($this->curl->response);
			// }
			// if($address){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// 	print_r($this->curl->response);
			// }
			// if($_SESSION['user'][ERP_NAME]['signConfig']['id'] == 7){
			// 	echo $url;
			// 	echo "'".json_encode($data)."'";
			// 	print_r($this->curl->response);
			// }
			
			if ($rs->error) {
				// echo $url;die;
				// print_r($this->curl);
				// die;
			    echo 'Error: ' . $rs->errorCode . ': ' . $rs->errorMessage;
			} else {
				if(is_string($rs->response)){
					// echo $url;
                    $rs->response = json_decode($rs->response);
                } 
			    return $rs->response;
			}
		}else{
			echo 'Error: 类型错误。';
		}
	}

	/**
	 * postCurlFormData方法
	 * name 	服务名
	 * data 	数据
	 * urltype  发送方（全国平台、省平台）
	 * type 	HTTP方法
	 * domain   参数/表域
	 * servname 业务名称
	 */
	function postCurlFormData($name,$data="",$urltype=1,$type="post",$domain=1,$servname="",$address=""){
		if($_SESSION['user'][ERP_NAME]['school']['is_platform']==2){
			$ret->errorcode = 0;
			return $ret;
		}
		$strData = $data;
		$arr = array("post","get","delete","put","imgpost");
		if($address == 'http://47.98.237.105/'){
			$this->user = '16C21F9C588';
		}
		if(in_array($type,$arr)){
			$urlarr = array(
				//兴安盟
				1 => 'http://114.55.58.112:8085/top/',	//全国平台新增   获取唯一编号
				2 => 'http://114.55.58.112:8085/',		//全国平台查询
				3 => $_SESSION['user'][ERP_NAME]['signConfig']['address'] ? $_SESSION['user'][ERP_NAME]['signConfig']['address'] :  $address//省平台
			);
			if($domain != 1){
				$data = $this->doServiceName($servname,$data);
			}
			$url = $urlarr[$urltype].$name."?v=".$this->version."&ts=".$this->timestamp."&sign=".$this->getSign($data,$domain)."&user=".$this->user;
			// echo $url;
			// echo "'".json_encode($data)."'";
			// die;
			$res = json_decode(httpFile($url, $data));
			return $res;
		}else{
			echo 'Error: 类型错误。';
		}
	}

	/**
	 * postCurlGradinfoQuery方法
	 * name 	服务名
	 * data 	数据
	 * urltype  发送方（全国平台、省平台）
	 * type 	HTTP方法
	 * domain   参数/表域
	 * servname 业务名称
	 */
	// function postCurlGradinfoQuery($name,$data="",$urltype=1,$type="post",$domain=1,$servname="",$address=""){
	// 	if($_SESSION['user'][ERP_NAME]['school']['is_platform']==2){
	// 		$ret->errorcode = 0;
	// 		return $ret;
	// 	}
	// 	$arr = array("post","get","delete","put","imgpost");
	// 	if($address == 'http://47.98.237.105/'){
	// 		$this->user = '16C21F9C588';
	// 	}
	// 	if(in_array($type,$arr)){
	// 		$urlarr = array(
	// 			//甘肃
	// 			1 => 'http://117.157.231.123:1236/tybh/',	//全国平台新增   获取唯一编号
	// 			2 => 'http://117.157.231.123:1236/',		//全国平台查询
	// 			3 => $_SESSION['user'][ERP_NAME]['signConfig']['address'] ? $_SESSION['user'][ERP_NAME]['signConfig']['address'] :  $address//省平台
	// 		);
	// 		if($domain != 1){
	// 			$data = $this->doServiceName($servname,$data);
	// 		}
	// 		$url = $urlarr[$urltype].$name."?v=1.0.0.e2"."&ts=".$this->timestamp."&sign=".$this->getTruckSign($data,$domain)."&user=".$this->userTruck;
	// 		echo $url;
	// 		echo "'".json_encode($data)."'";
	// 		die;
	// 		if($domain == 1){
	// 			$this->curl->$type($url,$data);
	// 		}else{
	// 			$this->curl->$type($url);
	// 		}

	// 		if ($this->curl->error) {
	// 		    echo 'Error: ' . $this->curl->errorCode . ': ' . $this->curl->errorMessage;
	// 		} else {
	// 			if(is_string($this->curl->response)){
 //                    $this->curl->response = json_decode($this->curl->response);
 //                } 
	// 		    return $this->curl->response;
	// 		}
	// 	}else{
	// 		echo 'Error: 类型错误。';
	// 	}
	// }

	function doServiceName($servname,$data)
	{
		switch($servname){
			//DELETE表域
			case 'region':
				$data = $data['inscode'].$data['seq'];
				break;
			case 'coach':
				$data = $data['coachnum'];
				break;
			case 'examiner':
				$data = $data['examnum'];
				break;
			case 'securityguard':
				$data = $data['secunum'];
				break;
			case 'trainingcar':
				$data = $data['carnum'];
				break;
			case 'car':
				$data = $data['carnum'];
				break;
			case 'charstandard':
				$data = $data['inscode'].$data['seq'];
				break;
			case 'device':
				$data = $data['devnum'];
				break;
			//GET表域
			case 'stuinfoquery':
				$data = $data['stunum'];
				break;
			case 'traininfo':
				$data = $data['stunum'];
				break;
			case 'stagetrainningtimereview':
				$data = $data['stunum'].$data['subject'];
				break;
			case 'evaluationquery':
				$data = $data['inscode'].$data['querydate'];
				break;
			case 'gradinfoquery':
				$data = $data['name'].$data['idcard'].$data['traintype'];
				break;	
			//教学区域查询	
			case 'regionreview':
				$data = $data['inscode'].$data['seq'];
				break;
			case 'stuinfo':
				$data = $data['cardnum'].$data['name'];
				break;
			case 'complaintquery':
				$data = $data['inscode'].$data['querydate'];
				break;
			case 'delstudent':
				$data = $data['stunum'];
				break;
			default:
				$data = $data['inscode'].$data['querydate'];
				break;
		}
		return $data;
	}


	//新疆电子签证
	public function XJCaSignature($data)
	{
		$url = 'http://124.117.245.67:8088/SealPic_WebService/services/SignPDFWebService/caSignature/';
		// $url = 'http://124.117.245.71:12345/SealPic_WebService/services/SignPDFWebService/caSignature/';

		$key = '7mx6Y81bMI';
		$path = $data['inscode'];
		$paramSign = json_encode($data);
		// $sign = new \Softcf\Sign\Sign();
		$longTime = $this->timestamp;
		$ret = json_decode($this->sign->XJCaSignature($url,$key,$path,$paramSign,$longTime),true);
		$ret['longTime'] = $longTime;
		return $ret;
	}

	//新疆验证电子签证
	public function XJCheckSign($signdata)
	{
		unset($signdata['caSerial']);

		$url = 'http://124.117.245.67:8088/SealPic_WebService/services/SignPDFWebService/checkSignature/';
		// $url = 'http://124.117.245.71:12345/SealPic_WebService/services/SignPDFWebService/checkSignature/';
		$key = '7mx6Y81bMI';
		$longTime = $this->timestamp;
		$paramSign = json_encode($signdata);
		// $sign = new \Softcf\Sign\Sign();
		return json_decode($this->sign->XJCheckSign($url,$key,$paramSign,$longTime),true);
	}

	//
	/**
	*新疆电子签章
	*/
	public function XJgetPdf($data)
	{
		// $sign   = new \Softcf\Sign\Sign();
		$pdf = $this->sign->XJgetPdf($data);
		$result = json_decode($pdf,true);
		return $result;
	}

	public function useJavaPack($data,$type = 'sign')
	{
		// $sign = new \Softcf\Sign\Sign();
		return json_decode($this->sign->useJavaPack($data,'sign'),true);
	}

}
?>