# PressPrimer Certificate 2.0.2 — every REST route annotated

19 routes from pinned plugin inventory.

| Pri | Bucket | Namespace | Path | File |
| --- | --- | --- | --- | --- |
| 10 | CERT_LIFECYCLE | `ppcert/v1` | `/certificates` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| 10 | CERT_LIFECYCLE | `ppcert/v1` | `/certificates/(?P<credential_id>[A-Za-z0-9\-]+)/pdf` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| 10 | CERT_LIFECYCLE | `ppcert/v1` | `/certificates/(?P<id>\d+)` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| 10 | CERT_LIFECYCLE | `ppcert/v1` | `/verify/(?P<credential_id>[A-Za-z0-9\-_.]{1,40})` | `includes/api/class-ppcert-rest-verification-controller.php` |
| 10 | MERGE_FIELDS | `ppcert/v1` | `/merge-fields` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| 10 | MERGE_FIELDS | `ppcert/v1` | `/merge-fields/post-meta-keys` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| 10 | MERGE_FIELDS | `ppcert/v1` | `/merge-fields/user-meta-keys` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| 9 | TEMPLATE_CRUD | `ppcert/v1` | `/templates` | `includes/api/class-ppcert-rest-templates-controller.php` |
| 9 | TEMPLATE_CRUD | `ppcert/v1` | `/templates/(?P<id>\d+)` | `includes/api/class-ppcert-rest-templates-controller.php` |
| 9 | TEMPLATE_CRUD | `ppcert/v1` | `/templates/(?P<id>\d+)/preview` | `includes/api/class-ppcert-rest-templates-controller.php` |
| 9 | TEMPLATE_CRUD | `ppcert/v1` | `/templates/(?P<id>\d+)/test-email` | `includes/api/class-ppcert-rest-templates-controller.php` |
| 9 | TEMPLATE_CRUD | `ppcert/v1` | `/templates/(?P<id>\d+)/triggers` | `includes/api/class-ppcert-rest-triggers-controller.php` |
| 9 | TRIGGERS | `ppcert/v1` | `/trigger-types` | `includes/api/class-ppcert-rest-triggers-controller.php` |
| 6 | USERS | `ppcert/v1` | `/users/search` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| 5 | ANALYTICS | `ppcert/v1` | `/dashboard` | `includes/api/class-ppcert-rest-dashboard-controller.php` |
| 5 | ANALYTICS | `ppcert/v1` | `/dashboard/chart` | `includes/api/class-ppcert-rest-dashboard-controller.php` |
| 4 | SETTINGS | `ppcert/v1` | `/email-optin` | `includes/api/class-ppcert-rest-email-optin-controller.php` |
| 4 | SETTINGS | `ppcert/v1` | `/settings` | `includes/api/class-ppcert-rest-settings-controller.php` |
| 4 | SETTINGS | `ppcert/v1` | `/settings/test-email` | `includes/api/class-ppcert-rest-settings-controller.php` |
