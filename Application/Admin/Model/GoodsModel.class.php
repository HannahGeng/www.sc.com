<?php
namespace Admin\Model;
use Think\Model;

class GoodsModel extends Model
{
    //添加时调用create方法允许接受的字段
    protected $insertFields = 'brand_id,goods_name,market_price,shop_price,is_on_sale,goods_desc';

    //修改时调用create方法允许接受的字段
    protected $updateFields = 'brand_id,id,goods_name,market_price,shop_price,is_on_sale,goods_desc';

    //定义验证规则
    protected $_validate = array(
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
        $id = $options['where']['id'];

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
    }

    //实现翻页、搜索、排序
    public function search($perPage = 5)
    {
        /*************搜索***************/
        $where = array();
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
            ->field('a. *,b.brand_name')
            ->alias('a')
            ->join('LEFT JOIN __BRAND__ b ON a.brand_id=b.id')
            ->where($where)
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->select();

        //返回数据
        return array(
            'data' => $data, //数据
            'page' => $pageString,  //翻页字符串
        );
    }
}