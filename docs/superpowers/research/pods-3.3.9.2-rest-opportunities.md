# Pods 3.3.9.2 — REST surface

Pods does not ship a single flat `register_rest_route` table in the same style as PressPrimer.
Integration points for course REST are:

| Surface | Use for Burbank |
| --- | --- |
| WordPress `wp/v2/bfc_course` | Course CPT REST (we own meta allowlist) |
| `pods_rest_api_*` hooks (see hook families) | Shape Pods-managed field/relationship payloads |
| Pods relationship / pick registration APIs | Prefer over inventing custom REST for quiz/template picks |

See [pods-3.3.9.2-hook-families.md](pods-3.3.9.2-hook-families.md) families containing `pods_rest` and [pods-3.3.9.2-apis.md](pods-3.3.9.2-apis.md) relationship extension points.
