<?php
namespace app\middleware;

use think\Response;

class CheckApiKey
{
    /**
    * @title 对于ApiToken的验证
    * @desc 验证ApiToken
    * @use app\validate\CheckApiKey
    */
    public function handle($request, \Closure $next){
        //从authorization获取apitoken
        $token = $request->header('Authorization');
        //$token = $request->header('authorization');

        //token空 401
        if (empty($token)) {
            $result = ['status' => 403, 'msg' => 'authorization头不存在'];
            return json($result);
        }

        //带bearer 会出问题
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7); //bearer+空格 去掉 刚好7个字符
        }
        
        //序列化 有空格就去掉
        $token = trim($token);
        //从配置文件里读
        $allowedKeys = config('geetest.api_keys', []);

        //验证是否在白名单中
        if (!in_array($token, $allowedKeys)) {
            $result = ['status' => 403, 'msg' => 'apikey不存在'];
            return json($result);
        }

        return $next($request);
    }
}
