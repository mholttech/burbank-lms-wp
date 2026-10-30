# Phase 1 course catalog and editorial controls

## Intent and accepted decisions

Build the course authoring foundation for Burbank CERT Independent Study, following `docs/independent-study-implementation-spec.md`. The user confirmed Phase 0 passed. Pods is a required dependency and owns course type/field definitions; Gutenberg remains the content editor. Course records use WordPress posts and metadata.

## Approaches

Selected: a plugin-managed Pods definition for the `bfc_course` post type and its fields, backed by WordPress posts and metadata, with Gutenberg and a small permanent number registry. The plugin supplies versioned, idempotent schema provisioning through supported Pods APIs and remains independent of the site theme.

Considered: core-only type and field registration. Rejected because Pods is a core requirement. Existing membership types remain authoritative; the plugin references them without duplicating or changing their records.

## Behavior

Training Editors draft courses with title, body, excerpt, image and revisions. A course stores its BFC code, separate revision, subject, one final quiz reference, certificate template reference, optional matrix requirement slug, independent-study outcome, approval audit and source notes. Approval is restricted to Training Administrators. Editing assessed metadata invalidates approval; an ordinary content edit does not erase historical credentials.

BFC codes follow the specified subject ranges and remain unique across draft, published, archived and deleted records. A transactional registry reserves each code. First publication permanently binds the code to that course; deletion retains the reservation. Unpublished draft reservations may be changed without reusing a previously published code.

Publication requires valid metadata, authorized approval, an available published quiz, an active matching certificate configuration, and no conflicting quiz assignment. REST publication returns actionable errors. Classic-editor publication is held as draft with an actionable notice when validation fails. Missing PressPrimer dependencies block publication. Missing Pods blocks course initialization and displays an actionable administrator notice; existing stored records are retained.

Archiving removes a course from new learner discovery while retaining its identity and history. Phase 1 supplies editorial controls and admin list columns; learner catalog presentation follows in Phase 3.

## Implementation boundaries

Business logic lives under `src/` and hooks register through Loader. Dedicated course capabilities distinguish editors, approvers and learners. Administrators receive the management capabilities through an idempotent upgrade routine. Metadata authorization applies to REST and admin saves; clients cannot write approval audit fields directly.

A PressPrimer adapter contains all dependency-specific inspection. Its implementation must use the exact installed release contracts discovered from available artifacts; missing or unsupported contracts produce explicit validation errors, never optimistic publication. Native PressPrimer remains responsible for grading and issuance.

Declare `Requires Plugins: pods` in the WordPress plugin header and guard Pods API calls at runtime. Pods manages the course schema and field UI; plugin services enforce capabilities, validation, approval and immutable identity for every write path, including Pods saves and REST requests. The existing matrix relationship remains read-only and stores stable requirement slugs; missing matrix records do not break course viewing or assessment. Do not create replacement membership types when they are absent. This phase creates no curricula, quiz questions, certificates, or program approvals. It creates no learner progress state and changes no membership eligibility.

## Validation

The isolated test environment includes Pods. WordPress application tests cover dependency handling, idempotent Pods schema provisioning, Pods save validation, post-type and metadata registration, capability boundaries, BFC format/range validation, concurrent uniqueness and permanent reservations, approval invalidation, conflicting quiz mappings, publication failures and archiving. Run the existing PHPUnit environment, PHPStan and PHPCS; run asset checks if editor assets change. Document operational behavior in docs and add a high-level AGENTS.md reference.

## Subsequent work

After this design is approved, produce an implementation plan, then implement Phase 1 with tests. Identity and access, learner status projections, export/restore tooling and pilot delivery remain part of the full product scope in the baseline spec.

## Builder review resolutions

Before registration, detect a conflicting existing `bfc_course` type and report an administrative error rather than replacing its definition. Use core post status for publication and an explicit catalog state for archive visibility.

Provide an explicit material-revision action: assessed content changes require a new revision and renewed approval; editorial corrections may retain approval under the administrator's policy. Changing code, revision, subject, quiz, certificate reference or outcome invalidates approval. Keep immutable approval snapshots of revision, title, outcome, quiz and certificate mappings so later edits preserve attribution of historical achievements. This audit records course configuration, not learner completion. Bound all free-text metadata and document these limits.
