<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Sosyal hesapla giriş',
    'back_to_panel' => 'Panele dön',

    'guards' => [
        'admin' => 'Yönetim paneli',
        'account' => 'Kişisel hesap',
    ],

    'login' => [
        'button' => ':provider ile giriş yap',
    ],

    'menu' => [
        'title' => 'Sosyal hesapla giriş',
        'accounts' => 'Bağlı hesaplar',
        'providers' => 'Sağlayıcılar',
    ],

    'settings' => [
        'description' => 'Sağlayıcının doğruladığı bir adresle, hangi e-posta alan adlarının mevcut bir hesabı açabileceği. Sağlayıcılar Sosyal hesapla giriş → Sağlayıcılar bölümünde ayarlanır.',
        'domains' => 'İzin verilen e-posta alan adları: :who',
        'domains_hint' => 'Her satıra bir tane veya virgülle ayırarak, örneğin example.com. Boş: herhangi bir alan adı. Yönetim panelinde bu yolla hiç kimse oluşturulmaz.',
    ],

    'accounts' => [
        'title' => 'Bağlı hesaplar',
        'hint' => 'Parola yerine başka bir hizmetteki hesabınızla giriş yapın. İkinci bir giriş adımınız varsa, o yine geçerlidir.',
        'linked_title' => 'Bağlı',
        'none' => 'Henüz bağlı bir hesap yok.',
        'available' => 'Başka bir hesap bağla',
        'link' => ':provider hesabını bağla',
        'unlink' => 'Bağlantıyı kaldır',
        'linked' => ':provider bağlandı. Artık onunla giriş yapabilirsiniz.',
        'unlinked' => ':provider bağlantısı kaldırıldı.',
        'last_method' => 'Bu, giriş yapabileceğiniz tek yol. Önce bir parola belirleyin veya başka bir hesap bağlayın.',
        'last_used' => 'Son kullanım',
        'never' => 'hiç',
    ],

    'providers' => [
        'title' => 'Sosyal hesapla giriş sağlayıcıları',
        'hint' => 'Sağlayıcıda bir uygulama kaydedin, Client ID ve secret değerlerini buraya girin ve düğmenin görünmesi gereken yerde açın.',
        'provider' => 'Sağlayıcı',
        'status' => 'Durum',
        'on' => 'Açık',
        'off' => 'Kapalı',
        'not_set_up' => 'Ayarlanmadı',
        'edit' => 'Ayarla',
        'save' => 'Kaydet',
        'saved' => ':provider kaydedildi.',
        'enabled_for' => 'Düğmeyi şu giriş ekranında göster:',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Bir secret kayıtlı. Korumak için alanı boş bırakın.',
        'secret_none' => 'Henüz kayıtlı bir secret yok.',
        'redirect' => 'Yönlendirme URL’leri',
        'redirect_hint' => 'Bu adresleri sağlayıcıda izin verilen yönlendirme (callback) URL’leri olarak, tam olarak göründüğü gibi girin.',
        'settings' => [
            'tenant' => 'Kiracı (tenant)',
            'tenant_hint' => 'Kuruluşunuzun dizin (tenant) kimliği veya alan adı. Boş: herhangi bir iş veya okul hesabı.',
            'host' => 'GitLab adresi',
            'host_hint' => 'Kendi sunucunuzdaki GitLab için, örneğin https://gitlab.example.com. Boş: gitlab.com.',
            'issuer' => 'Yayıncı adresi (Issuer URL)',
            'issuer_hint' => 'OpenID Connect sunucusunun adresi; /.well-known/openid-configuration bu adres altında açılmalıdır.',
            'label' => 'Düğme adı',
            'label_hint' => 'Kullanıcıların düğmede göreceği ad, örneğin şirketinizin giriş sisteminin adı.',
        ],
        'hints' => [
            'apple' => 'Client ID: Services ID. Client secret: anahtarınızdan üretilen imzalı JWT; Apple en fazla altı ay kabul eder, bu yüzden zamanında yenileyin.',
            'telegram' => 'Client ID: botun kullanıcı adı. Client secret: botun belirteci (token). Bot için site alan adını BotFather’da /setdomain ile belirleyin.',
            'entra' => 'Yalnızca aşağıda girilen tenant’ın iş ve okul hesapları giriş yapabilir. Boş bırakılırsa: herhangi bir iş veya okul hesabı.',
            'microsoft' => 'Kişisel, iş ve okul Microsoft hesapları. E-posta adresleri doğrulanmış sayılmaz, bu yüzden hesabın önce profilden bağlanması gerekir.',
            'vkid' => 'Client ID: uygulama kimliği. Client secret: korumalı anahtar. VK ID e-posta adreslerini güvence altına almaz, bu yüzden hesabın önce profilden bağlanması gerekir.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik ve diğer tüm OpenID Connect sunucuları.',
        ],
    ],

    'refused' => [
        'state' => ':provider ile giriş zaman aşımına uğradı veya başka bir yerde başlatıldı. Lütfen tekrar deneyin.',
        'cancelled' => ':provider ile giriş iptal edildi.',
        'failed' => ':provider girişi onaylamadı. Lütfen tekrar deneyin.',
        'no_email' => ':provider bir e-posta adresi paylaşmadı ve bu adrese bağlı bir hesap yok. Başka bir yolla giriş yapın ve profilinizden bağlayın.',
        'unverified' => ':provider e-posta adresini doğrulamadı ve bu adrese bağlı bir hesap yok. Başka bir yolla giriş yapın ve profilinizden bağlayın.',
        'inactive' => 'Bu hesap devre dışı.',
        'domain' => 'Bu alan adındaki adresler burada :provider ile giriş yapamaz.',
        'no_account' => 'Bu :provider adresi için bir hesap yok.',
        'taken' => 'Bu :provider hesabı zaten başka biriyle bağlı.',
    ],

    'bounce' => [
        'title' => 'Giriş yapılıyor…',
        'working' => 'Bir saniye, giriş tamamlanıyor.',
        'continue' => 'Devam',
    ],
];
