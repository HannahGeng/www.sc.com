<?php

namespace Admin\Controller;
use Think\Controller;

class GoodsController extends Controller
{
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
        $this->display();
    }

    public function lst()
    {
        $this->display();
    }
}