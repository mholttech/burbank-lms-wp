# Learner portal UI polish — contrast, mobile, headings

## Intent

Live QA on `cert.responseforge.app` (light/dark, desktop + ~390px) found readability and layout gaps in the learner portal and PressPrimer surfaces that the shell design (`2026-10-31-lms-learner-shell-design.md`) did not fully close:

- Dark/green text on `action-red` CTAs and certificate filter pills
- Mobile shell collapsing Catalog / My Courses / Profile into a “Training” `<details>` disclosure
- Certificate filter rows wrapping awkwardly; LMS content sitting under WP Accessibility and GTranslate floats
- Duplicate document H1s on learner pages; quiz title remaining H1 despite demotion

This pass is **plugin-only**: portal SCSS, PressPrimer skin, shell markup, and heading ownership. Theme header crowding and third-party plugin configuration are out of scope except for LMS padding that clears those widgets.

**Supersedes** the shell design’s “Training disclosure on mobile” decision: nav stays expanded at all breakpoints. Other shell decisions (who sees chrome, soft gate, register/verify outside shell) remain in force.

**Domain rules** in `docs/learner-access.md` and PressPrimer contracts in `docs/pressprimer-compatibility.md` stay authoritative. Quiz Submit hang / stuck loading spinner is a separate bug and is **out of scope**.

## Accepted decisions

| Topic | Decision |
| --- | --- |
| Scope | One pass: contrast + mobile layout + heading cleanup |
| Approach | Token-first CSS + targeted PHP (not remapping primary to green, not hard-coded one-offs) |
| Primary brand color | Keep `action-red` for quiz/cert CTAs; force light inverse text |
| Theme CTAs | Untouched (e.g. footer “Join CERT”); overrides scoped under LMS / PressPrimer roots |
| Floating widgets | LMS safe-area padding only; do not edit WP Accessibility or GTranslate |
| Mobile LMS menu | Always show pill-track nav; remove mobile `<details>` collapse |
| Headings | Portal owns H1 on profile / my-courses / catalog; demote quiz `.ppq-quiz-title` H1 → H2 |
| Quiz submit hang | Out of scope |

## Approaches

**Selected: Token + targeted CSS/PHP**

Add LMS CTA tokens; map PressPrimer primary button text to inverse; always-visible shell nav; portal padding for floats; extend heading suppression + harden quiz demotion.

**Rejected**

- Remap PPQ/PPCERT primary to callout-green — avoids contrast fight but breaks site action-red language.
- Hard-coded selector color patches only — fragile across dark mode and PressPrimer markup drift.

## Architecture

```
resources/frontend/scss/app.scss          CTA tokens, portal padding, shell always-expanded, filter wrap
resources/frontend/scss/pressprimer.scss  --ppq/--ppcert inverse text on primary; filter pill contrast
src/Learner/Shell.php                     Drop bfc-shell__mobile details markup
src/Learner/Portal_Headings.php           Suppress core/heading + core/post-title on learner pages
src/Integrations/PressPrimer_Quiz.php     Harden demote_quiz_title class matching
tests/php/*                               Shell / headings / demotion coverage
docs/learner-access.md                    Short note on nav + CTA + heading ownership
```

No REST, schema, or domain-rule changes.

## Contrast & CTAs

1. On `:root` and `html[data-bc-theme="dark"]`, define:
   - `--bfc-cta-bg: var(--wp--preset--color--action-red, …)`
   - `--bfc-cta-text: #fff` (solid; do not inherit content-link / content-text)
2. In `pressprimer.scss`, keep `--ppq-primary` / `--ppcert-primary` on action-red; set `--ppq-text-inverse` (and any primary-button text vars PressPrimer uses) to `--bfc-cta-text` / `#fff`.
3. Explicitly style primary quiz/cert buttons and active certificate filter pills under `.wp-site-blocks main` PressPrimer roots so inherited `a` / theme link colors cannot paint dark text on red.
4. Soft-gate / portal enroll-style greens remain on existing `--bfc-pill-*` tokens (unchanged).

## Mobile layout & nav

1. **Shell markup** (`Shell::render_chrome` and any `burbank-lms/shell` block mirror): render a single nav structure — pill-track list + contextual actions — with no `details.bfc-shell__mobile` duplicate.
2. **SCSS**: remove the `max-width: 781px` desktop-hide / mobile-show swap; keep wrapping flex so pills wrap cleanly on narrow widths; course actions may wrap onto a second row.
3. **Certificate filters**: `flex-wrap` + consistent gap; active state uses CTA inverse text.
4. **Safe area** on `.bfc-portal` (and quiz chrome if needed): modest `padding-left` / `padding-bottom` (and optional `env(safe-area-inset-*)`) so content clears the left a11y toolbar and bottom-right language float. Values are approximate; perfect avoidance of every third-party size is not required.
5. Slightly reduce horizontal padding on narrow viewports if it helps avoid a11y-bar collision without looking cramped.

## Headings

1. **`Portal_Headings`**: on `profile` and `my-courses` (and catalog archive when a competing title block appears), suppress:
   - `render_block_core/heading` level 1 (existing)
   - `render_block_core/post-title` (extend — live duplicate H1s likely come from post-title, not only heading blocks)
2. Course singles: keep portal display-name as `<p>`; theme/course title H1 for the course name remains acceptable document title.
3. **`PressPrimer_Quiz::demote_quiz_title`**: convert any `<h1>` whose class list includes `ppq-quiz-title` to `<h2>`, including alternate quoting / multi-class attributes that the current regex misses. Leave unrelated H1s alone.
4. Register / verify remain outside the shell; no change to soft-gate H1 ownership.

## Testing

| Area | Coverage |
| --- | --- |
| Shell | Assert chrome contains `bfc-shell__list` and does **not** contain `bfc-shell__mobile` or `<details` |
| Portal headings | Existing level-1 suppression + new `core/post-title` empty output on learner pages |
| Quiz demotion | Existing simple class case + at least one alternate attribute shape (e.g. extra classes / different quote style if applicable) |
| Manual | Light/dark + ~390px on my-courses, course, quiz after `npm run build` |

Update `docs/learner-access.md` with a short note: shell nav always expanded; CTA inverse text; heading ownership.

## Out of scope

- Theme header density (Duty Officer + theme toggle + hamburger)
- Configuring or removing WP Accessibility / GTranslate widgets
- Quiz Submit hang / `ppq-loading` without results UI
- Remapping brand primary from red to green
- Theme-wide red button / link contrast outside LMS and PressPrimer roots

## Success criteria

- Primary quiz/cert CTAs and active red filter pills are readable (light text on red) in light and dark.
- Catalog / My Courses / Profile pills remain visible without a disclosure on mobile.
- Learner profile / my-courses show a single portal H1; mapped quiz titles render as H2.
- LMS portal content is not obscured by the left a11y bar or bottom EN float under typical mobile widths.
)
