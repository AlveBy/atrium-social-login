<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Login social',
    'back_to_panel' => 'Voltar ao painel',

    'guards' => [
        'admin' => 'Painel administrativo',
        'account' => 'Conta pessoal',
    ],

    'login' => [
        'button' => 'Entrar com :provider',
    ],

    'menu' => [
        'title' => 'Login social',
        'accounts' => 'Contas vinculadas',
        'providers' => 'Provedores',
    ],

    'settings' => [
        'description' => 'Quais domínios de e-mail podem abrir uma conta existente com um endereço verificado pelo provedor. Os provedores são configurados em Login social → Provedores.',
        'domains' => 'Domínios de e-mail permitidos: :who',
        'domains_hint' => 'Um por linha ou separados por vírgula, por exemplo example.com. Vazio: qualquer domínio. Nenhuma conta é criada no painel administrativo dessa forma.',
    ],

    'accounts' => [
        'title' => 'Contas vinculadas',
        'hint' => 'Entre com uma conta de outro serviço em vez da senha. Uma segunda etapa de login, se você tiver uma, continua valendo.',
        'linked_title' => 'Vinculadas',
        'none' => 'Nenhuma conta vinculada ainda.',
        'available' => 'Vincular outra conta',
        'link' => 'Vincular :provider',
        'unlink' => 'Desvincular',
        'linked' => ':provider foi vinculado. Agora você pode entrar com ele.',
        'unlinked' => ':provider foi desvinculado.',
        'last_method' => 'Esta é a sua única forma de entrar. Defina uma senha ou vincule outra conta antes.',
        'last_used' => 'Último uso',
        'never' => 'nunca',
    ],

    'providers' => [
        'title' => 'Provedores de login social',
        'hint' => 'Registre um aplicativo no provedor, informe aqui o Client ID e o segredo e ative o botão onde as pessoas devem vê-lo.',
        'provider' => 'Provedor',
        'status' => 'Status',
        'on' => 'Ativado',
        'off' => 'Desativado',
        'not_set_up' => 'Não configurado',
        'edit' => 'Configurar',
        'save' => 'Salvar',
        'saved' => ':provider foi salvo.',
        'enabled_for' => 'Mostrar o botão na tela de login de',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Há um segredo salvo. Deixe o campo vazio para mantê-lo.',
        'secret_none' => 'Nenhum segredo salvo ainda.',
        'redirect' => 'URLs de redirecionamento',
        'redirect_hint' => 'Informe estes endereços no provedor como URLs de redirecionamento (callback) permitidas, exatamente como aparecem.',
        'settings' => [
            'tenant' => 'Locatário (tenant)',
            'tenant_hint' => 'O ID do diretório (tenant) ou o domínio da sua organização. Vazio: qualquer conta corporativa ou escolar.',
            'host' => 'Endereço do GitLab',
            'host_hint' => 'Para um GitLab hospedado por você, por exemplo https://gitlab.example.com. Vazio: gitlab.com.',
            'issuer' => 'URL do emissor (Issuer URL)',
            'issuer_hint' => 'O endereço do servidor OpenID Connect; /.well-known/openid-configuration deve abrir sob ele.',
            'label' => 'Nome do botão',
            'label_hint' => 'O que as pessoas veem no botão, por exemplo o nome do login da sua empresa.',
        ],
        'hints' => [
            'apple' => 'Client ID: o Services ID. Client secret: o JWT assinado gerado a partir da sua chave; a Apple aceita um por no máximo seis meses, então renove-o a tempo.',
            'telegram' => 'Client ID: o nome de usuário do bot. Client secret: o token do bot. Defina o domínio do site para o bot com /setdomain no BotFather.',
            'entra' => 'Somente contas corporativas e escolares do tenant informado abaixo podem entrar. Em branco: qualquer conta corporativa ou escolar.',
            'microsoft' => 'Contas Microsoft pessoais, corporativas e escolares. Os e-mails delas não são considerados verificados, então é preciso vincular a conta antes, pelo perfil.',
            'vkid' => 'Client ID: o ID do aplicativo. Client secret: a chave protegida. O VK ID não garante os endereços de e-mail, então é preciso vincular a conta antes, pelo perfil.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik e qualquer outro servidor OpenID Connect.',
        ],
    ],

    'refused' => [
        'state' => 'O login com :provider expirou ou foi iniciado em outro lugar. Tente novamente.',
        'cancelled' => 'O login com :provider foi cancelado.',
        'failed' => ':provider não confirmou o login. Tente novamente.',
        'no_email' => ':provider não compartilhou um endereço de e-mail e nenhuma conta está vinculada a ele. Entre de outra forma e vincule-o no seu perfil.',
        'unverified' => ':provider não confirmou o endereço de e-mail e nenhuma conta está vinculada a ele. Entre de outra forma e vincule-o no seu perfil.',
        'inactive' => 'Esta conta está desativada.',
        'domain' => 'Endereços deste domínio não podem entrar aqui com :provider.',
        'no_account' => 'Não há conta para este endereço do :provider.',
        'taken' => 'Esta conta do :provider já está vinculada a outra pessoa.',
    ],

    'bounce' => [
        'title' => 'Entrando…',
        'working' => 'Um momento, concluindo o login.',
        'continue' => 'Continuar',
    ],
];
