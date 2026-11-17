# Atrium social sign-in

Sign-in to the [Atrium](https://github.com/AlveBy/atrium) admin panel and personal account with Google, Microsoft, Microsoft Entra ID, Yandex ID, Apple, GitHub, GitLab, Facebook, VK ID, Mail.ru, Telegram, LinkedIn or any OpenID Connect server (Keycloak, Authentik, Okta, Auth0…). Built on Laravel Socialite and SocialiteProviders.

- Each provider is switched on in the panel, with its client ID and an encrypted secret, for the panel, the account or both.
- An existing person is found only by an address the provider has verified; Microsoft, VK ID and Telegram accounts are linked from the profile first.
- The panel never registers anybody through a social account, and can be limited to e-mail domains.
- Link and unlink in the profile; the last way in cannot be unlinked.
- With `alveby/atrium-2fa`, the second step still applies and every sign-in is in the log.
- Pages in 15 languages; buttons say the provider's name in words.

## Install

```bash
composer require alveby/atrium-social-login
php artisan atrium:module:install alveby/atrium-social-login
```

Then **Social sign-in → Providers** in the panel. How to register an app at each provider and which redirect URLs to give: [docs/social-login.md](https://github.com/AlveBy/atrium/blob/main/docs/social-login.md) ([по-русски](https://github.com/AlveBy/atrium/blob/main/docs/social-login.ru.md)).

## License

MIT. This package is split from the [Atrium monorepo](https://github.com/AlveBy/atrium); issues and pull requests go there.
