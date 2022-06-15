<?php 
	$res = array('code'=>'-1', 'message'=>'系统异常');
	if(isset($e['file'])) {
		$res['file'] = $e['file'];
		$res['line'] = $e['line'];
	}

	if(isset($e['trace'])) {
		$res['trace'] = explode('#', ltrim($e['trace'], '#'));
	}
	echo json_encode($res);
?>
