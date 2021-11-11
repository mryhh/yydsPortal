<?php

namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {

	public function index(){
		header('Location: Public/Admin/Index/index.html');
	}
}