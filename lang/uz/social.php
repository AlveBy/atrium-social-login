<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Ijtimoiy tarmoq orqali kirish',

    'guards' => [
        'admin' => 'Administrator paneli',
        'account' => 'Shaxsiy kabinet',
    ],

    'login' => [
        'button' => ':provider orqali kirish',
    ],

    'menu' => [
        'title' => 'Ijtimoiy tarmoq orqali kirish',
        'accounts' => 'Bogʻlangan akkauntlar',
        'providers' => 'Provayderlar',
    ],

    'settings' => [
        'description' => 'Provayder tasdiqlagan manzil boʻyicha mavjud akkauntni qaysi domenlardagi elektron pochta manzillari ocha oladi. Provayderlar «Ijtimoiy tarmoq orqali kirish → Provayderlar» boʻlimida sozlanadi.',
        'domains' => 'Ruxsat etilgan pochta domenlari: :who',
        'domains_hint' => 'Har qatorga bittadan yoki vergul bilan, masalan example.com. Boʻsh boʻlsa: istalgan domen. Bu yoʻl bilan administrator panelida hech kim yaratilmaydi.',
    ],

    'accounts' => [
        'title' => 'Bogʻlangan akkauntlar',
        'hint' => 'Parol oʻrniga boshqa xizmatdagi akkauntingiz bilan kiring. Kirishning qoʻshimcha bosqichi yoqilgan boʻlsa, u baribir talab qilinadi.',
        'linked_title' => 'Bogʻlangan',
        'none' => 'Hozircha birorta akkaunt bogʻlanmagan.',
        'available' => 'Yana bir akkauntni bogʻlash',
        'link' => ':provider akkauntini bogʻlash',
        'unlink' => 'Uzish',
        'linked' => ':provider bogʻlandi. Endi u orqali kira olasiz.',
        'unlinked' => ':provider bogʻlanishi uzildi.',
        'last_method' => 'Bu sizning yagona kirish usulingiz. Avval parol oʻrnating yoki boshqa akkauntni bogʻlang.',
        'last_used' => 'Oxirgi foydalanish',
        'never' => 'hech qachon',
    ],

    'providers' => [
        'title' => 'Ijtimoiy tarmoq orqali kirish provayderlari',
        'hint' => 'Provayderda ilovani roʻyxatdan oʻtkazing, uning Client ID va sirini shu yerga kiriting hamda tugma koʻrinishi kerak boʻlgan joyda uni yoqing.',
        'provider' => 'Provayder',
        'status' => 'Holati',
        'on' => 'Yoqilgan',
        'off' => 'Oʻchirilgan',
        'not_set_up' => 'Sozlanmagan',
        'edit' => 'Sozlash',
        'save' => 'Saqlash',
        'saved' => ':provider sozlamalari saqlandi.',
        'enabled_for' => 'Tugmani quyidagi kirish ekranida koʻrsatish',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Sir saqlangan. Oʻzgartirmaslik uchun maydonni boʻsh qoldiring.',
        'secret_none' => 'Sir hali saqlanmagan.',
        'redirect' => 'Qayta yoʻnaltirish manzillari',
        'redirect_hint' => 'Bu manzillarni provayderda ruxsat etilgan qayta yoʻnaltirish (callback) manzillari sifatida aynan shu koʻrinishda kiriting.',
        'settings' => [
            'tenant' => 'Katalog (tenant)',
            'tenant_hint' => 'Tashkilotingizning katalog (tenant) identifikatori yoki domeni. Boʻsh boʻlsa: istalgan ish yoki oʻquv akkaunti.',
            'host' => 'GitLab manzili',
            'host_hint' => 'Oʻz serveringizdagi GitLab uchun, masalan https://gitlab.example.com. Boʻsh boʻlsa: gitlab.com.',
            'issuer' => 'Chiqaruvchi manzili (Issuer URL)',
            'issuer_hint' => 'OpenID Connect serveri manzili; shu manzil boʻyicha /.well-known/openid-configuration ochilishi kerak.',
            'label' => 'Tugma nomi',
            'label_hint' => 'Foydalanuvchilar tugmada nimani koʻradi, masalan kompaniyangiz kirish tizimining nomi.',
        ],
        'hints' => [
            'apple' => 'Client ID: Services ID. Client secret: kalitingizdan yaratilgan imzolangan JWT; Apple uni olti oydan ortiq qabul qilmaydi, shuning uchun uni oldindan yangilab turing.',
            'telegram' => 'Client ID: botning foydalanuvchi nomi. Client secret: bot tokeni. Bot uchun sayt domenini BotFather ichida /setdomain buyrugʻi bilan belgilang.',
            'entra' => 'Faqat quyida koʻrsatilgan tenant-ning ish va oʻquv akkauntlari kira oladi. Maydon boʻsh boʻlsa — istalgan ish yoki oʻquv akkaunti.',
            'microsoft' => 'Microsoftning shaxsiy, ish va oʻquv akkauntlari. Ularning pochta manzillari tasdiqlangan hisoblanmaydi, shuning uchun akkauntni avval profildan bogʻlash kerak.',
            'vkid' => 'Client ID: ilova ID. Client secret: himoyalangan kalit. VK ID pochta manzillariga kafolat bermaydi, shuning uchun akkauntni avval profildan bogʻlash kerak.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik va boshqa istalgan OpenID Connect serveri.',
        ],
    ],

    'refused' => [
        'state' => ':provider orqali kirish muddati tugagan yoki boshqa joyda boshlangan. Qaytadan urinib koʻring.',
        'cancelled' => ':provider orqali kirish bekor qilindi.',
        'failed' => ':provider kirishni tasdiqlamadi. Qaytadan urinib koʻring.',
        'no_email' => ':provider elektron pochta manzilini bermadi, unga bogʻlangan akkaunt ham yoʻq. Boshqa usulda kiring va uni profilda bogʻlang.',
        'unverified' => ':provider elektron pochta manzilini tasdiqlamadi, unga bogʻlangan akkaunt ham yoʻq. Boshqa usulda kiring va uni profilda bogʻlang.',
        'inactive' => 'Bu akkaunt oʻchirib qoʻyilgan.',
        'domain' => 'Bu domendagi manzillar bu yerda :provider orqali kira olmaydi.',
        'no_account' => ':provider dagi bu manzil uchun akkaunt yoʻq.',
        'taken' => 'Bu :provider akkaunti allaqachon boshqa foydalanuvchiga bogʻlangan.',
    ],

    'bounce' => [
        'title' => 'Kirilmoqda…',
        'working' => 'Bir lahza, kirish yakunlanmoqda.',
        'continue' => 'Davom etish',
    ],
];
