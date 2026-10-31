# LMS learner shell — cohesive training experience

## Intent

Learner pages currently feel like separate WordPress pages: invisible form/filter controls in light and dark, duplicate H1s (page slug + block title), and no shared training chrome across catalog, course, my-courses, and profile. Guests can still reach the course catalog archive and singles.

This design adds a **plugin-owned learner shell** and **shared LMS design system** so authenticated learners get one training experience under the existing CERT site header/footer, while register and verify stay public standalone flows.

**Related prior work:** `docs/superpowers/specs/2026-10-30-fse-learner-ui-integration-design.md` (block polish, PressPrimer skinning, thin theme shells). This spec **extends** that work with shell chrome, soft gates, and a unified control/card system. Domain rules in `docs/learner-access.md` stay authoritative for access and enrollment.

## Accepted decisions

| Topic | Decision |
| --- | --- |
| Goal | Fuller product shell: shared chrome + cohesive UI, not style-only patches |
| Subnav layout | Hybrid: horizontal LMS strip on desktop; “Training” disclosure on mobile |
| Who sees the shell | Authenticated learners with `Learner::can_learn` on catalog, course singles, my-courses, profile |
| Guests + catalog | Guests must not browse catalog/course content |
| Soft gate | In-place soft-gate card on catalog/course (and consistent pattern on my-courses/profile when blocked); guests vs logged-in-no-access get **different copy/CTAs**; soft-gate views do **not** render the LMS subnav |
| Register / verify | Public; **outside** the LMS shell |
| Site chrome | Keep full CERT header/footer; LMS subnav sits under the header |
| Subnav items | Catalog · My courses · Profile; on course singles add **contextual actions** (Enroll / Continue / Withdraw as appropriate) |
| Ownership | Entirely in the plugin (Approach A) |

## Approaches

**Selected: Shell block + shared LMS design system**

Add learner shell chrome (nav + framed content), centralize form/card/button tokens in frontend SCSS, soft-gate catalog/course when `! can_learn`.

**Rejected**

- Style-only pass — does not meet guest-catalog or subnav goals.
- Template-only chrome duplicated per page — drifts; weak contextual course actions.

## Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  Theme: full header / footer (unchanged)                     │
└────────────────────────────┬─────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────┐
│  Plugin Learner\Shell + shell chrome                         │
│  Hybrid subnav · soft gate · content frame                   │
└───────┬───────────────────────────────┬──────────────────────┘
        │                               │
┌───────▼──────────────┐   ┌────────────▼──────────────────────┐
│ Blocks + templates   │   │ Shared SCSS design system         │
│ catalog/course/…     │   │ surfaces, inputs, buttons, cards  │
└───────┬──────────────┘   └───────────────────────────────────┘
        │
┌───────▼──────────────┐
│ Domain (unchanged)   │
│ Access / Enrollment  │
│ Learner::can_learn   │
└──────────────────────┘
```

### New / updated pieces

1. **`Learner\Shell`** — when to show chrome; nav item builder (routes + active state + contextual course actions); soft-gate state machine (guest vs logged-in-no-access vs can_learn).
2. **`burbank-lms/shell` block** — hybrid subnav + InnerBlocks/content slot; used from plugin FSE templates and learner pages. If a page cannot host the block, `Learner\Shell` may print the same chrome around registered learner block output without a second visual system.
3. **`resources/frontend/scss/`** — shared LMS tokens and control styles; block SCSS consumes them.
4. **`Learner\Templates`** — archive/single markup includes shell; single H1 ownership (no duplicate slug + block titles).
5. **Catalog/course (and blocked my-courses/profile) presentation** — content only when `can_learn`; otherwise soft gate with no course list leakage.

Register and verify keep their pages/blocks; they reuse control tokens but do not render the shell.

## Components / UI

### Design tokens

Shared light/dark-friendly tokens (prefer `--wp--preset--color-*`, then CERT `--bc-*` / `html[data-bc-theme]` when present, with solid fallbacks):

- Surfaces: content, card, muted panel
- Controls: input/select with **visible border + fill**, focus ring, disabled state
- Actions: primary, secondary, danger/withdraw
- Spacing/radius aligned with existing catalog card language (`border-radius: 6px`, clamped padding)

### Shell / subnav

- **Desktop:** horizontal strip under site header — Catalog · My courses · Profile; clear active state; optional right-side contextual actions on course singles.
- **Mobile:** “Training” disclosure expanding the same links + contextual actions.
- **Content frame:** consistent max-width and vertical rhythm for catalog cards, course detail, profile form, my-courses list.

### Per-surface polish

| Surface | Behavior |
| --- | --- |
| Catalog | Keep filters + cards; bordered filter controls; primary Enroll |
| Course / enroll | Same card/button language; contextual actions in shell when applicable |
| Profile | Shared field styles; one page H1 (demote block title if needed) |
| My courses | Shared cards/empty states; catalog CTA uses primary button |
| Register | Outside shell; **same** field/button tokens |
| Verify | Outside shell; leave PressPrimer verify UI; ensure credential field remains usable in both themes |
| Soft gate | Centered card: title, short copy, CTAs (see Access) |

### Titles

One visible H1 per learner page. Template owns the primary title where it already does (e.g. “Course catalog”); block-level headings demote to H2 or become visually secondary so slug pages (`profile`, `my-courses`, `register`) do not double-title.

## Access gates

**Gate key:** `Learner::can_learn( $user_id )` (training capability and not suspended). Existing admin capability grants remain.

| Audience | Catalog / course singles | My courses / Profile |
| --- | --- | --- |
| `can_learn` | Shell + full content | Shell + content |
| Guest | Soft gate: invite-only training; **Log in** + **Register** (`/register/`); **no** course list/filters/enroll | Soft gate or existing login requirement; same guest messaging pattern if HTML is shown |
| Logged in, cannot learn | Soft gate: distinct copy; contact training administrator (not Register-primary); **no** course list | Same logged-in-no-access soft-gate pattern |

**Register / verify:** public; no shell; no catalog soft gate. Register may still show “already signed in.”

**REST / enroll:** unchanged — learner mutations still require `can_learn`. Soft gate is HTML presentation only.

## Testing

### PHPUnit

- Shell nav builder: active route; contextual course actions on course context; items for can_learn users.
- Soft gate: guest and logged-in-no-access receive gate markup without catalog items; can_learn receives content.
- New shell block included in block registration guards if added as a built block.
- Existing enroll/access tests remain the source of truth for API gates.

### Manual / browser

- Light + dark: catalog, course, my-courses, profile (with shell); register + verify (no shell).
- Guest `/courses/` and course URL → soft gate, no course list.
- Logged-in-no-access → distinct soft-gate copy.
- Mobile training disclosure; desktop subnav.
- Profile/register/catalog filter controls visibly bordered in both themes.

## Out of scope

- Replacing CERT marketing header/footer with an app-only frame.
- Putting verify or register inside the LMS subnav.
- Changing invite/enrollment domain rules or PressPrimer attempt APIs.
- Auto-creating WordPress pages (still Site Editor / deploy).

## Success criteria

1. Authenticated learners with access experience one training chrome across catalog, course, my-courses, and profile.
2. Guests and no-access users never see the course catalog list or enroll CTAs on archive/singles.
3. Form and filter controls are clearly visible in light and dark.
4. Register and verify remain usable public pages without LMS subnav.
5. Implementation ships in `burbank-lms` without requiring theme changes for the shell to work (theme may still wrap with header/footer as today).
