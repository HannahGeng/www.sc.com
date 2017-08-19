<?php
namespace Admin\Model;
use Think\Model;

class GoodsModel extends Model
{
    //添加时调用create方法允许接受的字段
    protected $insertFields = 'brand_id,goods_name,market_price,shop_price,is_on_sale,goods_desc,cat_id,type_id';

    //修改时调用create方法允许接受的字段
    protected $updateFields = 'brand_id,id,goods_name,market_price,shop_price,is_on_sale,goods_desc,cat_id,type_id';

    //定义验证规则
    protected $_validate = array(
        array('cat_id','require','必须选择主分类！',1),
        array('goods_name','require','商品名称不能为空',1),
        array('market_price','currency','市场价格必须是货币类型！',1),
        array('shop_price','currency','本店价格必须是货币类型！',1),
    );

    //钩子方法
    protected function _before_insert(&$data, $options)
    {
        /***********处理LOGO***********/
        if ($_FILES['logo']['error'] == 0)
        {
            $ret = uploadOne('logo','Goods',array(
                array(700,700),
                array(350,350),
                array(130,130),
                array(50,50),
            ));

            $data['logo'] = $ret['images'][0];
            $data['mbig_logo'] = $ret['images'][1];
            $data['big_logo'] = $ret['images'][2];
            $data['mid_logo'] = $ret['images'][3];
            $data['sm_logo'] = $ret['images'][4];
        }

        /*
         * 添加时间
         */
        $data['addtime'] = date('Y-m-d H:i:s',time());

        //我们自己来过滤这个字段
        $data['goods_desc'] = removeXSS($_POST['goods_desc']);
    }

    protected function _before_update(&$data, $options)
    {
        $id = $options['where']['id'];//要修改的商品id
        if (isset($_FILES['pic']))
        {
            $pics = array();
            foreach ($_FILES['pic']['name'] as $k => $v)
            {
                $pics[] = array(
                    'name' => $v,
                    'type' => $_FILES['pic']['type'][$k],
                    'tmp_name' => $_FILES['pic']['tmp_name'][$k],
                    'error' => $_FILES['pic']['error'][$k],
                    'size' => $_FILES['pic']['size'][$k],
                );
            }

            $_FILES = $pics;
            $gpModel = D('goods_pic');

            //循环每个上传
            foreach ($pics as $k => $v)
            {
                $ret = uploadOne($k,'Goods',array(
                    array(650,650),
                    array(350,350),
                    array(50,50),
                ));

                if ($ret['ok'] == 1)
                {
                    $gpModel->add(array(
                        'pic' => $ret['images'][0],
                        'big_pic' => $ret['images'][1],
                        'mid_pic' => $ret['images'][2],
                        'sm_pic' => $ret['images'][3],
                        'goods_id' => $id,
                    ));
                }
            }
        }

        //处理会员价格
        $mp = I('post.member_price');
        $mpModel = D('member_price');

        //先删除原来的会员价格
        $mpModel->where(array(
           'goods_id' => array('eq',$id),
        ))->delete();

        foreach ($mp as $k => $v)
        {
            $_v = (float)$v;

            if ($_v>0)
            {
                $mpModel->add(array(
                    'price' => $_v,
                    'level_id' => $k,
                    'goods_id' => $id,
                ));
            }
        }

        /********************处理扩展分类**********************/
        $ecid = I('post.ext_cat_id');
        $gcModel = D('goods_cat');
        //先删除原分类数据
        $gcModel->where(array(
            'goods_id' => array('eq',$id),
        ))->delete();

        if ($ecid)
        {
            foreach($ecid as $k => $v)
            {
                if (empty($v))
                    continue;
                $gcModel->add(array(
                    'cat_id' => $v,
                    'goods_id' => $id,
                ));
            }
        }

        /********************修改商品属性**********************/
        $gaid = I('post.goods_attr_id');
        $attrValue = I('post.attr_value');
        $gaModel = D('goods_attr');
        $_i = 0;//循环次数
        foreach ($attrValue as $k => $v)
        {
            foreach ($v as $k1 => $v1)
            {
                if ($gaid[$_i] == '')
                {
                    $gaModel->add(array(
                       'goods_id' => $id,
                        'attr_id' => $k,
                        'attr_value' => $v1,
                    ));
                }
                else
                {
                    $gaModel->where(array(
                        'id' => array('eq',$gaid[$_i]),
                    ))->setField('attr_value',$v);
                }
                $_i ++;
            }

        }

        //判断有没有选择图片
        if ($_FILES['logo']['error'] == 0)
        {
            $ret = uploadOne('logo','Goods',array(
                array(700,700),
                array(350,350),
                array(130,130),
                array(50,50),
            ));

            $data['logo'] = $ret['images'][0];
            $data['mbig_logo'] = $ret['images'][1];
            $data['big_logo'] = $ret['images'][2];
            $data['mid_logo'] = $ret['images'][3];
            $data['sm_logo'] = $ret['images'][4];
            /*************** 删除原来的图片 *******************/
            // 先查询出原来图片的路径
            $oldLogo = $this->field('logo,mbig_logo,big_logo,mid_logo,sm_logo')->find($id);
            deleteImage($oldLogo);
        }

        // 我们自己来过滤这个字段
        $data['goods_desc'] = removeXSS($_POST['goods_desc']);
    }

