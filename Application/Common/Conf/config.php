<?php
return array(
    /* 数据库设置 */
    'DB_TYPE' =>  'pdo',     // mysql,mysqli,pdo
    'DB_DSN'    => 'mysql:host=localhost;dbname=php39;charset=utf8',
    'DB_USER' =>  'root',      // 用户名
    'DB_PWD' =>  'root',          // 密码
    'DB_PORT' =>  '3306',        // 端口
    'DB_PREFIX' =>  'p39_',    // 数据库表前缀
//    'DB_HOST' =>  'localhost', // 服务器地址
    //'DB_NAME' =>  'php39',          // 数据库名
    //'DB_CHARSET' =>  'utf8',      // 数据库编码默认采用utf8
    'DEFAULT_FILTER' => 'trim,htmlspecialchars',
);