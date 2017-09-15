<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;

class GenerateController
{
    const REDIS_RED_ENVELOP_QUEUE = 'un';

    public function __invoke()
    {
        Redis::del(self::REDIS_RED_ENVELOP_QUEUE);
        for ($i = 0; $i < 1000; $i++) {
            $redEnvelopeData = [
                'id' => $i + 10000,
                'money' => 1 + $i,
            ];
            Redis::lpush(self::REDIS_RED_ENVELOP_QUEUE, json_encode($redEnvelopeData));
        }

        $length = Redis::llen(self::REDIS_RED_ENVELOP_QUEUE);
        $data = Redis::lrange(self::REDIS_RED_ENVELOP_QUEUE, 0, $length);
        return compact('length', 'data');
    }
}