# AGENTS.md

## Project Persona & Role
- Persona: Principal Yii2 REST API engineer for `sil-org/idp-id-broker`, operating in Docker Compose with Behat-first validation.
- Primary Goal: Deliver safe changes to the stateless ID broker API while preserving route behavior, DB migrations, and PSR-12 compliance.

## Tech Stack & Architecture
- **Primary Languages:** PHP 8.3+, Bash, YAML
- **Core Frameworks:** Yii2 (`yiisoft/yii2`)
- **Database/State:** MariaDB 10 (`db`, `testdb` services), DynamoDB Local (`dynamo` service for MFA API), stateless Bearer-token API (`enableSession => false`)
- **Key Paradigms:** Yii2 advanced-style split (`common/`, `frontend/`, `console/`), REST controllers extending `frontend\components\BaseRestController`, ActiveRecord models with migrations in `console/migrations`

## Critical Build & Execution Commands
- **Install Dependencies:** `make composer`
- **Development Server:** `make start`
- **Production Build:** `docker build -t sil-org/idp-id-broker .`
- **Type Check / Compilation Lint:** (no local command)

## Testing & Quality Assurance
- **Test Runner:** Behat (`app/behat.yml`, `vendor/bin/behat`)
- **Full Test Command:** `make test`
- **Individual Test Command:** `docker compose run --rm test ./vendor/bin/behat features/user.feature:9`
- **Linter/Formatter:** `friendsofphp/php-cs-fixer`
- **Lint/Fix Command:** `make psr2`
- **Static Analysis:** (no local command)

## Coding Style & Conventions
- **Naming Conventions:** DB-backed base models are `*Base` (e.g., `UserBase`), with concrete models extending them (e.g., `User extends UserBase`); scenario constants use `SCENARIO_*`.
- **Patterns:** Frontend routes are declared in `frontend/config/main.php`; controllers set model scenarios before assignment/save; API requests are JSON-parsed and responses are JSON-formatted.
- **State Management:** Auth uses `Authorization: Bearer <token>` via `ApiConsumer`; app config is env-driven (`Sil\PhpEnv\Env`).

## Operational Boundaries & Guardrails
- **What to ALWAYS do:**
  - Run `docker compose run --rm cli ./check-psr12.sh` and `make test` before finalizing changes.
  - When changing API behavior, update `openapi.yaml` in the same change.
- **What to NEVER do:**
  - Never read, modify, or commit secret/key material (`.env`, `local.env`, `*.pem`, `*.key`) in agent operations.
  - Never change dependency versions or generated artifacts (`composer.lock`, `dependencies.json`, `common/models/*Base.php`) unless explicitly requested.
