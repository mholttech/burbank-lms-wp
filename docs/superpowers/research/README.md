# Plugin surface catalog — Pods + PressPrimer

Complete inventory of hooks and APIs from the pinned releases used by Burbank LMS.

| Plugin | Version | Hooks | APIs |
| --- | --- | --- | --- |
| Pods | 3.3.9.2 | [443 hooks](pods-3.3.9.2-hooks.md) | [functions, field types, key classes](pods-3.3.9.2-apis.md) |
| PressPrimer Quiz | 3.1.3 | [126 hooks](pressprimer-quiz-3.1.3-hooks.md) | [REST (54), shortcodes, caps, methods](pressprimer-quiz-3.1.3-apis.md) |
| PressPrimer Certificate | 2.0.2 | [69 hooks](pressprimer-certificate-2.0.2-hooks.md) | [REST (19), shortcodes, caps, methods](pressprimer-certificate-2.0.2-apis.md) |

## Start here

1. [integration-playbook.md](integration-playbook.md) — every high-value hook/API → concrete Burbank action (build order)
2. Annotated maps — every hook/route tagged with a priority bucket
3. Raw inventories — complete dump from pinned plugin sources

## Annotated opportunity maps

| File | Contents |
| --- | --- |
| [pressprimer-quiz-3.1.3-hook-opportunities.md](pressprimer-quiz-3.1.3-hook-opportunities.md) | All 126 Quiz hooks, priority-bucketed |
| [pressprimer-certificate-2.0.2-hook-opportunities.md](pressprimer-certificate-2.0.2-hook-opportunities.md) | All 69 Certificate hooks, priority-bucketed |
| [pods-3.3.9.2-hook-families.md](pods-3.3.9.2-hook-families.md) | All 443 Pods hooks by family |
| [pressprimer-quiz-3.1.3-rest-opportunities.md](pressprimer-quiz-3.1.3-rest-opportunities.md) | All 54 Quiz REST routes bucketed |
| [pressprimer-certificate-2.0.2-rest-opportunities.md](pressprimer-certificate-2.0.2-rest-opportunities.md) | All 19 Certificate REST routes bucketed |
| [pods-3.3.9.2-rest-opportunities.md](pods-3.3.9.2-rest-opportunities.md) | Pods REST posture (CPT + hook families) |

### Priority legend

| Pri | Meaning |
| --- | --- |
| 10 | Gates taking a quiz or issuing/merging certificate data |
| 9 | Sync authoring state or first-class Certificate citizenship |
| 8 | Learner-facing embed / results UX |
| 7 | Caps, banks, verification UX |
| ≤6 | Admin polish, email, branding, AI, foreign LMS bridges, upsells |

## Phase 1 already consumes

- Pods API provisioning + `pods_api_field_validation` + `pods_api_post_save_pod_item_bfc_course`
- Quiz `PressPrimer_Quiz_Quiz::get` + version pin
- Certificate `Template::get`, `Trigger::get_for_template`, `Trigger::find_active` + version pin
- Indirect: Certificate PPQ adapter listens to `pressprimer_quiz_quiz_passed`
