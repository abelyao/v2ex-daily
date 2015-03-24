<?php

# 自动领取 V2EX 每日奖励
# 利用 cURL share handle 特性无需生成 cookie 文件
# 要求 PHP >= 5.5
class v2ex
{
    # 网站主域名
    private $domain = 'https://www.v2ex.com';
    
    # 模拟 UserAgent
    private $useragent = 'Mozilla/5 (Macintosh; Intel Mac OS X 10) AppleWebKit/537 (KHTML, like Gecko) Chrome/41 Safari/537';

    # 共享 CURL 载体
    private $curls = null;

    # IP 地址
    private $ip = null;


    # 构造函数
    public function __construct($username, $password)
    {
        // 为本次请求生成一个随机 IP 地址，避免同 IP 上的猪队友
        $this->ip = join('.', [220, mt_rand(50, 250), mt_rand(50, 250), mt_rand(50, 250)]);

        // 初始化 CURL 共享载体
        if (is_null($this->curls))
        {
            $this->curls = curl_share_init();
            curl_share_setopt($this->curls, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
        }

        // 第一步：获取登录页面代码，提取登录用的 ONCE 参数
        $html = $this->request('/signin', '/');
        preg_match('@<input type="hidden" value="(.+?)" name="once" />@', $html, $match);
        $once = $match[1];

        // 第二步：请求登录，获取当前金币数量
        $data = [
            'u' => $username,
            'p' => $password,
            'once'  => $once,
            'next'  => '/',
        ];
        $html = $this->request('/signin', '/signin', $data);
        $balance['before'] = $this->balance($html) or die('登录失败！');
        
        // 第三步：进入每日任务页面，提取金币领取地址
        $html = $this->request('/mission/daily', '/');
        preg_match('@/mission/daily/redeem\?once=\d+@', $html, $match) or die('当日已经领取');
        $url = $match[0];

        // 第四步：领取每日奖励，获取新的金币数量
        $html = $this->request($url, '/mission/daily');
        $balance['after'] = $this->balance($html);

        // 判断领取是否成功
        stripos($html, '每日登录奖励已领取') or die('领取金币失败');

        // 连续登录天数
        preg_match('@已连续登录 \d+ 天@', $html, $match);
        $days = $match[0];

        // 输出结果
        echo $days.'，领取 '.($balance['after'] - $balance['before']).' 金币，账户余额 '.$balance['after'].' 金币';
    }


    # 析构函数
    public function __destruct()
    {
        // 关闭 CURL 共享载体
        if ($this->curls) curl_share_close($this->curls);
    }


    # 获取页面上当前金币的数量
    private function balance($html)
    {
        if (preg_match_all('@(\d+) <img.+?alt="[GSB]".+?/>@', $html, $matches)) return join('', $matches[1]);
        else return false;
    }


    # 封装 HTTP GET/POST 请求操作
    private function request($url, $referer, $data = false)
    {
        // 初始化 CURL 对象
        $curl = curl_init($this->domain.$url);
        curl_setopt_array($curl, [
            CURLOPT_SHARE           => $this->curls,
            CURLOPT_REFERER         => $this->domain.$referer,
            CURLOPT_USERAGENT       => $this->useragent,
            CURLOPT_HEADER          => 0,
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_FOLLOWLOCATION  => 1,
            CURLOPT_HTTPHEADER      => ['X-FORWARDED-FOR:'.$this->ip, 'CLIENT-IP:'.$this->ip],
        ]);

        // 如果为 $data 有参数则为 POST 请求
        if ($data && is_array($data))
        {
            curl_setopt_array($curl, [
                CURLOPT_POST        => 1,
                CURLOPT_POSTFIELDS  => $data,
            ]);
        }

        // 执行请求
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // 如果有错误则中断程序
        if ($httpcode != '200') die('请求失败：HTTP '.$httpcode);
        if ($response === false) die('请求失败：'.curl_error($curl));

        // 关闭 CURL 请求，返回响应内容
        curl_close($curl);
        return $response;
    }
}

?>
