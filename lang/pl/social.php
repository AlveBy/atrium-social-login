<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Logowanie przez media społecznościowe',

    'guards' => [
        'admin' => 'Panel administratora',
        'account' => 'Konto osobiste',
    ],

    'login' => [
        'button' => 'Zaloguj się przez :provider',
    ],

    'menu' => [
        'title' => 'Logowanie przez media społecznościowe',
        'accounts' => 'Połączone konta',
        'providers' => 'Dostawcy',
    ],

    'settings' => [
        'description' => 'Adresy e-mail z jakich domen mogą otworzyć istniejące konto, jeśli dostawca zweryfikował dany adres. Dostawców konfiguruje się w sekcji Logowanie przez media społecznościowe → Dostawcy.',
        'domains' => 'Dozwolone domeny e-mail: :who',
        'domains_hint' => 'Po jednej w wierszu lub oddzielone przecinkami, np. example.com. Puste: dowolna domena. W panelu administratora w ten sposób nikt nigdy nie jest tworzony.',
    ],

    'accounts' => [
        'title' => 'Połączone konta',
        'hint' => 'Logujesz się kontem w innym serwisie zamiast hasłem. Drugi krok logowania, jeśli go masz, nadal obowiązuje.',
        'linked_title' => 'Połączone',
        'none' => 'Nie połączono jeszcze żadnego konta.',
        'available' => 'Połącz inne konto',
        'link' => 'Połącz konto :provider',
        'unlink' => 'Rozłącz',
        'linked' => 'Konto :provider zostało połączone. Możesz się teraz nim logować.',
        'unlinked' => 'Konto :provider zostało rozłączone.',
        'last_method' => 'To Twój jedyny sposób logowania. Najpierw ustaw hasło lub połącz inne konto.',
        'last_used' => 'Ostatnio użyto',
        'never' => 'nigdy',
    ],

    'providers' => [
        'title' => 'Dostawcy logowania przez media społecznościowe',
        'hint' => 'Zarejestruj aplikację u dostawcy, wpisz tutaj jej Client ID i sekret oraz włącz przycisk tam, gdzie ma być widoczny.',
        'provider' => 'Dostawca',
        'status' => 'Status',
        'on' => 'Włączony',
        'off' => 'Wyłączony',
        'not_set_up' => 'Nieskonfigurowany',
        'edit' => 'Skonfiguruj',
        'save' => 'Zapisz',
        'saved' => 'Zapisano ustawienia :provider.',
        'enabled_for' => 'Pokaż przycisk na ekranie logowania do',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Sekret jest zapisany. Zostaw pole puste, aby go zachować.',
        'secret_none' => 'Nie zapisano jeszcze sekretu.',
        'redirect' => 'Adresy przekierowania',
        'redirect_hint' => 'Wpisz te adresy u dostawcy jako dozwolone adresy przekierowania (callback), dokładnie w takiej postaci.',
        'settings' => [
            'tenant' => 'Dzierżawca (tenant)',
            'tenant_hint' => 'Identyfikator katalogu (tenant) lub domena Twojej organizacji. Puste: dowolne konto służbowe lub szkolne.',
            'host' => 'Adres GitLab',
            'host_hint' => 'Dla własnego serwera GitLab, np. https://gitlab.example.com. Puste: gitlab.com.',
            'issuer' => 'Adres wystawcy (Issuer URL)',
            'issuer_hint' => 'Adres serwera OpenID Connect; pod nim musi otwierać się /.well-known/openid-configuration.',
            'label' => 'Nazwa przycisku',
            'label_hint' => 'Co użytkownicy zobaczą na przycisku, np. nazwę logowania w Twojej firmie.',
        ],
        'hints' => [
            'apple' => 'Client ID: Services ID. Client secret: podpisany JWT utworzony z Twojego klucza; Apple akceptuje go najwyżej przez sześć miesięcy, więc odnów go na czas.',
            'telegram' => 'Client ID: nazwa użytkownika bota. Client secret: token bota. Domenę witryny dla bota ustawisz poleceniem /setdomain w BotFather.',
            'entra' => 'Zalogować się mogą tylko konta służbowe i szkolne z podanego niżej tenanta. Puste pole: dowolne konto służbowe lub szkolne.',
            'microsoft' => 'Osobiste, służbowe i szkolne konta Microsoft. Ich adresy e-mail nie są uznawane za zweryfikowane, więc konto trzeba najpierw połączyć w profilu.',
            'vkid' => 'Client ID: identyfikator aplikacji. Client secret: chroniony klucz. VK ID nie gwarantuje adresów e-mail, więc konto trzeba najpierw połączyć w profilu.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik i każdy inny serwer OpenID Connect.',
        ],
    ],

    'refused' => [
        'state' => 'Logowanie przez :provider wygasło lub zostało rozpoczęte gdzie indziej. Spróbuj ponownie.',
        'cancelled' => 'Logowanie przez :provider zostało anulowane.',
        'failed' => ':provider nie potwierdził logowania. Spróbuj ponownie.',
        'no_email' => ':provider nie udostępnił adresu e-mail, a żadne konto nie jest z nim połączone. Zaloguj się w inny sposób i połącz go w swoim profilu.',
        'unverified' => ':provider nie potwierdził adresu e-mail, a żadne konto nie jest z nim połączone. Zaloguj się w inny sposób i połącz go w swoim profilu.',
        'inactive' => 'To konto jest wyłączone.',
        'domain' => 'Adresy z tej domeny nie mogą się tu logować przez :provider.',
        'no_account' => 'Nie ma konta dla tego adresu :provider.',
        'taken' => 'To konto :provider jest już połączone z kimś innym.',
    ],

    'bounce' => [
        'title' => 'Logowanie…',
        'working' => 'Chwileczkę, kończymy logowanie.',
        'continue' => 'Kontynuuj',
    ],
];
