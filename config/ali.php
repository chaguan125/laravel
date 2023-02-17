<?php

/*
 * This file is part of the overtrue/laravel-wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'pid' => env('ALI_PID', ''),
    'appid' => env('ALI_APPID', ''),
    'private_key' => env('ALI_PRIVATE_KEY', ''),
    'public_key' => env('ALI_PUBLIC_KEY', ''),
    'encrypt_key' => env('ALI_ENCRYPT_KEY', ''),
    'notify_url' => env('ALI_NOTIFY_URL', ''),
];
