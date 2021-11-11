var crm = {
	api_local : 'https://crm.jinianri.com/WxProgram',
	app_local : 'https://crm.jinianri.com/WxProgram'
}

//修改 jq 的post请求
crm.post = function(url, data, callback){
	$.post(url, data, function(res){
		if(res.code == '706'){
			alert(res.message);
			// window.location.href = crm.app_local + '/Login/login.html';
		}else if(res.code > 0){
			if(res.message && res.message != 'ok'){
				alert(res.message);
			}
			callback(res.data);
		}else{
			alert(res.message);
		}
	});
}