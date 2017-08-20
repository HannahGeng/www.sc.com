<?php
namespace Admin\Model;
use Think\Model;
class AdminRoleModel extends Model 
{
	protected $insertFields = array('admin_id','role_id');
	protected $updateFields = array('id','admin_id','role_id');
	protected $_validate = array(
		array('admin_id', 'require', '管理员id不能为空！', 1, 'regex', 3),
		array('admin_id', 'number', '管理员id必须是一个整数！', 1, 'regex', 3),
		array('role_id', 'require', '角色id不能为空！', 1, 'regex', 3),
		array('role_id', 'number', '角色id必须是一个整数！', 1, 'regex', 3),
	);
	public function search($pageSize = 20)
	{
		/**************************************** 搜索 ****************************************/
		$where = array();
		if($admin_id = I('get.admin_id'))
			$where['admin_id'] = array('eq', $admin_id);
		if($role_id = I('get.role_id'))
			$where['role_id'] = array('eq', $role_id);
		/************************************* 翻页 ****************************************/
		$count = $this->alias('a')->where($where)->count();
		$page = new \Think\Page($count, $pageSize);
		// 配置翻页的样式
		$page->setConfig('prev', '上一页');
		$page->setConfig('next', '下一页');
		$data['page'] = $page->show();
		/************************************** 取数据 ******************************************/
		$data['data'] = $this->alias('a')->where($where)->group('a.id')->limit($page->firstRow.','.$page->listRows)->select();
		return $data;
	}
	// 添加前
	protected function _before_insert(&$data, $option)
	{
	}
	// 修改前
	protected function _before_update(&$data, $option)
	{
	}
	// 删除前
	protected function _before_delete($option)
	{
		if(is_array($option['where']['id']))
		{
			$this->error = '不支持批量删除';
			return FALSE;
		}
	}
	/************************************ 其他方法 ********************************************/
}