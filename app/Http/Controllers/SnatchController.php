<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;

class SnatchController extends Controller
{
    /* 用户集合*/
    const REDIS_RED_ENVELOP_USER_HASH = 'users';

    /** Redis 键名 消费完的红包 */
    const REDIS_RED_ENVELOP_QUEUE_CONSUMED = 'consumed';

    //lua 脚本
    static $tryGetHongBaoScript = ''
        . "if redis.call('hexists', KEYS[3], KEYS[4]) ~= 0 then\n"
        . "return nil\n"
        . "else\n"
        . "local hongBao = redis.call('rpop', KEYS[1]);\n"
        . "if hongBao then\n"
        . "local x = cjson.decode(hongBao);\n"
        . "x['userId'] = KEYS[4];\n"
        . "local re = cjson.encode(x);\n"
        . "redis.call('hset', KEYS[3], KEYS[4], hongBao);\n"
        . "redis.call('lpush', KEYS[2], re);\n"
        . "return re;\n"
        . "end\n"
        . "end\n"
        . "return nil";

    //
    public function __invoke()
    {
        $userId = uniqid('user-');
        if (!$this->isSnatched($userId)) {
            $this->snatch($userId);
        } else {
            return 'quit';
        }
    }

    /**
     * 判断用户是否抢过红包
     *
     * @param string $userId
     * @return bool
     */
    protected function isSnatched(string $userId = ''): bool
    {
        return Redis::hexists(self::REDIS_RED_ENVELOP_USER_HASH, $userId);
    }

    /**
     * 抢红包
     *
     * @param string $userId
     */
    protected function snatch(string $userId)
    {
        return Redis::eval(self::$tryGetHongBaoScript,
            4,
            GenerateController::REDIS_RED_ENVELOP_QUEUE,
            self::REDIS_RED_ENVELOP_QUEUE_CONSUMED,
            self::REDIS_RED_ENVELOP_USER_HASH,
            $userId
        );
    }
}
