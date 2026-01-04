<?php
namespace app\validate;

use think\validate;

/**
 * @title 对于ApiToken的验证
 * @desc 验证ApiToken
 * @use app\validate\GeetestValidate
 */

class GeetestValidate extends validate{

    //构建参数
    protected $rule = [
        'group_id'          => 'require',
        'user_id'           => 'require',
        'token'             => 'require',
        'code'              => 'require',
        'lot_number'        => 'require',
        'captcha_output'    => 'require',
        'pass_token'        => 'require',
        'gen_time'          => 'require',
    ];

    protected $message = [
        'group_id.require'          => 'verify_group_id_require',
        'user_id.require'           => 'verify_user_id_require',
        'token.require'             => 'verify_token_invalid',
        'code.require'              => 'verify_code_require',
        'lot_number.require'        => 'geetest_param_error',
        'captcha_output.require'    => 'geetest_param_error',
        'pass_token.require'        => 'geetest_param_error',
        'gen_time.require'          => 'geetest_param_error',
    ];

    protected $scene = [
        //对于链接生成使用的
        'create'   => ['group_id', 'user_id'],
        
        //对于用户调用验证页面 不需要做其他验证
        'page'     => ['token'],
        
        //回调接口
        'callback' => ['token', 'lot_number', 'captcha_output', 'pass_token', 'gen_time'],
        
        'check'    => ['group_id', 'code'],
    ];
}