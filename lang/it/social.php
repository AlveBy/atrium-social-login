<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Accesso con i social',

    'guards' => [
        'admin' => 'Pannello di amministrazione',
        'account' => 'Area personale',
    ],

    'login' => [
        'button' => 'Accedi con :provider',
    ],

    'menu' => [
        'title' => 'Accesso con i social',
        'accounts' => 'Account collegati',
        'providers' => 'Provider',
    ],

    'settings' => [
        'description' => 'Quali domini e-mail possono aprire un account esistente con un indirizzo verificato dal provider. I provider si configurano in Accesso con i social → Provider.',
        'domains' => 'Domini e-mail consentiti: :who',
        'domains_hint' => 'Uno per riga o separati da virgole, ad esempio example.com. Vuoto: qualsiasi dominio. In questo modo nel pannello di amministrazione non viene mai creato nessun account.',
    ],

    'accounts' => [
        'title' => 'Account collegati',
        'hint' => 'Accedi con un account di un altro servizio al posto della password. Un secondo passaggio di accesso, se ne hai uno, resta comunque richiesto.',
        'linked_title' => 'Collegati',
        'none' => 'Nessun account collegato per ora.',
        'available' => 'Collega un altro account',
        'link' => 'Collega :provider',
        'unlink' => 'Scollega',
        'linked' => ':provider è collegato. Ora puoi accedere con esso.',
        'unlinked' => ':provider è stato scollegato.',
        'last_method' => 'Questo è il tuo unico modo per accedere. Imposta prima una password o collega un altro account.',
        'last_used' => 'Ultimo utilizzo',
        'never' => 'mai',
    ],

    'providers' => [
        'title' => 'Provider di accesso con i social',
        'hint' => 'Registra un’applicazione presso il provider, inserisci qui il Client ID e il secret e attiva il pulsante dove deve essere visibile.',
        'provider' => 'Provider',
        'status' => 'Stato',
        'on' => 'Attivo',
        'off' => 'Disattivato',
        'not_set_up' => 'Non configurato',
        'edit' => 'Configura',
        'save' => 'Salva',
        'saved' => ':provider è stato salvato.',
        'enabled_for' => 'Mostra il pulsante nella schermata di accesso di',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Un secret è già salvato. Lascia il campo vuoto per mantenerlo.',
        'secret_none' => 'Nessun secret salvato per ora.',
        'redirect' => 'URL di reindirizzamento',
        'redirect_hint' => 'Inserisci questi indirizzi presso il provider come URL di reindirizzamento (callback) consentiti, esattamente come mostrati.',
        'settings' => [
            'tenant' => 'Tenant',
            'tenant_hint' => 'L’ID della directory (tenant) o il dominio della tua organizzazione. Vuoto: qualsiasi account di lavoro o scuola.',
            'host' => 'Indirizzo di GitLab',
            'host_hint' => 'Per un GitLab self-hosted, ad esempio https://gitlab.example.com. Vuoto: gitlab.com.',
            'issuer' => 'URL dell’emittente (Issuer URL)',
            'issuer_hint' => 'L’indirizzo del server OpenID Connect; sotto di esso deve aprirsi /.well-known/openid-configuration.',
            'label' => 'Nome del pulsante',
            'label_hint' => 'Ciò che le persone vedono sul pulsante, ad esempio il nome dell’accesso della tua azienda.',
        ],
        'hints' => [
            'apple' => 'Client ID: il Services ID. Client secret: il JWT firmato creato dalla tua chiave; Apple ne accetta uno per al massimo sei mesi, quindi rinnovalo in tempo.',
            'telegram' => 'Client ID: il nome utente del bot. Client secret: il token del bot. Imposta il dominio del sito per il bot con /setdomain in BotFather.',
            'entra' => 'Possono accedere solo gli account di lavoro e scuola del tenant inserito qui sotto. Se vuoto: qualsiasi account di lavoro o scuola.',
            'microsoft' => 'Account Microsoft personali, di lavoro e scuola. I loro indirizzi e-mail non sono considerati verificati, quindi un account va prima collegato dal profilo.',
            'vkid' => 'Client ID: l’ID dell’app. Client secret: la chiave protetta. VK ID non garantisce gli indirizzi e-mail, quindi un account va prima collegato dal profilo.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik e qualsiasi altro server OpenID Connect.',
        ],
    ],

    'refused' => [
        'state' => 'L’accesso con :provider è scaduto o è stato avviato altrove. Riprova.',
        'cancelled' => 'L’accesso con :provider è stato annullato.',
        'failed' => ':provider non ha confermato l’accesso. Riprova.',
        'no_email' => ':provider non ha condiviso un indirizzo e-mail e nessun account è collegato a esso. Accedi in un altro modo e collegalo nel tuo profilo.',
        'unverified' => ':provider non ha confermato l’indirizzo e-mail e nessun account è collegato a esso. Accedi in un altro modo e collegalo nel tuo profilo.',
        'inactive' => 'Questo account è disattivato.',
        'domain' => 'Gli indirizzi di questo dominio non possono accedere qui con :provider.',
        'no_account' => 'Non esiste alcun account per questo indirizzo :provider.',
        'taken' => 'Questo account :provider è già collegato a un’altra persona.',
    ],

    'bounce' => [
        'title' => 'Accesso in corso…',
        'working' => 'Un attimo, sto completando l’accesso.',
        'continue' => 'Continua',
    ],
];
