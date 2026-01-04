<?php
//接口配置
return [
    //captcha配置
    'Captcha' => '',
    'CaptchaKey' => '',
    //api服务端配置
    'ApiServer' => 'https://gcaptcha4.geetest.com',
    
    // 验证码有效期/s
    'notBefore' => 300,
    //表名
    'TableName'    => 'Validate',
    'cache_prefix' => 'geetest:token:',
    //缓存目录
    'storage_path' => 'runtime/Geetest/',
    //api密钥
    'api_keys' => ['key1','key2',],
];
