<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'ソーシャルログイン',
    'back_to_panel' => 'パネルに戻る',

    'guards' => [
        'admin' => '管理パネル',
        'account' => 'マイページ',
    ],

    'login' => [
        'button' => ':provider でログイン',
    ],

    'menu' => [
        'title' => 'ソーシャルログイン',
        'accounts' => '連携済みアカウント',
        'providers' => 'プロバイダー',
    ],

    'settings' => [
        'description' => 'プロバイダーが確認済みのメールアドレスで、既存のアカウントにログインできるメールドメインを指定します。プロバイダーは「ソーシャルログイン → プロバイダー」で設定します。',
        'domains' => '許可するメールドメイン: :who',
        'domains_hint' => '1行に1つ、またはカンマ区切りで入力します（例: example.com）。空欄の場合はすべてのドメインが対象です。この方法で管理パネルにアカウントが作成されることはありません。',
    ],

    'accounts' => [
        'title' => '連携済みアカウント',
        'hint' => 'パスワードの代わりに、他のサービスのアカウントでログインできます。2段階目のログイン手順を設定している場合は、引き続き必要です。',
        'linked_title' => '連携済み',
        'none' => '連携しているアカウントはまだありません。',
        'available' => '別のアカウントを連携',
        'link' => ':provider を連携',
        'unlink' => '連携を解除',
        'linked' => ':provider を連携しました。今後はこれでログインできます。',
        'unlinked' => ':provider の連携を解除しました。',
        'last_method' => 'これが唯一のログイン方法です。先にパスワードを設定するか、別のアカウントを連携してください。',
        'last_used' => '最終利用',
        'never' => '未使用',
    ],

    'providers' => [
        'title' => 'ソーシャルログインのプロバイダー',
        'hint' => 'プロバイダー側でアプリケーションを登録し、その Client ID とシークレットをここに入力して、ボタンを表示したい場所で有効にします。',
        'provider' => 'プロバイダー',
        'status' => 'ステータス',
        'on' => '有効',
        'off' => '無効',
        'not_set_up' => '未設定',
        'edit' => '設定',
        'save' => '保存',
        'saved' => ':provider を保存しました。',
        'enabled_for' => 'ボタンを表示するログイン画面',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'シークレットは保存済みです。変更しない場合は空欄のままにしてください。',
        'secret_none' => 'シークレットはまだ保存されていません。',
        'redirect' => 'リダイレクト URL',
        'redirect_hint' => 'これらのアドレスを、プロバイダー側で許可するリダイレクト（コールバック）URL として、表示のとおりに入力してください。',
        'settings' => [
            'tenant' => 'テナント',
            'tenant_hint' => '組織のディレクトリ（テナント）ID またはドメイン。空欄の場合は、すべての職場または学校アカウントが対象です。',
            'host' => 'GitLab のアドレス',
            'host_hint' => 'セルフホストの GitLab の場合に入力します（例: https://gitlab.example.com）。空欄の場合は gitlab.com です。',
            'issuer' => '発行者 URL（Issuer URL）',
            'issuer_hint' => 'OpenID Connect サーバーのアドレス。この配下で /.well-known/openid-configuration を開ける必要があります。',
            'label' => 'ボタン名',
            'label_hint' => 'ボタンに表示する名前です（例: 自社のログインサービス名）。',
        ],
        'hints' => [
            'apple' => 'Client ID: Services ID。Client secret: キーから作成した署名付き JWT。Apple は最長6か月までしか受け付けないため、期限前に更新してください。',
            'telegram' => 'Client ID: ボットのユーザー名。Client secret: ボットのトークン。ボットに設定するサイトのドメインは、BotFather で /setdomain を使って指定します。',
            'entra' => '下に入力したテナントの職場または学校アカウントのみログインできます。空欄の場合は、すべての職場または学校アカウントがログインできます。',
            'microsoft' => '個人、職場、学校の Microsoft アカウント。メールアドレスは確認済みとして扱われないため、先にプロフィールからアカウントを連携する必要があります。',
            'vkid' => 'Client ID: アプリ ID。Client secret: 保護キー。VK ID はメールアドレスを保証しないため、先にプロフィールからアカウントを連携する必要があります。',
            'oidc' => 'Keycloak、Okta、Auth0、Authentik、その他すべての OpenID Connect サーバー。',
        ],
    ],

    'refused' => [
        'state' => ':provider でのログインは期限切れか、別の場所で開始されたものです。もう一度お試しください。',
        'cancelled' => ':provider でのログインはキャンセルされました。',
        'failed' => ':provider がログインを確認できませんでした。もう一度お試しください。',
        'no_email' => ':provider からメールアドレスが提供されず、連携済みのアカウントもありません。別の方法でログインし、プロフィールで連携してください。',
        'unverified' => ':provider がメールアドレスを確認しておらず、連携済みのアカウントもありません。別の方法でログインし、プロフィールで連携してください。',
        'inactive' => 'このアカウントは無効になっています。',
        'domain' => 'このドメインのアドレスでは、ここで :provider によるログインはできません。',
        'no_account' => 'この :provider のアドレスに対応するアカウントはありません。',
        'taken' => 'この :provider アカウントは、すでに他のユーザーに連携されています。',
    ],

    'bounce' => [
        'title' => 'ログイン中…',
        'working' => 'しばらくお待ちください。ログインを完了しています。',
        'continue' => '続ける',
    ],
];
