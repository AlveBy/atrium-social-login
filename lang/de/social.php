<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Anmeldung über soziale Netzwerke',

    'guards' => [
        'admin' => 'Administrationsbereich',
        'account' => 'Persönliches Konto',
    ],

    'login' => [
        'button' => 'Mit :provider anmelden',
    ],

    'menu' => [
        'title' => 'Anmeldung über soziale Netzwerke',
        'accounts' => 'Verknüpfte Konten',
        'providers' => 'Anbieter',
    ],

    'settings' => [
        'description' => 'Mit E-Mail-Adressen welcher Domains sich ein bestehendes Konto öffnen lässt, wenn der Anbieter die Adresse bestätigt hat. Die Anbieter werden unter Anmeldung über soziale Netzwerke → Anbieter eingerichtet.',
        'domains' => 'Erlaubte E-Mail-Domains: :who',
        'domains_hint' => 'Eine pro Zeile oder durch Kommas getrennt, z. B. example.com. Leer: jede Domain. Im Administrationsbereich wird auf diesem Weg nie ein Konto angelegt.',
    ],

    'accounts' => [
        'title' => 'Verknüpfte Konten',
        'hint' => 'Melden Sie sich statt mit dem Passwort mit einem Konto bei einem anderen Dienst an. Ein zweiter Anmeldeschritt, falls Sie einen eingerichtet haben, gilt weiterhin.',
        'linked_title' => 'Verknüpft',
        'none' => 'Noch kein Konto verknüpft.',
        'available' => 'Weiteres Konto verknüpfen',
        'link' => ':provider verknüpfen',
        'unlink' => 'Verknüpfung aufheben',
        'linked' => ':provider ist verknüpft. Sie können sich jetzt damit anmelden.',
        'unlinked' => 'Die Verknüpfung mit :provider wurde aufgehoben.',
        'last_method' => 'Dies ist Ihre einzige Möglichkeit, sich anzumelden. Legen Sie zuerst ein Passwort fest oder verknüpfen Sie ein anderes Konto.',
        'last_used' => 'Zuletzt verwendet',
        'never' => 'nie',
    ],

    'providers' => [
        'title' => 'Anbieter für die Anmeldung über soziale Netzwerke',
        'hint' => 'Registrieren Sie beim Anbieter eine Anwendung, tragen Sie hier die Client ID und das Secret ein und aktivieren Sie den Button dort, wo er sichtbar sein soll.',
        'provider' => 'Anbieter',
        'status' => 'Status',
        'on' => 'Aktiv',
        'off' => 'Inaktiv',
        'not_set_up' => 'Nicht eingerichtet',
        'edit' => 'Einrichten',
        'save' => 'Speichern',
        'saved' => ':provider wurde gespeichert.',
        'enabled_for' => 'Button auf dem Anmeldebildschirm anzeigen für',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'secret_kept' => 'Ein Secret ist gespeichert. Lassen Sie das Feld leer, um es beizubehalten.',
        'secret_none' => 'Noch kein Secret gespeichert.',
        'redirect' => 'Weiterleitungs-URLs',
        'redirect_hint' => 'Tragen Sie diese Adressen beim Anbieter als erlaubte Weiterleitungs-URLs (Callback) ein, genau in dieser Schreibweise.',
        'settings' => [
            'tenant' => 'Mandant (Tenant)',
            'tenant_hint' => 'Die Verzeichnis-ID (Tenant) oder Domain Ihrer Organisation. Leer: jedes Geschäfts-, Schul- oder Unikonto.',
            'host' => 'GitLab-Adresse',
            'host_hint' => 'Für ein selbst gehostetes GitLab, z. B. https://gitlab.example.com. Leer: gitlab.com.',
            'issuer' => 'Issuer-URL',
            'issuer_hint' => 'Die Adresse des OpenID-Connect-Servers; darunter muss /.well-known/openid-configuration erreichbar sein.',
            'label' => 'Button-Name',
            'label_hint' => 'Was auf dem Button zu sehen ist, z. B. der Name der Anmeldung Ihres Unternehmens.',
        ],
        'hints' => [
            'apple' => 'Client ID: die Services ID. Client Secret: das signierte JWT, das aus Ihrem Schlüssel erzeugt wird; Apple akzeptiert es höchstens sechs Monate lang, erneuern Sie es also rechtzeitig.',
            'telegram' => 'Client ID: der Benutzername des Bots. Client Secret: das Bot-Token. Die Domain der Website legen Sie für den Bot mit /setdomain in BotFather fest.',
            'entra' => 'Nur Geschäfts-, Schul- und Unikonten des unten eingetragenen Tenants können sich anmelden. Leer: jedes Geschäfts-, Schul- oder Unikonto.',
            'microsoft' => 'Private Microsoft-Konten sowie Geschäfts-, Schul- und Unikonten. Deren E-Mail-Adressen gelten nicht als bestätigt, daher muss ein Konto zuerst im Profil verknüpft werden.',
            'vkid' => 'Client ID: die App-ID. Client Secret: der geschützte Schlüssel. VK ID bürgt nicht für E-Mail-Adressen, daher muss ein Konto zuerst im Profil verknüpft werden.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik und jeder andere OpenID-Connect-Server.',
        ],
    ],

    'refused' => [
        'state' => 'Die Anmeldung mit :provider ist abgelaufen oder wurde an anderer Stelle gestartet. Bitte versuchen Sie es erneut.',
        'cancelled' => 'Die Anmeldung mit :provider wurde abgebrochen.',
        'failed' => ':provider hat die Anmeldung nicht bestätigt. Bitte versuchen Sie es erneut.',
        'no_email' => ':provider hat keine E-Mail-Adresse übermittelt, und mit ihr ist kein Konto verknüpft. Melden Sie sich anders an und verknüpfen Sie es in Ihrem Profil.',
        'unverified' => ':provider hat die E-Mail-Adresse nicht bestätigt, und mit ihr ist kein Konto verknüpft. Melden Sie sich anders an und verknüpfen Sie es in Ihrem Profil.',
        'inactive' => 'Dieses Konto ist deaktiviert.',
        'domain' => 'Adressen dieser Domain können sich hier nicht mit :provider anmelden.',
        'no_account' => 'Zu dieser :provider-Adresse gibt es kein Konto.',
        'taken' => 'Dieses :provider-Konto ist bereits mit einer anderen Person verknüpft.',
    ],

    'bounce' => [
        'title' => 'Anmeldung läuft…',
        'working' => 'Einen Moment, die Anmeldung wird abgeschlossen.',
        'continue' => 'Weiter',
    ],
];
