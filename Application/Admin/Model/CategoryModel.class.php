<?php
namespace Admin\Model;
use Think\Model;
class CategoryModel extends Model 
{
	protected $insertFields = array('cat_name','parent_id');
	protected $updateFields = array('id','cat_name','parent_id');
	protected $_validate = array(
		array('cat_name', 'require', '分类名称不能为空！', 1, 'regex', 3),
		array('cat_name', '1,30', '分类名称的值最长不能超过 30 个字符！', 1, 'length', 3),
		array('parent_id', 'number', '上级分类的Id,0:顶级分类必须是一个整数！', 2, 'regex', 3),
	);

	//找一个分类所有子分类的ID
    public function getChildren($catId)
    {
        //取出所有的分类
        $data = $this->select();
        //递归从所有的子分类中挑出子分类的id
        return $this->_getChildren($data,$catId,TRUE);
    }

    //递归从数据中找子分类
    private function _getChildren($data,$catId,$isClear = FALSE)
    {
        static $_ret = array(); //保存找到的子分类的id

        if ($isClear)
        {
            $_ret = array();
        }

        //循环所有的分类找子分类
        foreach ($data as $k => $v)
        {
            if ($v['parent_id'] == $catId)
            {
                $_ret[] = $v['id'];
                //再找这个$v的子分类
                $this->_getChildren($data,$v['id']);
            }
        }

        return $_ret;

    }

    public function getTree()
    {
        $data = $this->select();
        return $this->_getTree($data);
    }

    private function _getTree($data,$parent_id=0,$level=0)
    {
        static $_ret = array();
        foreach ($data as $k => $v)//用来标记这个分类是第几级的
        {
            if ($v['parent_id'] == $parent_id)
            {
                $v['level'] = $level;
                $_ret[] = $v;
                //找子分类
                $this->_getTree($data,$v['id'],$level+1);
            }
        }
        return $_ret;
    }

	public function search($pageSize = 20)
	{
		/**************************************** 搜索 ****************************************/
		$where = array();
		if($cat_name = I('get.cat_name'))
			$where['cat_name'] = array('like', "%$cat_name%");
		if($parent_id = I('get.parent_id'))
			$where['parent_id'] = array('eq', $parent_id);
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
	    //先找出所有子分类的ID
        $children = $this->getChildren($option['where']['id']);
        if ($children)
        {
            $children = implode(',',$children);
            $model = new \Think\Model;
            $model->table('__CATEGORY__')->delete($children);
        }
	}
}