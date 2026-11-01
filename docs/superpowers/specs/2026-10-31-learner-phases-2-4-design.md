# Learner UI Phases 2–4 (mega-spec) — course journey, getting in, back office

## Intent

Phase 1 redesigned My Courses, Catalog, Profile, and certificate-name self-service on `fix/learner-light-mode-pill-contrast` ([PR 34](https://github.com/mholttech/burbank-lms-wp/pull/34)). This spec designs **Phases 2–4** so the **same PR** ships the full program: taking a course, getting in (including email verification), and React back office + branded mail. No separate PRs for later phases.

Domain rules in `docs/learner-access.md` remain authoritative except where this spec adds email verification and React admin surfaces. No progress percentages anywhere (same as Phase 1). Design language continues Phase 1 tiles / section heads / `--bfc-*` tokens for learner surfaces; admin uses wp-admin chrome with a shared React kit; emails use one branded HTML wrapper + text fallback.

HTML mocks in design sessions must use **theme CSS variables** (T3 `html_render` tokens) so light/dark thread appearance stays readable — do not hard-code light-only greys.

**Mock coverage (approved compositions + gap states):** course stack (open / invite / enrolled / completed / withdrawn / unverified); public cert verify (valid / invalid / expired); enroll mini-card feet; register + success; guest + no-access soft-gates; verify-link landing success/error; React Learners admin; Course sidebar Invites kit; branded verify email + text fallback note. Site-invites table was mocked in the Journey 3 overview.

## Accepted decisions

| Topic | Decision |
| --- | --- |
| Delivery | Journey-first mega-spec; implement Phases 2→3→4 into **PR 34** after this plan. |
| Progress % | **None** — status labels and counts only. |
| Phase 2 course single | **Stack (A):** badge → title → meta → lede → materials (post content) → quiz region → foot (status + Withdraw/Continue or Enroll). No split side-rail. Matches Phase 1 tile language. |
| Phase 2 quiz | Keep mapped PressPrimer player; LMS chrome (back to course + enrollment status pill); skin via existing PressPrimer assets / tokens; H1→H2 demotion unchanged. Gate: `can_learn` + active enrollment + **email verified**. |
| Phase 2 certificates | Issued list stays PressPrimer-shaped with Phase 1 grid polish; public verify stays **outside** the shell — code field + valid / invalid / expired in portal panel language. |
| Phase 2 enroll block | Standalone `burbank-lms/enroll` becomes a state-aware mini-card aligned with catalog foot states: Open→Enroll, Invite→chip+View, Enrolled→Continue, Unverified→verify CTA only. |
| Phase 3 email verify | **Access at register** (`can_learn` granted on signup). Verify is an **extra learning gate**. Signed-in unverified users **see Shell**; catalog / profile / my-courses / course **bodies** are replaced by a verify soft-gate (resend, change email). Enroll + quiz blocked until verified. |
| Phase 3 other states | Leave no gaps: register success (check email / sign in), guest soft-gate (no shell), no-access soft-gate (`!can_learn`, no shell), public verify-link landing (success/error → continue), shell actions that enroll/quiz hide or route to verify while unverified. |
| Phase 4 admin | **Everything React:** Training → Site invites page; Training → Learners / Training Access (grant, revoke, suspend, reactivate, audited legal-name correction); restyle/extend Course editor sidebar panels to the same admin kit. Retire PHP site-invite screens and PHP Training Access profile UI. |
| Phase 4 emails | One branded HTML template + text fallback wrapping welcome, enroll, completion, **verify**, **resend**. Per-message body copy; shared layout/brand. |

## Approaches

**Selected: Journey-first (A)** — one spec in three journeys; HTML mock critical states; implement in journey order on the Phase 1 branch.

**Rejected:** Surface inventory (B) — misses cross-state consistency. Shared kit first (C) — delays real screens.

## Architecture

### Journey 1 — Taking a course

```
src/Blocks/course/          Stack tile: badge, title, meta, materials, #bfc-course-quiz, foot
src/Blocks/enroll/          State-aware mini-card (mirror catalog foot)
src/Integrations/           Quiz chrome + skin; cert merge unchanged; verify page skin
resources/frontend/scss/    Course / enroll / quiz / verify tokens (no %)
```

**Data / gates:** Existing enrollment REST + PressPrimer IDs. New: `Learner::email_verified()` (or equivalent usermeta / WP capability flag) consulted by Soft_Gate content swap, enroll REST, quiz `can_start` / `before_submit`.

**Error handling:** Enroll/quiz while unverified → 403 with verify-oriented code; UI shows verify CTA. Public cert verify: invalid/expired copy without leaking PII.

### Journey 2 — Getting in

```
src/Access/                 Registration sends verify mail; mark verified on token consume
src/Learner/Soft_Gate.php   New audience: verified vs unverified (shell still shown when can_learn)
src/Learner/Shell.php       Hide/redirect enroll & quiz contextual actions when unverified
src/Blocks/register/        Success copy: check email / sign in
src/REST/Controller.php     Resend verify; optional change-email (re-verify)
src/Mail/                   Verify + resend via shared template
```

**Flow:** Invite register → grant access + set unverified → welcome + verify emails → login allowed → shell + verify panel until link clicked → learning unlocked.

**Testing:** Soft_Gate / Shell / Registration / REST / PressPrimer gate tests for verified vs unverified; guest and no-access unchanged.

### Journey 3 — Back office

```
resources/admin/            React apps: site invites + training access (wp-admin pages)
src/Access/Admin*.php       Thin PHP menus enqueue React; remove old PHP form UIs
src/Courses/Editor + JS     Sidebar panels share admin kit styles/components
src/REST/Admin_Controller   Extend as needed for invites / learner access APIs
src/Mail/                   Branded HTML wrapper + text multipart
```

**Auth:** Existing `enroll_bfc_courses` (and edit-course caps for sidebar). No learner REST for admin actions.

## Components (by journey)

### Course stack (J1)

- Status badge (open / invite / enrolled / active / passed / completed / withdrawn) — same vocabulary as catalog/my-courses.
- Meta row: BFC code · subject · outcome (no %).
- Materials: theme post content above or slotted as today; quiz region `#bfc-course-quiz` only when enrolled + verified + quiz mapped.
- Foot: left status text; right primary CTA + quiet withdraw when applicable.

### Quiz chrome (J1)

- Bar: “← Back to course” + status pill.
- Player: PressPrimer; skin CSS only — do not fork quiz UX.
- Gate includes email verified.

### Certificate (J1)

- Learner list: Phase 1 my-courses certificates section.
- Public verify: no shell; panel with code input + result states.

### Enroll mini-card (J1)

- One foot row mirroring catalog; unverified shows verify CTA only.

### Verify soft-gate (J2)

- Training kicker + H1 “Verify your email” + email hint + Resend + Change email.
- Rendered inside learner blocks when `can_learn && !email_verified`.
- Shell remains visible.

### Soft gates (J2)

- Guest / no-access: keep Phase 1 panels (no shell); polish copy/spacing only as needed for “no gaps.”

### React Training admin (J3)

- Submenu under Training: Site invites | Training access (Learners).
- Site invites: list, create multi/once, revoke, show plaintext once at create.
- Learners: search, grant/revoke access, suspend/reactivate, admin legal-name correction (audited).
- Course sidebar: Details unchanged functionally; Approval / Invites / Enrollments visual kit alignment.

### Mail (J3)

- Shared header/brand/footer HTML; plain-text alternative; message body slots for each type.

## Data flow

1. **Register** → user + first/last legal name + `can_learn` + unverified flag → welcome + verify mail.
2. **Verify link** → mark verified → redirect into portal.
3. **Learner surfaces** → Soft_Gate: guest / no-access / unverified-verify-panel / pass-through.
4. **Enroll / quiz** → require verified; otherwise error + UI CTA.
5. **Admin React** → REST → existing Access/Enrollment services.
6. **Mailer** → wrap all transactional sends in branded template.

## Error handling

| Case | Behavior |
| --- | --- |
| Unverified enroll/quiz | 403 + verify-oriented code; UI verify panel / CTA |
| Bad/expired verify token | Public landing with retry / resend |
| Resend rate limit | Soft error on verify panel |
| Admin unauthorized | Existing REST 403 |
| Mail send failure | Log; do not block registration success path (compensating behavior documented) |

## Testing

- PHPUnit: registration verify flag, Soft_Gate audiences, Shell action hiding, REST resend/verify, PressPrimer gate with unverified, Admin REST for React pages, Mailer template multipart.
- JS lint on new admin React + block view scripts.
- Manual (post-deploy): course stack, quiz chrome, verify panel with shell, React admin pages, sample emails in light/dark client.

## Out of scope

- Progress percentages; course tile images; renaming already-issued certificates; account deletion / privacy export / application passwords; forking PressPrimer quiz/certificate internals beyond skin + chrome; separate release PRs for Phases 2–4.

## Roadmap update

Phase 1 spec (`2026-10-31-learner-course-pages-redesign-design.md`) remains the Phase 1 design record. This document supersedes that file’s “Phases 2–4 later” note: **Phases 2–4 are designed here and ship in PR 34** with Phase 1.
