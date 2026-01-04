<?php
namespace app\model;

use think\facade\Config;
use think\facade\Db;
use think\facade\Cache;

/**
 * @title Geetest验证模型
 * @desc 极验验证码逻辑处理
 * @use app\model\GeetestModel
 */
class GeetestModel{
    // 设置配置信息
    protected $config = [];

    public function __construct()
    {
        $conf = Config::get('geetest') ?: [];
        
        $this->config = array_merge([
            'Captcha'      => '',
            'CaptchaKey'   => '',
            'ApiServer'    => '',
            'notBefore'    => 300,
            'TableName'    => 'ValidateTable',
            'cache_prefix' => 'geetest:token:',
            'code_expire'  => 300, // 默认5分钟
        ], $conf);
    }

    /**
     * 时间 2026-01-04
     * @title 生成唯一Token
     * @desc 生成唯一Token
     * @author yjwmidc
     * @version v1
     * @param string gid - 群组ID
     * @param string uid - 用户ID
     * @return string token
     */
    public function generateToken(string $gid, string $uid)
    {
        return md5($gid . $uid . microtime(true) . uniqid() . mt_rand(1000, 9999));
    }

    /**
     * 时间 2026-01-04
     * @title 生成6位验证码
     * @desc 生成6位数字验证码
     * @author yjwmidc
     * @version v1
     * @return string code
     */
    public function generateCode()
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 时间 2026-01-04
     * @title 保存验证码数据
     * @desc 保存验证码数据到数据库
     * @author yjwmidc
     * @version v1
     * @param string token - 唯一token
     * @param array data - 保存的数据
     * @return bool result - 是否成功
     */
    public function saveVerifyData(string $token, array $data)
    {
        $now = time();
        
        $insertData = [
            'token'      => $token,
            'group_id'   => $data['group_id'] ?? '',
            'user_id'    => $data['user_id'] ?? '',
            'code'       => $data['code'] ?? null,
            'verified'   => !empty($data['verified']) ? 1 : 0,
            'used'       => 0,
            'ip'         => $data['ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'extra'      => isset($data['extra']) ? json_encode($data['extra'], JSON_UNESCAPED_UNICODE) : null,
            'expire_at'  => $now + $this->config['code_expire'],
            'created_at' => $now,
        ];
        
        $res = Db::name($this->config['TableName'])->insert($insertData);
        
        if ($res) {
            $cacheKey = $this->config['cache_prefix'] . $token;
            Cache::set($cacheKey, $this->formatData($insertData), $this->config['code_expire']);
            return true;
        }
        
        return false;
    }

    /**
     * 时间 2026-01-04
     * @title 获取验证码数据
     * @desc 根据token获取验证码数据
     * @author yjwmidc
     * @version v1
     * @param string token - 唯一token
     * @return array data - 验证数据
     */
    public function getVerifyData(string $token)
    {  
        $cacheKey = $this->config['cache_prefix'] . $token;
        
        // 读取缓存
        $cachedData = Cache::get($cacheKey);
        if (!empty($cachedData)) {
            return $cachedData;
        }
        
        // 缓存miss，查询数据库
        $result = Db::name($this->config['TableName'])
            ->where('token', $token)
            ->find();
        
        if (!$result) {
            return null;
        }
        
        // 检查是否过期
        if ($result['expire_at'] < time()) {
            $this->deleteVerifyData($token);
            return null;
        }
        
        // 格式化数据并回写缓存
        $formattedData = $this->formatData($result);
        $ttl = $result['expire_at'] - time();
        
        if ($ttl > 0) {
            Cache::set($cacheKey, $formattedData, $ttl);
        }

        return $formattedData;
    }

    /**
     * 时间 2026-01-04
     * @title 通过验证码查找数据
     * @desc 通过验证码查找有效的数据
     * @author yjwmidc
     * @version v1
     * @param string code - 验证码
     * @param string gid - 群号
     * @return array data - 验证数据
     */
    public function findByCode(string $code, string $gid)
    {
        $result = Db::name($this->config['TableName'])
            ->where('code', $code)
            ->where('group_id', $gid)
            ->where('verified', 1)
            ->where('used', 0)
            ->where('expire_at', '>', time())
            ->find();
        
        return $result ? $this->formatData($result) : null;
    }

    /**
     * 时间 2026-01-04
     * @title 更新验证数据
     * @desc 更新验证数据
     * @author yjwmidc
     * @version v1
     * @param string token - 唯一token
     * @param array new_verify_data - 新数据
     * @return bool result - 是否成功
     */
    public function updateVerifyData(string $token, array $newVerifyData)
    {
        if (isset($newVerifyData['verified'])) {
            $newVerifyData['verified'] = $newVerifyData['verified'] ? 1 : 0;
        }
        
        if (isset($newVerifyData['extra']) && is_array($newVerifyData['extra'])) {
            $newVerifyData['extra'] = json_encode($newVerifyData['extra'], JSON_UNESCAPED_UNICODE);
        }
        
        $newVerifyData['updated_at'] = time();
        
        $res = Db::name($this->config['TableName'])
            ->where('token', $token)
            ->update($newVerifyData);
            
        if ($res !== false) {
            Cache::delete($this->config['cache_prefix'] . $token);
            return true;
        }
        
        return false;
    }

    /**
     * 时间 2026-01-04
     * @title 删除验证数据
     * @desc 根据token删除验证数据
     * @author yjwmidc
     * @version v1
     * @param string token - 唯一token
     * @return bool result - 是否成功
     */
    public function deleteVerifyData(string $token)
    {
        Cache::delete($this->config['cache_prefix'] . $token);
        
        return Db::name($this->config['TableName'])
            ->where('token', $token)
            ->delete() !== false;
    }

    /**
     * 时间 2026-01-04
     * @title 标记验证码已使用
     * @desc 标记验证码为已使用状态
     * @author yjwmidc
     * @version v1
     * @param string code - 验证码
     * @param string gid - 群号
     * @return bool result - 是否成功
     */
    public function setUsed(string $code, string $gid)
    {
        $token = Db::name($this->config['TableName'])
            ->where('code', $code)
            ->where('group_id', $gid)
            ->where('used', 0)
            ->value('token');
            
        if (!$token) {
            return false;
        }
        
        $res = Db::name($this->config['TableName'])
            ->where('token', $token)
            ->update([
                'used'       => 1,
                'used_at'    => time(),
                'updated_at' => time(),
            ]);
            
        if ($res) {
            Cache::delete($this->config['cache_prefix'] . $token);
            return true;
        }
        
        return false;
    }

    /**
     * 时间 2026-01-04
     * @title 验证极验数据
     * @desc 调用极验API验证前端参数
     * @author yjwmidc
     * @version v1
     * @param array params - 前端提交的极验参数
     * @return bool result - 验证是否通过
     */
    public function verifyGeetest(array $params)
    {
        //校验参数
        if (empty($params['Batch']) || empty($params['CaptchaOutput']) || 
            empty($params['PassTokens']) || empty($params['GenTimes'])) {
            return false;
        }
    
        //获取配置
        $captchaId = $this->config['Captcha'];
        $captchaKey = $this->config['CaptchaKey'];

        if (empty($captchaId) || empty($captchaKey)) {
            return false;
        }
        
        $signToken = hash_hmac('sha256', $params['Batch'], $captchaKey);
    
        //接口只支持蛇形 在这里修改一下
        $postData = [
            'lot_number'     => $params['Batch'],
            'captcha_output' => $params['CaptchaOutput'],
            'pass_token'     => $params['PassTokens'],
            'gen_time'       => $params['GenTimes'],
            'sign_token'     => $signToken
        ];

        //构造请求 URL
        $baseUrl = rtrim($this->config['ApiServer'], '/') . '/validate';
        $url = $baseUrl . '?captcha_id=' . $captchaId;
    
        // 6. 调用你的公共 curl 函数
        // curl($url, $data = [], $timeout = 30, $request = 'POST', $header = [], $curlFile = false)
        $result = curl($url, $postData, 5, 'POST', ['Content-Type: application/x-www-form-urlencoded']);
    
        $response = json_decode($result['content'], true);
    
        return true;
    }

    /**
     * 时间 2026-01-04
     * @title 获取CaptchaId
     * @desc 获取极验CaptchaId
     * @author yjwmidc
     * @version v1
     * @return string captcha_id
     */
    public function getCaptchaId()
    {
        return $this->config['Captcha'];
    }

    /**
     * 时间 2026-01-04
     * @title 获取验证码有效期
     * @desc 获取配置中的验证码有效期
     * @author yjwmidc
     * @version v1
     * @return int expire
     */
    public function getCodeExpire()
    {
        return $this->config['code_expire'];
    }

    /**
     * 时间 2026-01-04
     * @title 清理过期验证信息
     * @desc 删除过期的数据记录
     * @author yjwmidc
     * @version v1
     * @param int days - 清理多少天前的记录
     * @return int count - 删除的记录数
     */
    public function cleanExpiredCodes(int $days = 7)
    {
        $expireTime = time() - ($days * 86400);
        return Db::name($this->config['TableName'])
            ->where('created_at', '<', $expireTime)
            ->delete();
    }

    /**
     * @title 格式化数据
     * @desc 格式化数据库返回的数据类型
     * @param array data
     * @return array formatted_data
     */
    protected function formatData(array $data)
    {
        $data['verified'] = (bool)($data['verified'] ?? 0);
        $data['used'] = (bool)($data['used'] ?? 0);
        
        if (isset($data['extra']) && is_string($data['extra'])) {
            $data['extra'] = json_decode($data['extra'], true);
        } elseif (!isset($data['extra'])) {
            $data['extra'] = null;
        }

        return $data;
    }
}