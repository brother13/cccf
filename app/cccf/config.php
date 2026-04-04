<?php

return [
    // +----------------------------------------------------------------------
    // | Token设置设置
    // +----------------------------------------------------------------------
    'dwid'=>1,
    'fycode'=>'613',
    'fyname'=>'沈阳市大东区人民法院',
    'token'                => [
        'prefix'         => 'web_token_',
        'token_name'    => 'WEB-TOKEN', //前台提前的token名
        'expire' => 86400 * 7, // 默认一个星期时间
    ],
    "upload" => [
        "dir" => "../public/uploads" // 这样可以共享目录
    ],
    'template' => [
        'path' => 'template'
    ],
    'doc' => [
        "path" => "docs"
    ],
    'cache' => [
        // 使用复合缓存类型
        'type' => 'complex',
        'default' => [
            // 驱动方式
            'type' => 'File',
            // 缓存保存目录
            'path' => CACHE_PATH,
        ],
        'token' => [
            'type' => 'File',
            // 缓存保存目录
            'path' => RUNTIME_PATH . "token/",
        ],
    ],
    // 新闻相关的配置
    
    'news'=>[
        'newsday'=>2, // 2天以内的新闻，标记为 isnew
    ],
    'onlinehour'=>2, // 2小时以内有过活动的，视为在线

    'autoLogoff_time'=>600, //一段时间不操作会自动退出的时间，单位为s，默认为3600s，即一个小时，为0时不判断自动退出 。仅在部分有效操作的情况下，才会刷新当前时间


    // 二次校验的时间等配置
    'safeauth'=>[
        // 通讯录开关
        'contact'=>[
            'enable'=>true,
            'time'=>180 // 默认 3分钟
        ],
        // 工具开关
        'tool'=>[
            'enable'=>true,
            'time'=>1800 // 默认 3分钟
        ]
    ],
    'template' => [
        // 台账列表里的文书信息
        'txcl' => [
            ['label' => "冻结文书", 'icon' => 'el-icon-download', 'file' => "冻结.docx"],
            ['label' => "扣划文书", 'icon' => 'el-icon-download', 'file' => "扣划.docx"],

        ]
    ]


];