    protected function _before_delete($options)
    {
        $id = $options['where']['id'];//要删除的商品id

        /************删除商品库存量**********/
        $gnModel = D('goods_number');
        $gnModel->where(array(
           'goods_id' => array('eq',$id),
        ))->delete();

        /************删除扩展分类**********/
        $gcModel = D('goods_cat');
        $gcModel->where(array(
           'goods_id' => array('eq',$id),
        ))->delete();

        /************删除商品属性**********/
        $gaModel = D('goods_attr');
        $gaModel->where(array(
           'goods_id' => array('eq',$id),
        ))->delete();

        /************删除相册中的图片**********/
        $gpModel = D('goods_pic');
        $pics = $gpModel->field('pic,sm_pic,mid_pic,big_pic')->where(array(
            'goods_id' => array('eq',$id),
        ))->select();

        //循环每个图片从硬盘上删除
        foreach ($pics as $k => $v)
        {
            deleteImage($v);

            //从数据库中把图片删除
            $gpModel->where(array(
                'goods_id' => array('eq',$id),
            ))->delete();
        }

        //先查询出图片的路径
        $oldLogo = $this->field('logo,mbig_logo,big_logo,mid_logo,sm_logo')->find($id);
        deleteImage($oldLogo);

        //删除会员价格
        $mpModel = D('member_price');
        $mpModel->where(array(
            'goods_id' => array('eq',$id),
        ))->delete();
    }

    //商品添加之后毁掉用这个方法
    protected function _after_insert($data, $options)
    {
        /********处理扩展分类*********/
        $ecid = I('post.ext_cat_id');
        if ($ecid)
        {
            $gcModel = D('goods_cat');
            foreach($ecid as $k => $v)
            {
                if (empty($v))
                    continue;
                $gcModel->add(array(
                    'cat_id' => $v,
                    'goods_id' => $data['id'],
                ));

            }
        }

        /********处理相册图片*********/
        if (isset($_FILES['pic']))
        {
            $pics = array();
            foreach ($_FILES['pic']['name'] as $k => $v)
            {
                $pics[] = array(
                    'name' => $v,
                    'type' => $_FILES['pic']['type'][$k],
                    'tmp_name' => $_FILES['pic']['tmp_name'][$k],
                    'error' => $_FILES['pic']['error'][$k],
                    'size' => $_FILES['pic']['size'][$k],
                );
            }

            $_FILES = $pics;
            $gpModel = D('goods_pic');

            //循环每个上传
            foreach ($pics as $k => $v)
            {
                $ret = uploadOne($k,'Goods',array(
                    array(650,650),
                    array(350,350),
                    array(50,50),
                ));

                if ($ret['ok'] == 1)
                {
                    $gpModel->add(array(
                        'pic' => $ret['images'][0],
                        'big_pic' => $ret['images'][1],
                        'mid_pic' => $ret['images'][2],
                        'sm_pic' => $ret['images'][3],
                        'goods_id' => $data['id'],
                    ));
                }
            }
        }

        /********处理会员价格*********/
        $mp = I('post.member_price');
        $mpModel = D('member_price');
        foreach ($mp as $k => $v)
        {
            $_v = (float)$v;
            //如果设置了会员价格就插入表中
            if ($_v > 0)
            {
                $mpModel->add(array(
                    'price' => $_v,
                    'level_id' => $k,
                    'goods_id' => $data['id'],
                ));
            }
        }

        /********处理商品属性的代码*********/
        $attrValue = I('post.attr_value');
        $gaModel = D('goods_attr');
        foreach($attrValue as $k => $v)
        {
            //把属性的数组去重
            $v = array_unique($v);
            foreach ($v as $k1 => $v1)
            {
                $gaModel->add(array(
                    'goods_id' => $data['id'],
                    'attr_id' => $k,
                    'attr_value' => $v1,
                ));
            }
        }
    }

