<?php
namespace Admin\Model;
use Think\Model;

class GoodsModel extends Model
{
    //添加时调用create方法允许接受的字段
    protected $insertFields = 'goods_name,market_price,shop_price,is_on_sale,goods_desc';

    //修改时调用create方法允许接受的字段
    protected $updateFields = 'id,goods_name,market_price,shop_price,is_on_sale,goods_desc';

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
        $oldLogo = $this->field('logo,mbig_logo,big_logo,mid_logo,sm_logo')->find();

        //从硬盘上删除
        unlink('./Public/Uploads/'.$oldLogo['logo']);
        unlink('./Public/Uploads/'.$oldLogo['mbig_logo']);
        unlink('./Public/Uploads/'.$oldLogo['big_logo']);
        unlink('./Public/Uploads/'.$oldLogo['mid_logo']);
        unlink('./Public/Uploads/'.$oldLogo['sm_logo']);
    }

    //实现翻页、搜索、排序
    public function search($perPage = 5)
    {
        /*************搜索***************/
        $where = array();
        //商品名称
        $gn = I('get.gn');
        if ($gn)
        {
            $where['goods_name'] = array('like',"%$gn%");
        }
        //价格
        $fp = I('get.fp');
        $tp = I('get.tp');
        if ($fp && $tp)
        {
            $where['shop_price'] = array('between',array($fp,$tp));
        }
        elseif ($fp)
        {
            $where['shop_price'] = array('egt',$fp);
        }
        elseif ($tp)
        {
            $where['shop_price'] = array('elt',$tp);
        }
        //是否上架
        $ios = I('get.ios');
        if ($ios)
        {
            $where['is_on_sale'] = array('eq',$ios);
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
        $orderby = 'id';//默认排序的字段
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
        $data = $this->order("$orderby $orderway")->where($where)->limit($pageObj->firstRow.','.$pageObj->listRows)->select();

        //返回数据
        return array(
            'data' => $data, //数据
            'page' => $pageString,  //翻页字符串
        );
    }
}