<?php
namespace Admin\Model;
use Think\Model;

class GoodsModel extends Model
{
    //添加时调用create方法允许接受的字段
    protected $insertFields = 'goods_name,market_price,shop_price,is_on_sale,goods_desc';

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
        //判断有没有图片
        if ($_FILES['logo']['error'] == 0)
        {
            $upload = new \Think\Upload();//实例化上传类
            $upload->maxSize = 1024*1024;//1M
            $upload->exts = array('jpg','gif','png','jpeg');
            $upload->rootPath = './Public/Uploads/';//设置附件上传目录
            $upload->savePath = 'Goods/';//设置附件上传（子）目录

            //上传文件
            $info = $upload->upload();

            if (!$info)
            {
                $this->error = $upload->getError();
                return FAULSE;
            }
            else
            {
                /***********生成缩略图***********/
                //先拼成原图上的路径
                $logo = $info['logo']['savepath'].$info['logo']['savename'];
                //拼出缩略图的路径和名称
                $mbiglogo = $info['logo']['savepath'].'mbig_'.$info['logo']['savename'];
                $biglogo = $info['logo']['savepath'].'big_'.$info['logo']['savename'];
                $midlogo = $info['logo']['savepath'].'mid_'.$info['logo']['savename'];
                $smlogo = $info['logo']['savepath'].'sm_'.$info['logo']['savename'];
                $image = new \Think\Image();
                //打开要生成的缩略图的图片
                $image->open('./Public/Uploads/'.$logo);
                //生成缩略图
                $image->thumb(700,700)->save('./Public/Uploads/'.$mbiglogo);
                $image->thumb(150,150)->save('./Public/Uploads/'.$biglogo);
                $image->thumb(130,130)->save('./Public/Uploads/'.$midlogo);
                $image->thumb(50,50)->save('./Public/Uploads/'.$smlogo);
                //把路径放到表单中
                $data['logo'] = $logo;
                $data['mbig_logo'] = $mbiglogo;
                $data['big_logo'] = $biglogo;
                $data['mid_logo'] = $midlogo;
                $data['sm_logo'] = $smlogo;

            }
        }

        /*
         * 添加时间
         */
        $data['addtime'] = date('Y-m-d H:i:s',time());

        //我们自己来过滤这个字段
        $data['goods_desc'] = removeXSS($_POST['goods_desc']);
    }
}