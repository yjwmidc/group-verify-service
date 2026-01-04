<?php
namespace app\controller;

use app\BaseController;
use app\model\GeetestModel;
use app\validate\GeetestValidate;
use app\middleware\CheckApiKey;
use think\facade\View;

/**
 * @title 极验验证管理
 * @desc 极验验证管理
 * @use app\controller\GeetestController
 */
class GeetestController extends BaseController{
    //php8.2+ 动态属性被弃用 报错:User #0 [8192]ErrorException in GeetestController.php line 26 Creation of dynamic property app\controller\GeetestController::$validate is deprecated
    protected $validate;
    //应用初始化
    public function initialize()
    {
        //调用父类初始化
        parent::initialize();
        $this->validate = new GeetestValidate();
    }
    
    /**
     * 中间件配置
     */
    protected $middleware = [
        CheckApiKey::class => ['except' => ['page', 'callback']]
    ];



    /**
     * 时间 2026-01-04
     * @title 生成验证链接
     * @desc 生成验证链接
     * @url /verify/create
     * @method  GET
     * @author yjwmidc
     * @version v1
     * @param string gid - 群组ID required
     * @param string uid - 用户ID required
     * @return int status - 状态码,200成功,400失败
     * @return string msg - 提示信息
     * @return array data - 返回数据
     * @return string data.token - 验证Token
     * @return string data.url - 验证链接
     * @return int data.expire - 过期时间(秒)
     */
    public function create()
    {
        $param = $this->request->param();
        
        //映射参数方便Validate类处理
        $param['group_id'] = $param['gid'] ?? '';
        $param['user_id'] = $param['uid'] ?? '';

        //参数验证
        if (!$this->validate->scene('create')->check($param)){
            $result = ['status' => 400 , 'msg' => lang($this->validate->getError())];
            return json($result);
        }

        //实例化模型类
        $geetestModel = new GeetestModel();

        //生成Token
        $token = $geetestModel->generateToken($param['group_id'], $param['user_id']);

        //保存数据
        $saveData = [
            'group_id' => $param['group_id'],
            'user_id'  => $param['user_id'],
            'verified' => 0,
            'code'     => null
        ];

        if (!$geetestModel->saveVerifyData($token, $saveData)) {
            $result = ['status' => 500, 'msg' => lang('system_error')];
            return json($result);
        }
        //返回
        $result = [
            'status' => 200,
            'msg'    => lang('success_message'),
            'data'   => [
                'token'  => $token,
                'url'    => $this->request->domain() . '/verify/page?token=' . $token,
                'expire' => $geetestModel->getCodeExpire(),
            ]
        ];

        return json($result);
    }

    /**
     * 时间 2026-01-04
     * @title 生成用户访问的验证页
     * @desc 生成用户访问的验证页
     * @url /verify/page
     * @method  GET
     * @author yjwmidc
     * @version v1
     * @param string token - 验证Token required
     * @return mixed html - 验证页面HTML
     */
    public function page()
    {
        $param = $this->request->param();

        // 参数验证
        if (!$this->validate->scene('page')->check($param)){
            return response(lang($this->validate->getError()), 400);
        }
        // 实例化模型类
        $geetestModel = new GeetestModel();

        $data = $geetestModel->getVerifyData($param['token']);

        if (!$data) {
            return response('验证链接已过期或不存在', 404);
        }

        if ($data['verified']) {
            return response('您已完成验证，验证码: ' . $data['code'] . '，请在群内发送此验证码', 200);
        }

        // 获取极验ID
        $captchaId = $geetestModel->getCaptchaId();
        
        // 赋值模板
        View::assign([
            'token'      => $param['token'],
            'captcha_id' => $captchaId
        ]);

        return View::fetch();
    }

