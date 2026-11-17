<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Connexion via les réseaux sociaux',
    'back_to_panel' => 'Retour au panneau',

    'guards' => [
        'admin' => 'Panneau d’administration',
        'account' => 'Espace personnel',
    ],

    'login' => [
        'button' => 'Se connecter avec :provider',
    ],

    'menu' => [
        'title' => 'Connexion via les réseaux sociaux',
        'accounts' => 'Comptes liés',
        'providers' => 'Fournisseurs',
    ],

    'settings' => [
        'description' => 'Les domaines dont les adresses e-mail peuvent ouvrir un compte existant lorsqu’un fournisseur a vérifié l’adresse. Les fournisseurs se configurent sous Connexion via les réseaux sociaux → Fournisseurs.',
        'domains' => 'Domaines e-mail autorisés : :who',
        'domains_hint' => 'Un par ligne ou séparés par des virgules, par exemple example.com. Vide : tous les domaines. Aucun compte n’est jamais créé de cette façon dans le panneau d’administration.',
    ],

    'accounts' => [
        'title' => 'Comptes liés',
        'hint' => 'Connectez-vous avec un compte d’un autre service à la place du mot de passe. Une seconde étape de connexion, si vous en avez une, reste exigée.',
        'linked_title' => 'Liés',
        'none' => 'Aucun compte n’est encore lié.',
        'available' => 'Lier un autre compte',
        'link' => 'Lier :provider',
        'unlink' => 'Dissocier',
        'linked' => ':provider est lié. Vous pouvez désormais vous connecter avec.',
        'unlinked' => ':provider a été dissocié.',
        'last_method' => 'C’est votre seul moyen de connexion. Définissez d’abord un mot de passe ou liez un autre compte.',
        'last_used' => 'Dernière utilisation',
        'never' => 'jamais',
    ],

    'providers' => [
        'title' => 'Fournisseurs de connexion sociale',
        'hint' => 'Enregistrez une application chez le fournisseur, saisissez ici son Client ID et son secret, puis activez-le là où son bouton doit apparaître.',
        'provider' => 'Fournisseur',
        'status' => 'Statut',
        'on' => 'Activé',
        'off' => 'Désactivé',
        'not_set_up' => 'Non configuré',
        'edit' => 'Configurer',
        'save' => 'Enregistrer',
        'saved' => ':provider a été enregistré.',
        'enabled_for' => 'Afficher le bouton sur l’écran de connexion de',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Un secret est enregistré. Laissez le champ vide pour le conserver.',
        'secret_none' => 'Aucun secret n’est encore enregistré.',
        'redirect' => 'URL de redirection',
        'redirect_hint' => 'Saisissez ces adresses chez le fournisseur comme URL de redirection (callback) autorisées, exactement comme indiqué.',
        'settings' => [
            'tenant' => 'Locataire (tenant)',
            'tenant_hint' => 'L’ID du répertoire (tenant) ou le domaine de votre organisation. Vide : tout compte professionnel ou scolaire.',
            'host' => 'Adresse de GitLab',
            'host_hint' => 'Pour un GitLab auto-hébergé, par exemple https://gitlab.example.com. Vide : gitlab.com.',
            'issuer' => 'URL de l’émetteur (Issuer URL)',
            'issuer_hint' => 'L’adresse du serveur OpenID Connect ; /.well-known/openid-configuration doit s’ouvrir sous cette adresse.',
            'label' => 'Nom du bouton',
            'label_hint' => 'Ce que les utilisateurs voient sur le bouton, par exemple le nom de la connexion de votre entreprise.',
        ],
        'hints' => [
            'apple' => 'Client ID : le Services ID. Client secret : le JWT signé créé à partir de votre clé ; Apple l’accepte six mois au maximum, pensez donc à le renouveler à temps.',
            'telegram' => 'Client ID : le nom d’utilisateur du bot. Client secret : le jeton du bot. Définissez le domaine du site pour le bot avec /setdomain dans BotFather.',
            'entra' => 'Seuls les comptes professionnels et scolaires du tenant indiqué ci-dessous peuvent se connecter. Laissé vide : n’importe quel compte professionnel ou scolaire.',
            'microsoft' => 'Comptes Microsoft personnels, professionnels et scolaires. Leurs adresses e-mail ne sont pas considérées comme vérifiées : un compte doit donc d’abord être lié depuis le profil.',
            'vkid' => 'Client ID : l’ID de l’application. Client secret : la clé protégée. VK ID ne garantit pas les adresses e-mail : un compte doit donc d’abord être lié depuis le profil.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik et tout autre serveur OpenID Connect.',
        ],
    ],

    'refused' => [
        'state' => 'La connexion avec :provider a expiré ou a été lancée ailleurs. Veuillez réessayer.',
        'cancelled' => 'La connexion avec :provider a été annulée.',
        'failed' => ':provider n’a pas confirmé la connexion. Veuillez réessayer.',
        'no_email' => ':provider n’a pas transmis d’adresse e-mail, et aucun compte n’y est lié. Connectez-vous autrement et liez-la dans votre profil.',
        'unverified' => ':provider n’a pas confirmé l’adresse e-mail, et aucun compte n’y est lié. Connectez-vous autrement et liez-la dans votre profil.',
        'inactive' => 'Ce compte est désactivé.',
        'domain' => 'Les adresses de ce domaine ne peuvent pas se connecter ici avec :provider.',
        'no_account' => 'Il n’existe aucun compte pour cette adresse :provider.',
        'taken' => 'Ce compte :provider est déjà lié à quelqu’un d’autre.',
    ],

    'bounce' => [
        'title' => 'Connexion en cours…',
        'working' => 'Un instant, la connexion se termine.',
        'continue' => 'Continuer',
    ],
];
