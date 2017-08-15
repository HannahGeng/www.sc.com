<?php

namespace Admin\Controller;
use Think\Controller;

class GoodsController extends Controller
{
    //添加商品
    public function add()
    {
        //判断用户是否提交了表单
        if (IS_POST)
        {
            $model = D('goods');

            //create方法：接收数据并保存到模型中；根据模型中定义的规则验证表单
            /**
             * 第一个参数：数据默认是$_POST
             * 第二个参数：表单类型：1为添加、2为修改
             * $_POST：表单中原始的数据，I('post.')：过滤之后的$_POST数据，过滤XSS攻击
             */
            if ($model->create(I('post.'),1))
            {
                //插入数据库中
                if ($model->add())
                {
                    //显示成功信息并等待1妙之后跳转
                    $this->success('操作成功',U('lst'));
                    exit();
                }
            }

            $error = $model->getError();
            $this->error($error);
        }

        //显示表单
        // 设置页面信息
        $this->assign(array(
            '_page_title' => '添加新商品',
            '_page_btn_name' => '商品列表',
            '_page_btn_link' => U('lst'),
        ));
        $this->display();
    }

    //商品列表：查询
    public function lst()
    {
        $model = D('goods');

        $data = $model->search();

        $this->assign($data);

        // 设置页面信息
        $this->assign(array(
            '_page_title' => '商品列表',
            '_page_btn_name' => '添加新商品',
            '_page_btn_link' => U('add'),
        ));

        $this->display();
    }

    //修改商品
    public function edit()
    {
        $id = I('get.id');//要修改的商品的ID
        $model = D('goods');

        //判断用户是否提交了表单
        if (IS_POST)
        {
            //create方法：接收数据并保存到模型中；根据模型中定义的规则验证表单
            /**
             * 第一个参数：数据默认是$_POST
             * 第二个参数：表单类型：1为添加、2为修改
             * $_POST：表单中原始的数据，I('post.')：过滤之后的$_POST数据，过滤XSS攻击
             */
            if ($model->create(I('post.'),2))
            {
                //插入数据库中
                if (FALSE !== $model->save())
                {
                    //显示成功信息并等待1妙之后跳转
                    $this->success('操作成功',U('lst'));
                    exit();
                }
            }

            $error = $model->getError();
            $this->error($error);
        }

        //根据id取出要修改的商品的信息
        $data = $model->find($id);

        $this->assign('data',$data);

        //显示表单
        $this->display();
    }

    //删除商品
    public function delete()
    {
        $model = D('goods');
        if (FALSE !== $model->delete(I('get.id')))
        {
            $this->success('删除成功',U('lst'));
        }
        else
        {
            $this->error('删除失败！原因：'.$model->getError());
        }
    }
}