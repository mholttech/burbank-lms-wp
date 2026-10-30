# Burbank × PressPrimer × Pods — deeper integration playbook

Generated from exhaustive hook/API inventories (Pods 443 hooks / 271 funcs; Quiz 126 hooks / 54 REST; Certificate 69 hooks / 19 REST). Phase 1 already uses a thin slice. Everything below is the next layer, ordered by leverage.

## Tier 0 — already wired (do not re-implement)

| Surface | What we use |
| --- | --- |
| Certificate adapter | `ppcert_adapter_classes` |
| Template sync | `ppcert_template_created` / `_updated` / `_trashed` → course trigger fields |
| Competing triggers | `find_active` skips trashed / missing templates |
| Course REST | `register_post_meta` allowlist for `bfc_*` |
| Pods validation | `pods_api_field_validation` / post-save item hooks |

## Tier 1 — highest leverage (Pri 10)

### Quiz access & outcomes

| Hook / API | Burbank action |
| --- | --- |
| `pressprimer_quiz_can_start` | Gate on course approval + publication + enrollment identity |
| `pressprimer_quiz_before_submit` | Re-check gate at submit; block stale drafts |
| `pressprimer_quiz_quiz_passed` / `_failed` / `_completed` | Write progress onto course/user meta; feed Certificate issue |
| `pressprimer_quiz_attempt_started` / `_submitted` | Audit trail / revision-safe attempt snapshots |
| `/ppq/v1/my-attempts` | Read-side learner history; start/submit stay on AJAX hooks |

### Certificate issue & merge

| Hook / API | Burbank action |
| --- | --- |
| `ppcert_before_issue` | Require linked published course + approved revision |
| `ppcert_issue_validation` | Surface course-level errors into designer/issue UI |
| `ppcert_register_merge_fields` | Expose `bfc_*` course fields as merge tokens |
| `ppcert_merge_data` | Inject live course + learner values at PDF render |
| `ppcert_certificate_issued` | Store cert id on course completion record |
| `/ppcert/v1/certificates`, `/pdf`, `/verify/{id}` | Admin/verify tooling only; issue path stays hook-driven |
| `/ppcert/v1/merge-fields` | Discover/register Burbank tokens in designer |

## Tier 2 — authoring sync (Pri 9)

| Hook / API | Burbank action |
| --- | --- |
| `pressprimer_quiz_*` created/updated/published/deleted | Keep course ↔ quiz relationship fields coherent |
| `pressprimer_quiz_rest_quiz_saved` | Same for REST-driven editors |
| `ppcert_template_duplicated` / `_issuer_changed` | Extend existing template sync |
| Trigger registration (`/ppcert/v1/trigger-types`, template triggers) | First-class "Burbank course completed" trigger |
| `/ppq/v1/quizzes*`, `/ppcert/v1/templates*` | Optional admin sync jobs; not learner path |

## Tier 3 — Pods pickers & field UX (unlocks authoring)

| Hook / API | Burbank action |
| --- | --- |
| `pods_api_register_related_object` + pick UI filters | Register PressPrimer quizzes & templates as pick targets |
| `pods_field_pick_*` data filters | Limit picks to published, non-trashed, capability-safe |
| `pods_api_save_pod_item_*` | After save, validate quiz/template still resolvable |
| `pods_rest_api_*` | Shape relationship payloads if course REST exposes them |

## Tier 4 — embed / learner UX (Pri 8)

| Hook / API | Burbank action |
| --- | --- |
| `pressprimer_quiz_before_quiz` / `_after_quiz` / results hooks | Wrap quiz in course chrome; show progress |
| Quiz interface / shell hooks | Theme alignment without forking Quiz |
| `[ppcert_my_certificates]`, `[ppcert_verify]`, view-page hooks | Link credentials back to course record |
| Pods shortcodes | Course catalog / single course embeds |

## Tier 5 — caps, banks, verification (Pri 7)

| Hook / API | Burbank action |
| --- | --- |
| Quiz bank/access/edit/exposure filters | Align with course editorial caps |
| `ppcert_certificate_scope` / `user_can_*` | Align revoke/verify with LMS roles |
| `/ppq/v1/questions*`, `/banks*` | Only if courses own question sets |

## Superseded by full-suite design

Product scope is now the monolithic suite in
[`docs/superpowers/specs/2026-10-30-full-suite-integration-design.md`](../specs/2026-10-30-full-suite-integration-design.md).
That spec’s **Out of scope** list replaces this playbook’s old “defer” list
(foreign LMS bridges, Certificate upsells, matrix pod ownership move, cert.vip sync).

## Recommended build order (inside the suite plan)

1. Invite table (`scope=site|course`) + training-access flag + site registration gate
2. Enrollment table/services (open vs restricted course rules)
3. Pods picks (quiz, template, optional `bc_requirement` slug)
4. Quiz `can_start` / `before_submit` + progress on pass
5. Certificate merge fields + issue validation + completion
6. Learner blocks (register, catalog, course, enroll, my-courses) + FSE templates
7. Mail, branding filters, admin site/course invite UI, optional AI context



## Source inventories

| Kind | Files |
| --- | --- |
| Full hooks | `pods-3.3.9.2-hooks.md`, `pressprimer-quiz-3.1.3-hooks.md`, `pressprimer-certificate-2.0.2-hooks.md` |
| Full APIs | `pods-3.3.9.2-apis.md`, `pressprimer-quiz-3.1.3-apis.md`, `pressprimer-certificate-2.0.2-apis.md` |
| Per-hook buckets | `*-hook-opportunities.md`, `pods-*-hook-families.md` |
| Per-REST buckets | `*-rest-opportunities.md` |
