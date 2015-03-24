# V2EX 自动领取每日奖励

一段基于 `curl_share_*` 系列函数的 PHP 模拟登录 V2EX 代码，并自动领取每日奖励金币，无需生成临时 cookie 文件。

### 运行要求：

+ PHP >= 5.5
+ 支持 `curl_share_init` 等函数


### 使用方式：

实例化一个对象：`$v2ex = new v2ex('username', 'password');` 即可。


### 吐槽：

新浪的 SAE 虽然已经提供了 PHP 5.6 版本，但却不支持 `curl_share_*` 系列函数，真是日了狗了。
