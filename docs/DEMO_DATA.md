# Demo data and publication boundaries

This edition is built from a clean working-tree export. It does not carry the
operational application's Git history, database, original catalogue, original
pricing seed, logs, uploads, deployment guide, hostnames or credentials.

The visible product name is GrainLedger Demo. All seeded products are prefixed
`Demo`, all prices are illustrative, and the sales/deliveries are generated
relative to the current date. The reviewer account uses the reserved
`example.test` domain. Its published password is a demo credential, not a secret.

The eight-product fixture includes changing purchase costs, daily sales, one
out-of-stock item and one low-stock item. Screenshots must always be recreated
from this data, not copied from the operational application.

Database files, SQL exports, key files, local environment settings, backups,
logs and uploads are excluded from the repository. Ignore rules do not erase
already committed data; review staged files and commit history before release.
Dependency lockfiles remain included for reproducible installation.

The demo seeder is local/testing only and rejects any occupied database. This
is a demonstration guard, not a substitute for access control. Do not connect
this repository to real business data or deploy its public login unchanged.

No customer, supplier or staff records are needed for this showcase. The
public Git author should be Nduta06; GitHub's noreply address can be used to
avoid exposing a personal email in new commits.
