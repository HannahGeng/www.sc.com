<?php

namespace Admin\Controller;
use Think\Controller;

class LoginController extends Controller
{
    //生成验证码图片
    public function chkcode()
    {
        $Verify = new \Think\Verify(array(
            'fontSize' => 30,
            'length' => 2,
            'useNoise' => TRUE,
        ));

        $Verify->entry();
    }

    public function login()
    {
        if (IS_POST)
        {
            $model = D('Admin');

            //接收表单并验证表单
            if ($model->validate($model->_login_validate)->create())
            {
                if ($model->login())
                {
                    $this->success('登录成功！',U('Index/index'));
                    exit;
                }
            }
            $this->error($model->getError());
        }
        $this->display();
    }

    public function logout()
    {
        $model = D('Admin');
        $model -> logout();
        redirect('login');
    }

}