    /**
     * 时间 2026-01-04
     * @title 处理极验验证结果
     * @desc 处理极验验证结果
     * @url /verify/callback
     * @method  POST
     * @author yjwmidc
     * @version v1
     * @param string token - 验证Token required
     * @param string lot_number - 极验参数 required
     * @param string captcha_output - 极验参数 required
     * @param string pass_token - 极验参数 required
     * @param string gen_time - 极验参数 required
     * @return int status - 状态码,200成功,400失败
     * @return string msg - 提示信息
     * @return array data - 返回数据
     * @return string data.code - 6位数字验证码
     */
    public function callback()
    {
        $param = $this->request->param();

        // 参数验证
        if (!$this->validate->scene('callback')->check($param)){
            $result = ['status' => 400 , 'msg' => lang($this->validate->getError())];
            return json($result);
        }
        
        // 实例化模型类
        $geetestModel = new GeetestModel();
        
        $data = $geetestModel->getVerifyData($param['token']);

        if (!$data) {
            $result = ['status' => 400, 'msg' => '验证链接已过期'];
            return json($result);
        }

        if ($data['verified']) {
            $result = ['status' => 200, 'msg' => '已验证', 'data' => ['code' => $data['code']]];
            return json($result);
        }

        //二次校验参数映射
        $verifyParam = [
            'Batch'         => $param['lot_number'],
            'CaptchaOutput' => $param['captcha_output'],
            'PassTokens'    => $param['pass_token'],
            'GenTimes'      => $param['gen_time'],
        ];

        if (!$geetestModel->verifyGeetest($verifyParam)) {
            $result = ['status' => 400, 'msg' => '验证失败，请刷新重试'];
            return json($result);
        }

        // 生成验证码
        $code = $geetestModel->generateCode();
        
        $updateData = [
            'verified'    => 1,
            'code'        => $code,
            'verified_at' => time(),
            'ip'          => $this->request->ip(),
            'user_agent'  => $this->request->header('user-agent'),
            'extra'       => ['geetest_params' => $verifyParam]
        ];

        if ($geetestModel->updateVerifyData($param['token'], $updateData)) {
            $result = [
                'status' => 200,
                'msg'    => '验证成功',
                'data'   => ['code' => $code]
            ];
            return json($result);
        }

        $result = ['status' => 500, 'msg' => '数据保存失败'];
        return json($result);
    }

    /**
     * 时间 2026-01-04
     * @title 验证验证码
     * @desc 验证验证码是否正确且属于该用户
     * @url /verify/check
     * @method  POST
     * @author yjwmidc
     * @version v1
     * @param string group_id - 群组ID required
     * @param string code - 验证码 required
     * @param string user_id - 用户ID optional
     * @return int status - 状态码,200成功,400失败
     * @return string msg - 提示信息
     * @return bool data.passed - 是否通过
     * @return string data.user_id - 用户ID
     * @return string data.group_id - 群组ID
     */
    public function check()
    {
        $param = $this->request->param();

        // 参数验证
        if (!$this->validate->scene('check')->check($param)){
            $result = ['status' => 400 , 'msg' => lang($this->validate->getError()), 'data' => ['passed' => false]];
            return json($result);
        }

        $geetestModel = new GeetestModel();
        
        // 查找验证码
        $data = $geetestModel->findByCode($param['code'], $param['group_id']);

        if (!$data) {
            $result = ['status' => 400, 'msg' => '验证码无效或已过期', 'data' => ['passed' => false]];
            return json($result);
        }

        // 验证用户ID (如果传了)
        if (!empty($param['user_id']) && (string)$data['user_id'] !== (string)$param['user_id']) {
            $result = ['status' => 403, 'msg' => '非本人验证码', 'data' => ['passed' => false]];
            return json($result);
        }

        // 标记使用
        if ($geetestModel->setUsed($param['code'], $param['group_id'])) {
            $result = [
                'status' => 200,
                'msg'    => '验证通过',
                'data'   => [
                    'passed'   => true,
                    'user_id'  => $data['user_id'],
                    'group_id' => $data['group_id'],
                ]
            ];
            return json($result);
        }

        $result = ['status' => 500, 'msg' => '状态更新失败', 'data' => ['passed' => false]];
        return json($result);
    }

    /**
     * 时间 2026-01-04
     * @title 清理过期验证码
     * @desc 删除数据库中的过期记录
     * @url /verify/clean
     * @method  POST
     * @author yjwmidc
     * @version v1
     * @return int status - 状态码
     * @return string msg - 清理结果
     * @return int data.count - 清理数量
     */
    public function clean()
    {
        $counts = (new GeetestModel())->cleanExpiredCodes(7);

        $result = [
            'status' => 200,
            'msg'    => "清除过期验证码数量:{$counts}",
            'data'   => ['count' => $counts]
        ];

        return json($result);
    }
}