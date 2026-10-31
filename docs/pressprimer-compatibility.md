# PressPrimer compatibility

Burbank LMS checks a course's PressPrimer quiz and certificate configuration before approval and publication. All PressPrimer-specific code lives in `Burbank_LMS\Integrations\PressPrimer`. Course workflow is covered in [course-authoring.md](course-authoring.md).

## Supported releases

| Plugin | Minimum version | Version constant |
| --- | --- | --- |
| PressPrimer Quiz | **3.1.3** | `PRESSPRIMER_QUIZ_VERSION` |
| PressPrimer Certificate | **2.0.2** | `PPCERT_VERSION` |

Support is a floor, not a pin: installed versions must be at least these minima. Newer releases are allowed when the inspected public contracts still match. Older releases fail closed. If a newer release breaks a contract method (missing, non-static, or different arity), publication still fails until the adapter is updated. The test environment pins the minimum pair in `.wp-env.test.json`.

## Contracts used

Before any lookup, the adapter checks that both version constants meet the minima and that each method below exists and is `public static` with exactly the parameter count shown in its signature:

| Call | Expected behavior in the supported release |
| --- | --- |
| `PressPrimer_Quiz_Quiz::get( $id )` | Returns the quiz row object or `null`. `status` is `draft`, `published` or `archived`. |
| `PressPrimer_Certificate_Template::get( $id )` | Returns a template row that is not soft-deleted, or `null`. `status` is `draft`, `published` or `archived`. |
| `PressPrimer_Certificate_Trigger::get_for_template( $id )` | Returns trigger rows with `trigger_type`, `source_ref` and `is_active`. |
| `PressPrimer_Certificate_Trigger::find_active( $trigger_type, $source_ref )` | Returns active trigger rows, with `template_id`, on every template whose `source_ref` equals `$source_ref` or the sentinel `any`. |

At issuance, Certificate's quiz adapter listens for `pressprimer_quiz_quiz_passed` and calls `Trigger::find_active( 'ppq_quiz', $quiz_id )`. It issues a certificate for every row returned, whichever template the row belongs to. Value-only trigger types store `NULL`.

## Mapping rules

A course's `pressprimer_quiz_id` and `certificate_template_id` pass only when all of the following hold:

1. The quiz exists and its `status` is `published`.
2. The template exists and its `status` is `published`.
3. Among the template's **active** `ppq_quiz` triggers (inactive triggers and other trigger types are ignored):
   - at least one has `source_ref` equal to the course quiz ID;
   - none has `source_ref` of `any`, `NULL` or empty, because a broad trigger would issue this course's certificate for any quiz;
   - none points at a different quiz ID, because the template would also issue for that quiz.
4. No **other** template has an active `ppq_quiz` trigger for the course quiz or for `any`, because passing the quiz would also issue that template's certificate. The adapter checks this with `find_active( 'ppq_quiz', $quiz_id )`. Triggers on trashed templates are excluded: Certificate soft-deletes a template without deactivating its triggers, but `Template::get()` returns `null` for it and it cannot issue. Draft and archived templates still count, so their triggers must be deactivated too.
5. No other course (draft, published, archived or trashed; auto-drafts excluded) uses the same quiz ID. `Publication` enforces this rule. The adapter does not.

## Errors

Each failure has its own code and an actionable message. REST returns them with HTTP `400`. Classic saves show them in the draft notice.

| Code | Cause |
| --- | --- |
| `burbank_course_dependencies` | Quiz or Certificate missing, inactive, older than the minimum versions, or a changed contract. *Activate PressPrimer Quiz 3.1.3 or newer and PressPrimer Certificate 2.0.2 or newer before publishing.* No further checks run. |
| `burbank_course_quiz` | Quiz missing or not published. |
| `burbank_course_certificate` | Template missing or not published. |
| `burbank_course_any_quiz` | An active `ppq_quiz` trigger with `any`, `NULL`, or empty `source_ref` scope. |
| `burbank_course_trigger_mismatch` | An active `ppq_quiz` trigger for a different quiz. |
| `burbank_course_trigger` | No active `ppq_quiz` trigger for the selected quiz. |
| `burbank_course_competing_trigger` | Another non-trashed template (published, draft or archived) has an active `ppq_quiz` trigger for this quiz or for `any`. The message lists the template IDs to deactivate. |
| `burbank_course_duplicate_quiz` | Another course already uses the quiz. |

