<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => '社交账号登录',
    'back_to_panel' => '返回管理面板',

    'guards' => [
        'admin' => '管理面板',
        'account' => '个人中心',
    ],

    'login' => [
        'button' => '使用 :provider 登录',
    ],

    'menu' => [
        'title' => '社交账号登录',
        'accounts' => '已绑定账号',
        'providers' => '登录服务商',
    ],

    'settings' => [
        'description' => '服务商已验证的邮箱地址中，哪些域名的地址可以进入已有账号。服务商在“社交账号登录 → 登录服务商”中设置。',
        'domains' => '允许的邮箱域名：:who',
        'domains_hint' => '每行一个，或用英文逗号分隔，例如 example.com。留空：不限域名。通过这种方式不会在管理面板中创建任何账号。',
    ],

    'accounts' => [
        'title' => '已绑定账号',
        'hint' => '可使用其他服务的账号代替密码登录。如果您启用了二次登录验证，仍然需要完成。',
        'linked_title' => '已绑定',
        'none' => '尚未绑定任何账号。',
        'available' => '绑定其他账号',
        'link' => '绑定 :provider',
        'unlink' => '解除绑定',
        'linked' => '已绑定 :provider，现在可以用它登录。',
        'unlinked' => '已解除 :provider 的绑定。',
        'last_method' => '这是您唯一的登录方式。请先设置密码或绑定其他账号。',
        'last_used' => '上次使用',
        'never' => '从未',
    ],

    'providers' => [
        'title' => '社交账号登录服务商',
        'hint' => '在服务商处注册应用，在此填写其 Client ID 和密钥，并在需要显示按钮的位置启用它。',
        'provider' => '服务商',
        'status' => '状态',
        'on' => '已启用',
        'off' => '已关闭',
        'not_set_up' => '未设置',
        'edit' => '设置',
        'save' => '保存',
        'saved' => '已保存 :provider。',
        'enabled_for' => '在以下登录页面显示按钮',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => '已保存密钥。留空则保持不变。',
        'secret_none' => '尚未保存密钥。',
        'redirect' => '重定向 URL',
        'redirect_hint' => '请在服务商处将以下地址填为允许的重定向（回调）URL，务必与此处显示的完全一致。',
        'settings' => [
            'tenant' => '租户（Tenant）',
            'tenant_hint' => '您所在组织的目录（租户）ID 或域名。留空：任何工作或学校账号。',
            'host' => 'GitLab 地址',
            'host_hint' => '自建 GitLab 时填写，例如 https://gitlab.example.com。留空：gitlab.com。',
            'issuer' => '签发者地址（Issuer URL）',
            'issuer_hint' => 'OpenID Connect 服务器的地址；该地址下必须能打开 /.well-known/openid-configuration。',
            'label' => '按钮名称',
            'label_hint' => '按钮上显示的文字，例如贵公司登录入口的名称。',
        ],
        'hints' => [
            'apple' => 'Client ID：Services ID。Client secret：由您的密钥生成的已签名 JWT；Apple 最多接受六个月有效期，请及时更新。',
            'telegram' => 'Client ID：机器人的用户名。Client secret：机器人的令牌。请在 BotFather 中用 /setdomain 为机器人设置网站域名。',
            'entra' => '只有下方所填 tenant 的工作和学校账号可以登录。留空则任何工作或学校账号均可登录。',
            'microsoft' => '个人、工作和学校的 Microsoft 账号。它们的邮箱地址不视为已验证，因此需要先在个人资料中绑定账号。',
            'vkid' => 'Client ID：应用 ID。Client secret：受保护的密钥。VK ID 不保证邮箱地址真实有效，因此需要先在个人资料中绑定账号。',
            'oidc' => 'Keycloak、Okta、Auth0、Authentik 以及任何其他 OpenID Connect 服务器。',
        ],
    ],

    'refused' => [
        'state' => '使用 :provider 登录已过期，或是在其他地方发起的。请重试。',
        'cancelled' => '已取消使用 :provider 登录。',
        'failed' => ':provider 未确认此次登录。请重试。',
        'no_email' => ':provider 未提供邮箱地址，且没有账号绑定到它。请用其他方式登录，并在个人资料中绑定。',
        'unverified' => ':provider 未验证该邮箱地址，且没有账号绑定到它。请用其他方式登录，并在个人资料中绑定。',
        'inactive' => '此账号已停用。',
        'domain' => '此域名的地址不能在这里使用 :provider 登录。',
        'no_account' => '此 :provider 地址没有对应的账号。',
        'taken' => '此 :provider 账号已绑定到其他人。',
    ],

    'bounce' => [
        'title' => '正在登录…',
        'working' => '请稍候，正在完成登录。',
        'continue' => '继续',
    ],
];
