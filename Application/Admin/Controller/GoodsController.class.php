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
            set_time_limit(0);

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

        //取出所有的品牌
        $brandModel = D('brand');
        $brandData = $brandModel->select();

        //取出所有的会员级别
        $mlModel = D('member_level');
        $mlData = $mlModel->select();

        //取出所有的分类做下拉框
        $catModel = D('category');
        $catData = $catModel->getTree();

        // 设置页面信息
        $this->assign(array(
            'brandData'   => $brandData,
            'mlData'      => $mlData,
            'catData'     => $catData,
            '_page_title' => '添加新商品',
            '_page_btn_name' => '商品列表',
            '_page_btn_link' => U('lst'),
        ));

        //显示表单
        $this->display();
    }

    //商品列表：查询
    public function lst()
    {
        $model = D('goods');

        $data = $model->search();

        $this->assign($data);

        //取出所有的分类做下拉框
        $catModel = D('category');
        $catData = $catModel->getTree();

        // 设置页面信息
        $this->assign(array(
            'catData'     => $catData,
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

        //取出所有的会员级别
        $mlModel = D('member_level');
        $mlData = $mlModel->select();

        //取出这件商品已经设置好的会员价格
        $mpModel = D('member_price');
        $mpData = $mpModel->where(array(
            'goods_id' => array('eq',$id),
        ))->select();

        //二维数组转一维数组
        $_mpData = array();
        foreach($mpData as $k => $v)
        {
            $_mpData[$v['level_id']] = $v['price'];
        }


        //取出所有品牌
        $brandModel = D('brand');
        $brandData = $brandModel->select();

        //取出相册中现有的图片
        $gpModel = D(goods_pic);
        $gpdata = $gpModel->field('id,mid_pic')->where(array(
            'goods_id' => array('eq',$id),
        ))->select();

        //取出所有的分类做下拉框
        $catModel = D('category');
        $catData = $catModel->getTree();

        //取出扩展分类
        $gcModel = D('goods_cat');
        $gcData = $gcModel->field('cat_id')->where(array(
            'goods_id' => array('eq',$id),
        ))->select();

        //取出这件商品已经设置了的属性值
        $attrModel = D('attribute');
        $attrData = $attrModel->alias('a')
            ->field('a.id attr_id,a.attr_name,a.attr_type,a.attr_option_values,b.attr_value,b.id')
            ->join('LEFT JOIN __GOODS_ATTR__ b ON (a.id=b.attr_id AND b.goods_id='.$id.')')
            ->where(array(
                'a.type_id' => array('eq', $data['type_id']),
            ))->select();

        $this->assign(array(
                'catData'   => $catData,
                'mlData'    => $mlData,
                'mpData'    => $_mpData,
                'brandData' => $brandData,
                'gpData'    => $gpdata,
                'gcData'    => $gcData,
                'gaData'  => $attrData,
                '_page_title' => '修改商品',
                '_page_btn_name' => '商品列表',
                '_page_btn_link' => U('lst'),
            )
        );

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

    //商品库存量
    public function goods_number()
    {
        //接收商品ID
        $id = I('get.id');
        $gnModel = D('goods_number');

        //处理表单
        if (IS_POST)
        {
            //先删除原库存
            $gnModel->where(array(
                'goods_id' => array('eq',$id),
            ))->delete();

            $gaid = I('post.goods_attr_id');
            $gn = I('post.goods_number');
            //先计算出商品属性ID和库存量的比例
            $gaidCount = count($gaid);
            $gnCount = count($gn);
            $rate = $gaidCount/$gnCount;

            //循环库存量
            $_i = 0;
            foreach($gn as $k => $v)
            {
                $_goodsAttrId = array();
                for($i = 0;$i < $rate; $i++)
                {
                    $_goodsAttrId[] = $gaid[$_i];
                    $_i++;
                }

                //先升序排序
                sort($_goodsAttrId,SORT_NUMERIC);

                //把取出来的商品属性ID转化为字符串
                $_goodsAttrId = (string)implode(',',$_goodsAttrId);
                $gnModel->add(array(
                   'goods_id' => $id,
                    'goods_attr_id' => $_goodsAttrId,
                    'goods_number' => $v,
                ));
            }

            $this->success('设置成功！', U('goods_number?id='.I('get.id')));
            exit();
        }

        //根据商品id取出这件商品所有可选属性的值
        $gaModel = D('goods_attr');
        $gaData = $gaModel->alias('a')
            ->field('a.*,b.attr_name')
            ->join('LEFT JOIN __ATTRIBUTE__ b ON a.attr_id=b.id')
            ->where(array(
                'a.goods_id' => array('eq', $id),
                'b.attr_type' => array('eq', '可选'),
            ))->select();

        $_gaData = array();
        foreach ($gaData as $k => $v)
        {
            $_gaData[$v['attr_name']][] = $v;
        }

        //取出这件商品已经设置过的库存量
        $gnData = $gnModel->where(array(
           'goods_id' => $id,
        ))->select();

        $this->assign(array(
           'gaData' => $_gaData,
            'gnData' => $gnData,
        ));

        //设置页面信息
        $this->assign(array(
           '_page_title' => '库存量',
            '_page_btn_name' => '返回列表',
            '_page_btn_link' => U('lst'),
        ));

        $this->display();
    }

    //处理AJAX删除图片请求
    public function ajaxDelPic()
    {
        $picId = I('get.picid');
        //根据id从硬盘上删除图片
        $gpModel = D('goods_pic');
        $pic = $gpModel->field('pic,sm_pic,mid_pic,big_pic')->find($picId);
        //从硬盘上删除图片
        deleteImage($pic);
        //从数据库中删除记录
        $gpModel->delete($picId);
    }

    //处理获取属性的AJAX请求
    public function ajaxGetAttr()
    {
        $typeId = I('get.type_id');
        $attrModel = D('Attribute');
        $attrData = $attrModel->where(array(
            'type_id' => array('eq',$typeId),
        ))->select();
        echo json_encode($attrData);
    }

    //处理删除属性
    public function ajaxDelAttr()
    {
        $goodsId = addslashes(I('get.goods_id'));
        $gaid = addslashes(I('get.gaid'));
        $gaModel = D('goods_attr');
        $gaModel->delete($gaid);
        //删除相关库存量数据
        $gnModel = D('goods_number');
        $gnModel->where(array(
            'goods_id' => array('EXP',"=$goodsId or AND FIND_IN_SET($gaid,attr_list)"),
        ))->delete();
    }

}