Missing or unsupported PressPrimer blocks **approval and publication**. It also blocks valid assessed edits to an already published course, because those edits are checked against the mapping. Drafts can still be edited, and published courses can still be archived. Burbank LMS never publishes on the assumption that dependencies are fine.

## Hooks consumed (learner suite)

Thin adapters in `Integrations\PressPrimer_Quiz`, `Integrations\PressPrimer_Certificate`, and optional `Branding\PressPrimer` translate PressPrimer hooks into enrollment, progress, merge, and chrome rules. Mapping validation in `Integrations\PressPrimer` is unchanged. Full inventories stay under `docs/superpowers/research/`.

### Quiz

| Hook | Kind | Behavior |
| --- | --- | --- |
| `pressprimer_quiz_can_start` | filter | For a published `bfc_course` mapped by `pressprimer_quiz_id`: require `Learner::can_learn` and an active enrollment. Unmapped quizzes pass through ungated. |
| `pressprimer_quiz_before_submit` | filter | Same checks again at submit. |
| `pressprimer_quiz_quiz_passed` | action | Stamp `quiz_passed_at` on the active enrollment once (priority 10, before Certificate's `ppq_quiz` issuer at 20). |

### Certificate

| Hook | Kind | Behavior |
| --- | --- | --- |
| `ppcert_register_merge_fields` | filter | Register Burbank tokens (`bfc.*`), including learner legal name — never email. |
| `ppcert_merge_data` | filter | Fill legal name, `bfc_code`, title, revision, outcome, subject, and matrix slug at issue/render. |
| `ppcert_issue_validation` | filter | For `ppq_quiz` issuances mapped to a course: require active enrollment and `quiz_passed_at`. |
| `ppcert_certificate_issued` | action | Store credential id, set enrollment `completed`, fire `burbank_lms_course_completed`. |

### Optional branding (when option `burbank_lms_brand_pressprimer` is truthy)

| Hook | Kind | Behavior |
| --- | --- | --- |
| `pressprimer_quiz_plugin_name` | filter | Replace display name with Burbank label. |
| `pressprimer_quiz_dashboard_logo` | filter | Prefer site icon / filtered logo URL. |
| `pressprimer_quiz_shell_branding` | filter | Product name / logo bag for learner shell when exposed. |
| `ppcert_plugin_name` | filter | Same name override for Certificate chrome. |
| `ppcert_dashboard_logo` | filter | Same logo override for Certificate chrome. |

Not consumed in this suite: `ppcert_before_issue` (validation runs on `ppcert_issue_validation` only), quiz fail/completed/attempt audit hooks, Certificate upsell/mascot filters, PP certificate email content filters, and PressPrimer AI prompt context filters.

## What Burbank LMS does not do

- It does not grade quizzes, record attempts or decide who passed. PressPrimer Quiz does.
- It does not issue, render or verify certificates. PressPrimer Certificate does, through its own trigger.
- It does not create or edit quizzes, templates or triggers. Configure them in PressPrimer, then reference their IDs on the course.
- It does not query PressPrimer database tables directly or rely on undocumented internals beyond the contracts above.
- It does not build a parallel AI stack or Certificate upsell/mascot UI.

Publication still checks that the configuration a course points at exists, is published, and is scoped to that course's quiz alone. Learner adapters additionally gate mapped quiz start/submit and certificate completion against enrollment.

## Test environment

`.wp-env.test.json` pins the dependencies the suite runs against:

- WordPress 7.1.3 and PHP 8.2
- Pods 3.3.9.2
- PressPrimer Quiz 3.1.3 and PressPrimer Certificate 2.0.2 (official WordPress.org zips)

`tests/bootstrap.php` loads Pods, both PressPrimer plugins and Burbank LMS. Setting `BFC_TEST_NO_PRESSPRIMER=1` skips loading PressPrimer, which runs the "plugins absent" path. `tests/pressprimer-fixture.php` creates real quiz, template and trigger rows through the released model APIs. `CoursePublicationTest` simulates missing plugins, other versions and changed contracts by overriding `installed_versions()` and `contract_methods()`. `tests/phpstan/pressprimer.stub` declares the four contract methods for static analysis only.
