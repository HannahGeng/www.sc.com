<?php
namespace Admin\Model;
use Think\Model;
class PrivilegeModel extends Model 
{
	protected $insertFields = array('pri_name','module_name','controller_name','action_name','parent_id');
	protected $updateFields = array('id','pri_name','module_name','controller_name','action_name','parent_id');
	protected $_validate = array(
		array('pri_name', 'require', '权限名称不能为空！', 1, 'regex', 3),
		array('pri_name', '1,30', '权限名称的值最长不能超过 30 个字符！', 1, 'length', 3),
		array('module_name', '1,30', '模块名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('controller_name', '1,30', '控制器名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('action_name', '1,30', '方法名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('parent_id', 'number', '上级权限Id必须是一个整数！', 2, 'regex', 3),
	);
	public function search($pageSize = 20)
	{
		/**************************************** 搜索 ****************************************/
		$where = array();
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

    /************************************* 递归相关方法 *************************************/
    public function getTree()
    {
        $data = $this->select();
        return $this->_reSort($data);
    }
    private function _reSort($data, $parent_id=0, $level=0, $isClear=TRUE)
    {
        static $ret = array();
        if($isClear)
            $ret = array();
        foreach ($data as $k => $v)
        {
            if($v['parent_id'] == $parent_id)
            {
                $v['level'] = $level;
                $ret[] = $v;
                $this->_reSort($data, $v['id'], $level+1, FALSE);
            }
        }
        return $ret;
    }

	/************************************ 检查当前管理员是否有权限访问这个页面 ********************************************/
	public function chkPri()
    {
        $adminId = session('id');
        //如果是超级管理员直接返回TRUE
        if ($adminId == 1)
            return TRUE;
        $arModel = D('admin_role');
        $has = $arModel->alias('a')
            ->join('LEFT JOIN __ROLE_PRI__ b ON a.role_id=b.role_id
                    LEFT JOIN __PRIVILEGE__ c ON b.pri_id=c.id')
            ->where(array(
                'a.admin_id' => array('eq',$adminId),
                'c.module_name' => array('eq',MODULE_NAME),
                'c.controller_name' => array('eq',CONTROLLER_NAME),
                'c.action_name' => array('eq',ACTION_NAME),
            ))->count();
        return ($has > 0);
    }

    /**
     * 获取当前管理员所拥有的前两级的权限
     */
    public function getBtns()
    {
        /**********************先取出当前管理员所拥有的所有的权限************************/
        $adminId = session('id');
        if ($adminId ==1)
        {
            $priModel = D('Privilege');
            $priData = $priModel->select();
        }
        else
        {
            $arModel = D('admin_role');
            $priData = $arModel->alias('a')
                ->field('DISTINCT c.id,c.pri_name,c.module_name,c.controller_name,c.action_name,c.parent_id')
                ->join('LEFT JOIN __ROLE_PRI__ b ON a.role_id=b.role_id
                        LEFT JOIN __PRIVILEGE__ c ON b.pri_id=c.id')
                ->where(array(
                    'a.admin_id' => array('eq',$adminId),
                ))->select();
        }

        /**********************从所有的权限中挑出前两级的************************/
        $btns = array();
        foreach ($priData as $k => $v)
        {
            if ($v['parent_id'] == 0)
            {
                foreach ($priData as $k1 => $v1)
                {
                    if ($v1['parent_id'] == $v['id'])
                    {
                        $v['children'][] = $v1;
                    }
                }
                $btns[] = $v;
            }
        }

        return $btns;
    }

}