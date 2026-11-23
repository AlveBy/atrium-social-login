<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Inicio de sesión social',

    'guards' => [
        'admin' => 'Panel de administración',
        'account' => 'Cuenta personal',
    ],

    'login' => [
        'button' => 'Iniciar sesión con :provider',
    ],

    'menu' => [
        'title' => 'Inicio de sesión social',
        'accounts' => 'Cuentas vinculadas',
        'providers' => 'Proveedores',
    ],

    'settings' => [
        'description' => 'Qué dominios de correo pueden abrir una cuenta existente con una dirección que el proveedor ha verificado. Los proveedores se configuran en Inicio de sesión social → Proveedores.',
        'domains' => 'Dominios de correo permitidos: :who',
        'domains_hint' => 'Uno por línea o separados por comas, p. ej. example.com. Vacío: cualquier dominio. De esta forma nunca se crea nadie en el panel de administración.',
    ],

    'accounts' => [
        'title' => 'Cuentas vinculadas',
        'hint' => 'Inicie sesión con una cuenta de otro servicio en lugar de la contraseña. Si tiene un segundo paso de inicio de sesión, se sigue aplicando.',
        'linked_title' => 'Vinculadas',
        'none' => 'Aún no hay ninguna cuenta vinculada.',
        'available' => 'Vincular otra cuenta',
        'link' => 'Vincular :provider',
        'unlink' => 'Desvincular',
        'linked' => ':provider está vinculado. Ya puede iniciar sesión con él.',
        'unlinked' => ':provider se ha desvinculado.',
        'last_method' => 'Es su única forma de iniciar sesión. Primero establezca una contraseña o vincule otra cuenta.',
        'last_used' => 'Último uso',
        'never' => 'nunca',
    ],

    'providers' => [
        'title' => 'Proveedores de inicio de sesión social',
        'hint' => 'Registre una aplicación en el proveedor, introduzca aquí su Client ID y su secreto, y actívelo donde deba verse su botón.',
        'provider' => 'Proveedor',
        'status' => 'Estado',
        'on' => 'Activado',
        'off' => 'Desactivado',
        'not_set_up' => 'Sin configurar',
        'edit' => 'Configurar',
        'save' => 'Guardar',
        'saved' => ':provider se ha guardado.',
        'enabled_for' => 'Mostrar el botón en la pantalla de inicio de sesión de',
        'client_id' => 'Client ID',
        'client_secret' => 'Client secret',
        'secret_kept' => 'Hay un secreto guardado. Deje el campo vacío para conservarlo.',
        'secret_none' => 'Aún no hay ningún secreto guardado.',
        'redirect' => 'URL de redirección',
        'redirect_hint' => 'Introduzca estas direcciones en el proveedor como URL de redirección (callback) permitidas, exactamente como se muestran.',
        'settings' => [
            'tenant' => 'Inquilino (tenant)',
            'tenant_hint' => 'El ID del directorio (tenant) o el dominio de su organización. Vacío: cualquier cuenta de trabajo o centro educativo.',
            'host' => 'Dirección de GitLab',
            'host_hint' => 'Para un GitLab alojado por usted, p. ej. https://gitlab.example.com. Vacío: gitlab.com.',
            'issuer' => 'URL del emisor (Issuer URL)',
            'issuer_hint' => 'La dirección del servidor OpenID Connect; /.well-known/openid-configuration debe abrirse bajo ella.',
            'label' => 'Nombre del botón',
            'label_hint' => 'Lo que verá la gente en el botón, p. ej. el nombre del acceso de su empresa.',
        ],
        'hints' => [
            'apple' => 'Client ID: el Services ID. Client secret: el JWT firmado generado con su clave; Apple solo lo acepta durante seis meses como máximo, así que renuévelo a tiempo.',
            'telegram' => 'Client ID: el nombre de usuario del bot. Client secret: el token del bot. Establezca el dominio del sitio para el bot con /setdomain en BotFather.',
            'entra' => 'Solo pueden iniciar sesión las cuentas de trabajo y de centros educativos del tenant indicado abajo. Si se deja vacío: cualquier cuenta de trabajo o de centro educativo.',
            'microsoft' => 'Cuentas de Microsoft personales, de trabajo y de centros educativos. Sus direcciones de correo no se consideran verificadas, así que primero hay que vincular la cuenta desde el perfil.',
            'vkid' => 'Client ID: el ID de la aplicación. Client secret: la clave protegida. VK ID no garantiza las direcciones de correo, así que primero hay que vincular la cuenta desde el perfil.',
            'oidc' => 'Keycloak, Okta, Auth0, Authentik y cualquier otro servidor OpenID Connect.',
        ],
    ],

    'refused' => [
        'state' => 'El inicio de sesión con :provider ha caducado o se inició en otro lugar. Inténtelo de nuevo.',
        'cancelled' => 'Se canceló el inicio de sesión con :provider.',
        'failed' => ':provider no confirmó el inicio de sesión. Inténtelo de nuevo.',
        'no_email' => ':provider no compartió ninguna dirección de correo y no hay ninguna cuenta vinculada a ella. Inicie sesión de otra forma y vincúlela en su perfil.',
        'unverified' => ':provider no confirmó la dirección de correo y no hay ninguna cuenta vinculada a ella. Inicie sesión de otra forma y vincúlela en su perfil.',
        'inactive' => 'Esta cuenta está desactivada.',
        'domain' => 'Las direcciones de este dominio no pueden iniciar sesión aquí con :provider.',
        'no_account' => 'No hay ninguna cuenta para esta dirección de :provider.',
        'taken' => 'Esta cuenta de :provider ya está vinculada a otra persona.',
    ],

    'bounce' => [
        'title' => 'Iniciando sesión…',
        'working' => 'Un momento, se está terminando el inicio de sesión.',
        'continue' => 'Continuar',
    ],
];
