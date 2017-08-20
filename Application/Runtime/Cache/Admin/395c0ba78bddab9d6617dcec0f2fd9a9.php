<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>管理中心 - 商品列表 </title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="/Public/Admin/Styles/general.css" rel="stylesheet" type="text/css" />
<link href="/Public/Admin/Styles/main.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/Public/umeditor1_2_2-utf8-php/third-party/jquery.min.js"></script>
</head>
<body>
<h1>
	<?php if($_page_btn_name): ?>
    <span class="action-span"><a href="<?php echo $_page_btn_link; ?>"><?php echo $_page_btn_name; ?></a></span>
    <?php endif; ?>
    <span class="action-span1"><a href="#">管理中心</a></span>
    <span id="search_id" class="action-span1"> - <?php echo $_page_title; ?> </span>
    <div style="clear:both"></div>
</h1>

<!--  内容  -->


<!-- 搜索 -->
<div class="form-div search_form_div">
    <form action="/index.php/Admin/Category/lst" method="GET" name="search_form">
		<p>
			分类名称：
	   		<input type="text" name="cat_name" size="30" value="<?php echo I('get.cat_name'); ?>" />
		</p>
		<p>
			上级分类的Id,0:顶级分类：
	   		<input type="text" name="parent_id" size="30" value="<?php echo I('get.parent_id'); ?>" />
		</p>
		<p><input type="submit" value=" 搜索 " class="button" /></p>
    </form>
</div>
<!-- 列表 -->
<form>
<div class="list-div" id="listDiv">
	<table cellpadding="3" cellspacing="1">
    	<tr>
            <th >分类名称</th>
			<th width="60">操作</th>
        </tr>
		<?php foreach ($data as $k => $v): ?>            
			<tr class="tron">
				<td><?php echo str_repeat('-', 8*$v['level']) . $v['cat_name']; ?></td>
		        <td align="center">
		        	<a href="<?php echo U('edit?id='.$v['id']); ?>"> 修改</a> |
	                <a href="<?php echo U('delete?id='.$v['id']); ?>" onclick="return confirm('确定要删除吗？');" title="移除">移除</a>
		        </td>
	        </tr>
        <?php endforeach; ?>
	</table>
</div>
</form>

<script>
</script>

<script src="/Public/Admin/Js/tron.js"></script>

<div id="footer"> 39期 </div>
</body>
</html>