# PressPrimer compatibility

Burbank LMS checks a course's PressPrimer quiz and certificate configuration before approval and publication. All PressPrimer-specific code lives in `Burbank_LMS\Integrations\PressPrimer`. Course workflow is covered in [course-authoring.md](course-authoring.md).

## Supported releases

| Plugin | Version | Version constant |
| --- | --- | --- |
| PressPrimer Quiz | **3.1.3** | `PRESSPRIMER_QUIZ_VERSION` |
| PressPrimer Certificate | **2.0.2** | `PPCERT_VERSION` |

Support is exact. Any other version of either plugin, including newer ones such as Certificate 2.1.0, is treated as unsupported. These are the releases the Burbank CERT site pins. To support a new release, inspect its released artifact, update `PressPrimer::QUIZ_VERSION` / `CERTIFICATE_VERSION` and the contract notes in the class docblock, update `.wp-env.test.json`, and rerun the suite.

## Contracts used

Before any lookup, the adapter checks that both version constants match and that each method below exists and is `public static` with exactly one parameter:

| Call | Expected behavior in the supported release |
| --- | --- |
| `PressPrimer_Quiz_Quiz::get( $id )` | Returns the quiz row object or `null`. `status` is `draft`, `published` or `archived`. |
| `PressPrimer_Certificate_Template::get( $id )` | Returns a template row that is not soft-deleted, or `null`. `status` is `draft`, `published` or `archived`. |
| `PressPrimer_Certificate_Trigger::get_for_template( $id )` | Returns trigger rows with `trigger_type`, `source_ref` and `is_active`. |

At issuance, Certificate's quiz adapter listens for `pressprimer_quiz_quiz_passed` and calls `Trigger::find_active( 'ppq_quiz', $quiz_id )`. That matches active triggers whose `source_ref` equals the quiz ID or the sentinel `any`. Value-only trigger types store `NULL`.

## Mapping rules

A course's `pressprimer_quiz_id` and `certificate_template_id` pass only when all of the following hold:

1. The quiz exists and its `status` is `published`.
2. The template exists and its `status` is `published`.
3. Among the template's **active** `ppq_quiz` triggers (inactive triggers and other trigger types are ignored):
   - at least one has `source_ref` equal to the course quiz ID;
   - none has `source_ref` of `any`, `NULL` or empty, because a broad trigger would issue this course's certificate for any quiz;
   - none points at a different quiz ID, because the template would also issue for that quiz.
4. No other course (draft, published, archived or trashed; auto-drafts excluded) uses the same quiz ID. `Publication` enforces this rule. The adapter does not.

## Errors

Each failure has its own code and an actionable message. REST returns them with HTTP `400`. Classic saves show them in the draft notice.

| Code | Cause |
| --- | --- |
| `burbank_course_dependencies` | Quiz or Certificate missing, inactive, a different version, or a changed contract. *Activate the supported PressPrimer Quiz 3.1.3 and PressPrimer Certificate 2.0.2 releases before publishing.* No further checks run. |
| `burbank_course_quiz` | Quiz missing or not published. |
| `burbank_course_certificate` | Template missing or not published. |
| `burbank_course_any_quiz` | An active `ppq_quiz` trigger with `any` or `NULL` scope. |
| `burbank_course_trigger_mismatch` | An active `ppq_quiz` trigger for a different quiz. |
| `burbank_course_trigger` | No active `ppq_quiz` trigger for the selected quiz. |
| `burbank_course_duplicate_quiz` | Another course already uses the quiz. |

Missing or unsupported PressPrimer blocks **approval and publication**. It also blocks valid assessed edits to an already published course, because those edits are checked against the mapping. Drafts can still be edited, and published courses can still be archived. Burbank LMS never publishes on the assumption that dependencies are fine.

## What Burbank LMS does not do

- It does not grade quizzes, record attempts or decide who passed. PressPrimer Quiz does.
- It does not issue, render or verify certificates. PressPrimer Certificate does, through its own trigger.
- It does not create or edit quizzes, templates or triggers. Configure them in PressPrimer, then reference their IDs on the course.
- It does not query PressPrimer database tables directly or rely on undocumented internals beyond the contracts above.

The only checks Burbank LMS runs are that the configuration a course points at exists, is published, and is scoped to that course's quiz alone.

## Test environment

`.wp-env.test.json` pins the dependencies the suite runs against:

- WordPress 7.1.3 and PHP 8.2
- Pods 3.3.9.2
- PressPrimer Quiz 3.1.3 and PressPrimer Certificate 2.0.2 (official WordPress.org zips)

`tests/bootstrap.php` loads Pods, both PressPrimer plugins and Burbank LMS. Setting `BFC_TEST_NO_PRESSPRIMER=1` skips loading PressPrimer, which runs the "plugins absent" path. `tests/pressprimer-fixture.php` creates real quiz, template and trigger rows through the released model APIs. `CoursePublicationTest` simulates missing plugins, other versions and changed contracts by overriding `installed_versions()` and `contract_methods()`. `tests/phpstan/pressprimer.stub` declares the three contract methods for static analysis only.
