layui.use(['layer','element','jquery'],function() {
	layer = layui.layer;
	element = layui.element;
	$ = layui.jquery;

	showMenu(); //加载左侧菜单

	//关闭tab清除记忆
	element.on('tabDelete(xbs_tab)', function(data){
		var id  = $(this).parent().attr('lay-id');
		xadmin.del_data(id);
		console.log(id);
	});
	//左侧菜单
	$('.left-nav #nav').on('click', 'li', function(event) {
		if($(this).parent().attr('id') == 'nav'){
			xadmin.set_cate_data({key:'f1', value:$('.left-nav #nav li').index($(this))})
			xadmin.set_cate_data({key:'f2', value:null})
			xadmin.set_cate_data({key:'f3', value:null})
		}
		if($(this).parent().parent().parent().attr('id') == 'nav'){
			xadmin.set_cate_data({key:'f2', value:$('.left-nav #nav li').index($(this))})
			xadmin.set_cate_data({key:'f3', value:null})
		}
		if($(this).parent().parent().parent().parent().parent().attr('id') == 'nav'){
			xadmin.set_cate_data({key:'f3', value:$('.left-nav #nav li').index($(this))})
		}
		if($('.left-nav').css('width') == '60px'){
			$('.left-nav').animate({width: '220px'}, 100);
			$('.page-content').animate({left: '220px'}, 100);
			$('.left-nav i').css('font-size','14px');
			$('.left-nav cite,.left-nav .nav_right').show();
		}

		if($(window).width() < 768){
			$('.page-content-bg').show();
		}

		$('.left-nav').find('a').removeClass('active');
		$(this).children('a').addClass('active');
		if($(this).children('.sub-menu').length){
			if($(this).hasClass('open')){
				$(this).removeClass('open');
				$(this).find('.nav_right').html('&#xe697;');
				$(this).children('.sub-menu').stop(true,true).slideUp();
				$(this).siblings().children('.sub-menu').slideUp();
			}else{
				$(this).addClass('open');
				$(this).children('a').find('.nav_right').html('&#xe6a6;');
				$(this).children('.sub-menu').stop(true,true).slideDown();
				$(this).siblings().children('.sub-menu').stop(true,true).slideUp();
				$(this).siblings().find('.nav_right').html('&#xe697;');
				$(this).siblings().removeClass('open');
			}
		}
		event.stopPropagation();
	})
	var left_tips_index = null;
	$('.left-nav #nav').on('mouseenter', '.left-nav-li', function(event) {
		if($('.left-nav').css('width')!='220px'){
		var tips  = $(this).attr('lay-tips');
		left_tips_index = layer.tips(tips, $(this));
		}
	})

	$('.left-nav #nav').on('mouseout', '.left-nav-li', function(event) {
		layer.close(left_tips_index); 
	})
	// 隐藏左侧
	$('.container .left_open i').click(function(event) {
		if($('.left-nav').css('width') == '220px'){
			$('.left-nav .open').click();
			$('.left-nav i').css('font-size','18px');
			$('.left-nav').animate({width: '60px'}, 100);
			$('.left-nav cite,.left-nav .nav_right').hide();
			$('.page-content').animate({left: '60px'}, 100);
			$('.page-content-bg').hide();
		}else{
			$('.left-nav').animate({width: '220px'}, 100);
			$('.page-content').animate({left: '220px'}, 100);
			$('.left-nav i').css('font-size','14px');
			$('.left-nav cite,.left-nav .nav_right').show();
			if($(window).width() < 768){
				$('.page-content-bg').show();
			}
		}
	});

	$('.page-content-bg').click(function(event) {
		$('.left-nav .open').click();
		$('.left-nav i').css('font-size','18px');
		$('.left-nav').animate({width: '60px'}, 100);
		$('.left-nav cite,.left-nav .nav_right').hide();
		$('.page-content').animate({left: '60px'}, 100);
		$(this).hide();
	});

	$(".layui-tab-title").on('contextmenu', 'li', function(event) {
		var tab_left = $(this).position().left;
		var tab_width = $(this).width();
		var left = $(this).position().top;
		var this_index = $(this).attr('lay-id');
		$('#tab_right').css({'left':tab_left+tab_width/2}).show().attr('lay-id',this_index);
		$('#tab_show').show();
		return false;
	});

	$('#tab_right').on('click', 'dd', function(event) {
		var data_type = $(this).attr('data-type');
		var lay_id = $(this).parents('#tab_right').attr('lay-id');
		if(data_type == 'this'){
			$('.layui-tab-title li[lay-id='+lay_id+']').find('.layui-tab-close').click();
		}else if(data_type == 'other'){
			$('.layui-tab-title li').eq(0).find('.layui-tab-close').remove();
			$('.layui-tab-title li[lay-id!='+lay_id+']').find('.layui-tab-close').click();
		}else if(data_type == 'all'){
			$('.layui-tab-title li[lay-id]').find('.layui-tab-close').click();
		}
		$('#tab_right').hide();
		$('#tab_show').hide();
	})


	$('.page-content, #tab_show, .container, .left-nav').click(function(event) {
		$('#tab_right').hide();
		$('#tab_show').hide();
	});
})
// 左侧菜单
function showMenu(){
	crm.post(crm.api_local + "/comm/getMenu", {}, function(data){
		//顶部
		var html_user = '';
		html_user += `
			<a href="javascript:;"><i class="iconfont">&#xe732;</i> ` + data.admin_user.nickname + `</a>
			<dl class="layui-nav-child">
				<dd><a href="` + crm.app_local + `/Login/rePassword.html">修改密码</a></dd>
				<dd><a href="` + crm.app_local + `/Login/login.html">退出</a></dd>
            </dl>
		`;
		$("#user").empty().append(html_user);
		$("#company_info").empty().append(data.company);

		$(".logo").html(`<a href="./index.html">` + data.admin_name + `</a>`);
		document.title = data.admin_name;

		//左侧菜单
		var datas = data.menu;
		var init_src = crm.app_local + '/Index/init.html?step=initInsert';
		if(data.admin_user.username == 'root' && datas.length < 1){
			xadmin.add_tab('设置向导', init_src);
			return false;
		}else{
			// xadmin.add_tab('设置向导', init_src);
			// return false;
			// var lay_id = $('.layui-tab-content').find(".x-iframe[src='" + init_src + "']").attr('tab-id');
			// console.log($(window.parent.document).find('.layui-tab-content').find('iframe.x-iframe').attr('tab-id'));
			// $('.layui-tab-title li[lay-id=' + lay_id + ']').find('.layui-tab-close').click();
		}

		var html = '';
		$.each(datas, function(i, val){
			html += `
				<li>
					<a href="javascript:;">
						<i class="iconfont">&#xe6ae;</i>
						<cite>` + val.title + `</cite>
						<i class="iconfont nav_right">&#xe697;</i>
					</a>
					<ul class="sub-menu">`;
			if(val.childs){
				$.each(val.childs, function(it, valt){
					var _html_url = 'javascript:;';
					if(valt.html_url){
						_html_url = crm.app_local + '/' + valt.html_url;
					} 
					var href = 'javascript:;'
					if(valt.show_type == '_blank'){
						href = `href="` + _html_url + `"`;
					} else{
						href = `onclick="xadmin.add_tab('` + valt.title + `', '` + _html_url + `')"`;
					}
					html += `
						<li>
							<a ` + href + ` target="_blank">
								<i class="iconfont">&#xe6a7;</i>
								<cite>` + valt.title + `</cite>
							</a>
						</li>`;
				});
			}
			html += `
					</ul>
				</li>`;
		});
		$("#nav").empty().append(html);

		xadmin.init(); //打开页面初始
		
		xadmin.end(); //页面加载完要做的
	});
}