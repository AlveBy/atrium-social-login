{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    One stylesheet for the panel and the site. Every colour is a variable of
    the panel's themes (--atrium-*), then of the site theme (--bg, --text…),
    then a fallback, so the pages read well in all of them. Measured pairs
    (WCAG): see docs/social-login.md, "Contrast".
    Nothing is told by colour alone: errors have a bar, a mark and words;
    the chosen factor is bold and has aria-current.
--}}
<style @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif>
.asl {
    --asl-bg: var(--atrium-page, var(--surface, #f3f4f6));
    --asl-card: var(--atrium-surface, var(--bg, #ffffff));
    --asl-fg: var(--atrium-fg, var(--text, #16181d));
    --asl-muted: var(--atrium-muted, var(--muted, #4f5662));
    --asl-line: var(--atrium-line, var(--line, #e2e5ea));
    --asl-field: var(--atrium-field, var(--control, #6b7280));
    --asl-accent: var(--atrium-accent, var(--accent, #1d4ed8));
    --asl-on-accent: var(--atrium-on-accent, var(--bg, #ffffff));
    --asl-focus: var(--atrium-focus, var(--focus, #1d4ed8));
    --asl-hover: var(--atrium-hover, var(--surface, #f3f4f6));
    --asl-danger: var(--atrium-danger, var(--text, #16181d));
    --asl-ok: var(--atrium-success, var(--text, #16181d));
    color: var(--asl-fg);
    font: inherit;
    line-height: 1.5;
}
.asl *, .asl *::before, .asl *::after { box-sizing: border-box; }
.asl-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; background: var(--asl-bg); }
.asl-site { display: flex; justify-content: center; padding: 2rem 1rem; }
.asl-card { width: 100%; max-width: 28rem; background: var(--asl-card); border: 1px solid var(--asl-line); border-radius: .5rem; padding: 2rem; }
.asl h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 .5rem; }
.asl h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 .5rem; }
.asl p { margin: 0 0 1rem; }
.asl-muted { color: var(--asl-muted); }
.asl-small { font-size: .875rem; }
.asl-section { border-top: 1px solid var(--asl-line); padding-top: 1.5rem; margin-top: 1.5rem; }
.asl label { display: block; font-weight: 600; font-size: .875rem; margin-bottom: .25rem; }
.asl input[type=text], .asl input[type=email], .asl input[type=tel], .asl input[type=search], .asl select {
    display: block; width: 100%; padding: .5rem .75rem; font: inherit; color: var(--asl-fg);
    background: var(--asl-card); border: 1px solid var(--asl-field); border-radius: .375rem;
}
.asl input.asl-code { font-size: 1.5rem; letter-spacing: .3em; text-align: center; font-variant-numeric: tabular-nums; }
.asl input:focus-visible, .asl select:focus-visible, .asl button:focus-visible, .asl a:focus-visible {
    outline: 3px solid var(--asl-focus); outline-offset: 2px;
}
.asl-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .5rem 1rem;
    font: inherit; font-weight: 600; font-size: .875rem; border-radius: .375rem; cursor: pointer; text-decoration: none;
    border: 1px solid var(--asl-field); background: var(--asl-card); color: var(--asl-fg);
}
.asl-btn:hover { background: var(--asl-hover); }
.asl-btn[disabled] { cursor: not-allowed; opacity: .7; }
.asl-primary { background: var(--asl-accent); border-color: var(--asl-accent); color: var(--asl-on-accent); }
.asl-primary:hover { background: var(--atrium-accent-hover, var(--asl-accent)); text-decoration: underline; }
.asl-block { width: 100%; }
.asl-link { background: none; border: 0; padding: 0; font: inherit; color: var(--asl-fg); text-decoration: underline; cursor: pointer; }
.asl-alert { border: 1px solid var(--asl-line); border-left-width: 4px; border-radius: .375rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; }
.asl-alert::before { font-weight: 700; margin-right: .25rem; }
.asl-error { border-left-color: var(--asl-danger); color: var(--asl-danger); }
.asl-error::before { content: "!"; }
.asl-ok { border-left-color: var(--asl-ok); }
.asl-ok::before { content: "✓"; }
.asl-field-error { color: var(--asl-danger); font-size: .875rem; margin-top: .25rem; }
.asl-field-error::before { content: "! "; font-weight: 700; }
.asl-list { list-style: none; padding: 0; margin: 0; }
.asl-list > li { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .75rem 0; border-bottom: 1px solid var(--asl-line); }
.asl-codes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .25rem 1rem; font-family: ui-monospace, monospace; font-size: 1rem; padding: 1rem; border: 1px dashed var(--asl-field); border-radius: .375rem; }
.asl-qr { display: inline-block; padding: .5rem; background: #ffffff; border-radius: .375rem; line-height: 0; }
.asl-tabs { display: flex; flex-wrap: wrap; gap: .5rem; }
.asl-tabs [aria-current] { font-weight: 700; border-width: 2px; border-color: var(--asl-fg); }
.asl-divider { display: flex; align-items: center; gap: .75rem; color: var(--asl-muted); font-size: .875rem; margin: 1.5rem 0 1rem; }
.asl-divider::before, .asl-divider::after { content: ""; flex: 1; border-top: 1px solid var(--asl-line); }
.asl-methods { list-style: none; padding: 0; margin: 0; display: grid; gap: .5rem; }
.asl-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.asl-pager { display: flex; gap: 1rem; margin-top: 1rem; }
</style>