    //实现翻页、搜索、排序
    public function search($perPage = 5)
    {
        /*************搜索***************/
        $where = array();
        //主分类的搜索
        $brandId = I('get.brand_id');
        if ($brandId)
        {
            $where['a.brand_id'] = array('eq',$brandId);
        }

        $catId = I('get.cat_id');
        if ($catId)
        {
            $gids = $this->getGoodsIdByCatId($catId);
            $where['a.id'] = array('in',$gids);
        }

        $catId = I('get.cat_id');
        if ($catId)
        {
            //先取出所有子分类的ID
            $catModel = D('category');
            $children = $catModel->getChildren($catId);
            $children[] = $catId;
            //搜索出所有这些分类下的商品
            $where['a.cat_id'] = array('IN',$children);
        }

        //品牌
        $brandId = I('get.brand_id');
        if ($brandId)
        {
            $where['a.brand_id'] = array('eq',$brandId);
        }

        //商品名称
        $gn = I('get.gn');
        if ($gn)
        {
            $where['a.goods_name'] = array('like',"%$gn%");
        }
        //价格
        $fp = I('get.fp');
        $tp = I('get.tp');
        if ($fp && $tp)
        {
            $where['a.shop_price'] = array('between',array($fp,$tp));
        }
        elseif ($fp)
        {
            $where['a.shop_price'] = array('egt',$fp);
        }
        elseif ($tp)
        {
            $where['a.shop_price'] = array('elt',$tp);
        }
        //是否上架
        $ios = I('get.ios');
        if ($ios)
        {
            $where['a.is_on_sale'] = array('eq',$ios);
        }
        //添加时间
        $fa = I('get.fa');
        $ta = I('get.ta');
        if ($fa && $ta)
        {
            $where['addtime'] = array('between',array($fa,$ta));
        }
        elseif ($fa)
        {
            $where['addtime'] = array('egt',$fa);
        }
        elseif ($ta)
        {
            $where['addtime'] = array('elt',$ta);
        }

        /*************翻页***************/
        //取出总的记录数
        $count = $this->count();

        //生成翻页类的对象
        $pageObj = new \Think\Page($count,$perPage);

        //设置样式
        $pageObj->setConfig('next','下一页');
        $pageObj->setConfig('prev','上一页');

        $pageString = $pageObj->show();

        /*************排序***************/
        $orderby = 'a.id';//默认排序的字段
        $orderway = 'desc';//默认排序的方法
        $odby = I('get.odby');
        if ($odby)
        {
            if ($odby == 'id_asc')
            {
                $orderway = 'asc';
            }
            elseif ($odby == 'price_desc')
            {
                $orderby = 'shop_price';
            }
            elseif ($odby == 'price_asc')
            {
                $orderby = 'shop_price';
                $orderway = 'asc';
            }
        }

        //取某一页的数据
        $data = $this->order("$orderby $orderway")//排序
            ->field('a. *,b.brand_name,c.cat_name,GROUP_CONCAT(e.cat_name SEPARATOR "<br />") ext_cat_name')
            ->alias('a')
            ->join('LEFT JOIN __BRAND__ b ON a.brand_id=b.id
                    LEFT JOIN __CATEGORY__ c ON a.cat_id=c.id
                    LEFT JOIN __GOODS_CAT__ d ON a.id=d.goods_id
                    LEFT JOIN __CATEGORY__ e ON d.cat_id=e.id')
            ->where($where)
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->group('a.id')
            ->select();

        //返回数据
        return array(
            'data' => $data, //数据
            'page' => $pageString,  //翻页字符串
        );
    }

    /**
     * 取出一个分类下所有商品的ID
     */
    public function getGoodsIdByCatId($catId)
    {
        //先取出所有子分类的ID
        $catModel = D('category');
        $children = $catModel->getChildren($catId);
        //和子分类放一起
        $children[] = $catId;
        /*********************取出子分类或扩展分类在这些分类中的商品**************/
        //取出主分类下的商品ID
        $gids = $this->field('id')->where(array(
            'cat_id' => array('in',$children),//主分类下的商品
        ))->select();

        //取出扩展分类下的商品id
        $gcModel = D('goods_cat');
        $gidsl = $gcModel->field('DISTINCT goods_id id')->where(
            array(
                'cat_id' =>array('IN',$children)
            ))->select();

        //把主分类的ID和扩展分类下的商品ID合并成一个二维数组【两个都不为空时合并，否则取出不为空的数组】
        if ($gids && $gidsl)
        {
            $gids = array_merge($gids,$gidsl);
        }
        elseif($gidsl)
        {
            $gids = $gidsl;
        }
        //二维转一维
        $id = array();
        foreach ($gids as $k => $v)
        {
            if (!in_array($v['id'],$id))
            {
                $id[] = $v['id'];
            }
        }
        return $id;
    }
}