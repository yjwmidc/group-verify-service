<?php
//接口配置
return [
    'Captcha' => '9cfd862579c55ccf5f92f673a75cd38b',
    'CaptchaKey' => 'fd982638c8dac52157b2f8c6a26230de',
    'ApiServer' => 'https://gcaptcha4.geetest.com',
    
    // 验证码有效期/s
    'notBefore' => 300,
    //表名
    'TableName'    => 'ValidateTable',
    'cache_prefix' => 'geetest:token:',
    
    'storage_path' => 'runtime/Geetest/',
    'api_keys' => [
        'key1',
        'key2',
    ],
];
