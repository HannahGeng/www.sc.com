<?php
namespace Admin\Controller;
use Think\Controller;
class CategoryController extends Controller 
{
    public function add()
    {
        $model = D('category');

        if(IS_POST)
    	{
    		if($model->create(I('post.'), 1))
    		{
    			if($id = $model->add())
    			{
    				$this->success('添加成功！', U('lst?p='.I('get.p')));
    				exit;
    			}
    		}
    		$this->error($model->getError());
    	}

    	//取出所有的分类做下拉框
        $catData = $model->getTree();

		// 设置页面中的信息
		$this->assign(array(
		    'catData'     => $catData,
			'_page_title' => '添加分类',
			'_page_btn_name' => '分类列表',
			'_page_btn_link' => U('lst'),
		));
		$this->display();
    }
    public function edit()
    {
    	$id = I('get.id');
        $model = D('Category');

        if(IS_POST)
    	{
    		if($model->create(I('post.'), 2))
    		{
    			if($model->save() !== FALSE)
    			{
    				$this->success('修改成功！', U('lst', array('p' => I('get.p', 1))));
    				exit;
    			}
    		}
    		$this->error($model->getError());
    	}

    	$catData = $model->getTree();
        $children = $model->getChildren($id);

		// 设置页面中的信息
		$this->assign(array(
		    'children'    => $children,
			'catData'        => $catData,
			'_page_title' => '修改分类',
			'_page_btn_name' => '分类列表',
			'_page_btn_link' => U('lst'),
		));
		$this->display();
    }
    public function delete()
    {
    	$model = D('Category');
    	if($model->delete(I('get.id', 0)) !== FALSE)
    	{
    		$this->success('删除成功！', U('lst'));
    	}
    	else 
    	{
    		$this->error('删除失败！原因：'.$model->getError());
    	}
    }
    public function lst()
    {
    	$model = D('category');
    	$data = $model->getTree();

		// 设置页面中的信息
		$this->assign(array(
		    'data'        => $data,
			'_page_title' => '分类列表',
			'_page_btn_name' => '添加分类',
			'_page_btn_link' => U('add'),
		));
    	$this->display();
    }
}