var refresh_html = `
	<a class="layui-btn layui-btn-sm layui-btn-primary refresh-icon" href="javascript:location.replace(location.href);" title="刷新">
		<i class="iconfont">&#xe6aa;</i>
	</a>`;
$("body").prepend(refresh_html);