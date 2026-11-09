# Symfony upgrade plan: 6.4 → 7.4 LTS (with Rector)

Status: **S0–S5 done** (2026-11-09). Branch `upgrade/symfony-7.4` is on Symfony 7.4 / PHP 8.4 / Doctrine ORM 3, CI green with the Rector gate. Next: review/merge, deploy (see S5 deployment notes), then the 8.x decision (D1).
Audit date: 2026-11-02. Target decided with owner: **7.4 LTS first**, then a separate decision about 8.x.

---

## 0. Decisions already made

| # | Decision | Chosen |
|---|----------|--------|
| D1 | Target version | **7.4 LTS** (bug fixes until 11/2028, security fixes until 11/2029). 8.x will be decided after 7.4 is green. |
| D2 | `cboden/ratchet` | **Remove**, together with `bin/chat-server.php`, `bin/push-server.php` and `src/Chat/*`. |
| D3 | Databases for upgrade work | New WSL containers on **13326 / 13396** through `var/upgrade/compose.ports.yml` plus untracked `.env.dev.local` / `.env.test.local`. The old Docker Desktop DBs on 3326/3396 are left alone. |
| D4 | Old Docker Desktop dev DB | FK `FK_8D93D64940369CAB` restored after the accidental partial migration (see §1.6). Nothing else touched. |
| D5 | Production platform | **PHP 8.4, MySQL 8.4** → PHP 8.4 package lines, `config.platform.php: 8.4`, `server_version: '8.4'`, Rector `php84` set |
| D6 | Tests | Fix test **setup** (Foundry-seeded data, schema once per run, `AppFixtures`); **assertions unchanged** |
| D7 | Removals approved | `/api/token/refresh` route (+ gesdinet bundle), `symfony/notifier` (+ its unused injection) |
| D8 | Infra approved | Makefile (host PHP) + GitHub Actions CI; Rector CI gate enabled at the end (S5) |
| D9 | Not approved / leave as-is | `src/Security/AppAuthenticator.php` stays untouched |
| D10 | Messenger queue | Not fixed in the upgrade branch; written up as a follow-up in §9 |

Why not 8.1 now: the newest stable is **8.1.10**, a short-support release with EOL **01/2027**. 8.2 is due late Nov 2026 and isn't tagged yet (only `8.2.x-dev`). Every route to 8.x passes through 7.4 anyway, because 7.4 is where the 8.0 removals are announced as deprecations.

---

## 1. Current state (baseline)

### 1.1 Versions (from `composer.lock`, vendor in sync)

| Package | Installed | Notes |
|---|---|---|
| PHP (host CLI) | 8.4.17 | `config.platform.php` is pinned to **8.2.0**. There's no PHP container: PHP runs on the WSL host. |
| symfony/* | 6.4.13–6.4.15 | `extra.symfony.require: 6.4.*`. **6.4 bug-fix support ends 11/2026 (in 28 days)**; security fixes continue until 11/2027. |
| symfony/notifier | **6.0.19** | Pinned `6.0.*` next to FrameworkBundle 6.4. Breaks `lint:container` (§1.3). |
| symfony/security-bundle | 6.4.13 | Not in `composer.json`; only a transitive dependency. |
| doctrine/orm / dbal | 2.19.7 / 3.9.1 | |
| doctrine/doctrine-bundle / migrations-bundle | 2.13.0 / 3.3.1 | |
| doctrine/annotations | 1.14.4 | Abandoned; not used by our code. |
| phpunit/phpunit | 9.6.20 | `symfony/phpunit-bridge` 6.4 |
| rector/rector | 1.2.10 | Latest is **2.8.3** |
| phpstan/phpstan | 1.12.11 | Level 6, `phpstan.dist.neon` |
| zenstruck/foundry | 1.38.3 | 4 factories still extend deprecated `ModelFactory` |
| API Platform | — | Not used |

Third-party bundles: lexik/jwt 2.18, nelmio/api-doc 4.26, nelmio/cors 2.5, jms/serializer-bundle 5.4, knp-paginator 6.2, endroid/qr-code-bundle 4.3 (qr-code 4.8), tattali/mobile-detect 2.3, willdurand/geocoder-bundle 5.20, gesdinet/jwt-refresh-token 1.3, gedmo/doctrine-extensions 3.16, beberlei/doctrineextensions `dev-master`, league/csv 9.16, nebkam/symfony-traits 4.1, nebkam/fluent-test 4.2, dama/doctrine-test-bundle 7.3.

### 1.2 Architecture and patterns (keep these)

- **Layout**: `Controller/` (8 controllers, all `AbstractController`), `Entity/` + `Repository/` (`ServiceEntityRepository`), `Form/` (16 form types used as input DTO validators), `Model/` (DTOs), `Service/`, `Factory/` (a mix of Foundry factories and plain domain "factories"), `Event/` + `EventSubscriber/`, `Command/` (3, all `#[AsCommand]`), `Message/` + `MessageHandler/` (`#[AsMessageHandler]`), `Enum/`.
- **Routing**: PHP attributes (`config/routes.yaml` → `type: attribute`). 7 files import the deprecated alias `Symfony\Component\Routing\Annotation\Route`; `SearchController` already uses `Routing\Attribute\Route`.
- **DI**: autowire + autoconfigure for `App\`. Explicit service tags in `services.yaml` duplicate autoconfigure for both subscribers and add the `UserChecker` tag. No `$this->get()`, no `ContainerAwareInterface`. Controllers get dependencies through the constructor or action arguments.
- **Doctrine**: attribute mapping only (`#[ORM\…]`), no annotations, lifecycle callbacks on most entities, one native SQL query (`UserRepository::getNearbyVets`), DQL `RAND()` from beberlei extensions.
- **Input**: JSON → Symfony Form through `Nebkam\SymfonyTraits\FormTrait::handleJSONForm()`. Validation errors go through `Nebkam\…\ValidationExceptionListener`.
- **Output**: `$this->json($data, status, [], ['groups' => ContextGroup::X])` using the Symfony Serializer with `#[Groups]` (9 files import the deprecated `Serializer\Annotation\Groups`). **The serialization groups are the public API contract.** Note that `ContextGroup::SHOW_PET === CREATE_PET` ('create_pet'); this is existing behaviour and must stay as-is.
- **API docs**: NelmioApiDoc 4 with `OpenApi\Attributes as OA` plus `Nelmio\ApiDocBundle\Annotation\{Model,Security}` (8 imports).
- **Security**: new authenticator system. `json_login` on `^/login_check` with Lexik success/failure handlers and a `UserChecker`. A stateless `jwt` firewall on `^/`. `access_control` rules plus `#[IsGranted]`. `src/Security/AppAuthenticator.php` is an **unused, non-working stub** (methods have no return).
- **Mail**: `TemplatedEmail` + Twig (inky/cssinliner), QR codes from endroid.
- **Messenger**: Doctrine transport `async`. `App\Message\Message` is routed async.
- **Scheduled jobs** (cron, outside the repo): `NotifyExaminationByEmail --time-range=…`, `MonthlyHealthRecordExportCommand`, `DeleteExpiredTokensCommand`.
- **Tests**: `KernelTestCase`/`WebTestCase` with nebkam/fluent-test, DAMA transactions, a custom `DatabasePrimer` (runs `SchemaTool::updateSchema`), Foundry factories.

**Legacy items flagged:** `security.enable_authenticator_manager`, `Routing/Serializer\Annotation` aliases, Nelmio `Annotation\*`, Foundry `ModelFactory`, DBAL `Statement::execute()->fetchAll()`, `User::eraseCredentials()` (deprecated in 7.3), `symfony/proxy-manager-bridge` (removed in 7.0), `doctrine/annotations` + `framework.annotations` integration, `config/routes/framework.yaml` → `errors.xml` (XML routing is deprecated in 7.4), DoctrineBundle `controller_resolver.auto_mapping` deprecation. None of the following were found: `sensio/framework-extra-bundle`, Guard, `ContainerAwareInterface`, `AbstractController::get()`, string form types, SwiftMailer, `Command::$defaultName`.

### 1.3 Baseline: what passes, what fails

Run on host PHP 8.4.17, Symfony 6.4.15. Saved in `var/upgrade/baseline-*` (git-ignored).

| Check | Result |
|---|---|
| `bin/console about` | ✅ |
| `lint:yaml config` | ✅ 30 files |
| `lint:twig templates` | ✅ 11 files |
| `lint:container` | ❌ **Already failing**: `notifier.channel.browser`: class `…\FlashMessage\DefaultFlashMessageImportanceMapper` does not exist. Cause: `symfony/notifier` 6.0 next to FrameworkBundle 6.4. |
| `doctrine:schema:validate` (mapping) | ✅ |
| Migrations on an **empty** DB | ✅ All 12 apply. Remaining drift: only the `messenger_messages` `DC2Type` comments. |
| Migrations on the **old populated dev DB** | ❌ `Version20240215143949` fails: `user.popularity` has values like `"16.00%"` that can't convert to `DOUBLE`. |
| PHPUnit (9.6) | ❌ **2 pass / 6 fail, then the run aborts**. Controller tests hard-code IDs (`vet: 12`, `pet: 1`, `examination: 3`) from an undocumented dataset. Entity tests crash the run ("There is no active transaction") because `DatabasePrimer` DDL implicitly commits inside DAMA's transaction. `doctrine:fixtures:load` is also broken: `AppFixtures` calls `HealthRecordFactory::createMany()`, but that class isn't a Foundry factory. |
| PHPStan level 6 | ❌ 120 errors (top: `User.php` 13, `Chat/Pusher.php` 13, `UserRepository.php` 12) |
| Rector (existing `rector.php`) | ❌ Can't run: "No paths definition". It enables `deadCode/codeQuality/typeDeclarations`, which conflicts with the upgrade-branch rules below. |
| `bin/console debug:router` | 62 routes, saved as the API-contract baseline (`var/upgrade/baseline-routes.{txt,json}`) |

### 1.4 Deprecations (`debug:container --deprecations`, 6.4)

**Our code / our config** (to fix):
- `security.enable_authenticator_manager` option (security-bundle 6.2)
- `framework.annotations` integration enabled → `annotations.*` services, `setDoctrineAnnotationReader()`, Routing/Serializer loaders receiving a Doctrine `Reader`. Caused by `doctrine/annotations` being installed.
- `doctrine.dbal.memory` + `url` (`config/packages/config_test.yaml`, which is loaded in **all** envs despite its name)
- `doctrine.orm.controller_resolver.auto_mapping` default changing / automapping deprecated (DoctrineBundle 2.12/2.13). Affects `?Pet $pet` / `?User` action arguments.
- `App\Entity\User::eraseCredentials()`: missing `void` return type
- `App\Factory\{User,Pet,Log,Examination}Factory extends ModelFactory` (Foundry)
- PHP 8.4: implicit nullable parameters in `tests/Unit/BaseTestCase::makeMockUser()`

**Vendor** (fixed by upgrading the package, not by us): symfony/notifier 6.0 (≈20 implicit-nullable messages), endroid/qr-code 4 (≈22), lexik/jwt 2.x (≈12), knp-components, geocoder (+bundle), tattali/mobile-detect, Foundry internals, gesdinet `AbstractRefreshToken`, doctrine/lexer `Token` ArrayAccess, mobiledetectlib, DAMA, Faker, PHPUnit 9 `E_STRICT`.

### 1.5 What's already migrated / half done

- Already done: attribute mapping/routes, `#[AsCommand]`, `#[AsMessageHandler]`, the new authenticator system, `handle_all_throwables`, `http_method_override: false`, `enable_lazy_ghost_objects: true`, `email_validation_mode: html5`.
- **Half done**: Rector 1.2 was installed and `rector.php` was started, but it was never runnable and never applied. `react/zmq` was removed (commits `ebd522a`, `a5f4e60`), but `bin/push-server.php` still uses it. Branches `feature/doc-and-refactor` and `feature/vet_repo_expanding` contain only query work (`6bf37ea`, `2905c59`); there's no upgrade work on them.

### 1.6 Incident during the audit (resolved)

While establishing the baseline I ran `doctrine:migrations:migrate` expecting a fresh container. WSL localhost forwarding sent `127.0.0.1:3326` to an **older Docker Desktop MySQL with existing dev data**. `Version20240215143949` partly applied before failing:
- created `messenger_messages` (empty; kept)
- dropped `on_call.chat_count` (table had 0 rows → no data lost; the column isn't mapped)
- dropped `FK_8D93D64940369CAB` → **restored** (verified 0 orphans first).

From now on, every DB-writing command is preceded by a `SELECT @@hostname` check against the expected container.

### 1.7 Other findings

- `.env` (untracked) holds **real production SMTP credentials** in plain text. Move them to `config/secrets/prod` or server env vars, and rotate the password.
- `public/images/*` and `public/exports/*` (health-record CSVs, i.e. personal data) were committed before the ignore rules existed. They're now untracked in the index (staged, not committed) and still in **git history**.
- `error_log` files from the hosting server are tracked in 10 directories.
- `config/packages/doctrine.yaml` `when@prod` sets `server_version: '14'` for **MySQL** (that's a PostgreSQL version number). Under DBAL 4 this decides the SQL platform, so it becomes `'8.4'` (production is MySQL 8.4, D5).
- There's no Dockerfile, Makefile or CI. Production looks like shared Apache hosting (`symfony/apache-pack`, `error_log` files).

---

## 2. Upgrade path

There's one major step. Doctrine and test tooling are upgraded **on 6.4 first**, so the Symfony bump itself stays small.

| Step | Symfony | PHP | What |
|---|---|---|---|
| S0 | 6.4 | 8.4 | Housekeeping: fix the broken baseline, remove dead packages, platform pin → 8.4, Rector 2.8 + PHPStan 2 tooling, Makefile |
| S1 | 6.4 | 8.4 | Test tooling: PHPUnit 12, DAMA 8, Foundry 2; make tests self-seeding → **green baseline** |
| S2 | 6.4 | 8.4 | Fix every 6.4 deprecation in our code (Rector + by hand) → deprecation-free on 6.4 |
| S3 | 6.4 | 8.4 | Doctrine: ORM 3, DBAL 4, DoctrineBundle 3, Migrations-bundle 4; local DB containers → MySQL 8.4 |
| S4 | **7.4** | 8.4 | `symfony/*` 7.4, bundles to their 7.4-compatible lines, recipes, Rector composer-based run |
| S5 | 7.4 | 8.4 | Clean up 7.x deprecations (8.0 preparation), `php84` Rector set, CI with Rector gate |

PHP: production runs **8.4** (D5). The host CLI is 8.4.17 too, so `config.platform.php` moves from `8.2.0` to `8.4.0` in S0. That makes PHP 8.4-only package lines (DoctrineBundle 3, Migrations-bundle 4) available. Symfony 7.4 itself only needs 8.2. Because 8.x also needs 8.4, a later 7.4 → 8.x step won't need another PHP upgrade.

### Target versions (Symfony 7.4 + PHP 8.4; checked on Packagist 2026-11-02)

| Package | From | To | Notes |
|---|---|---|---|
| symfony/* | 6.4 | 7.4.* | `symfony/notifier` removed (D7) |
| doctrine/orm | 2.19 | ^3.7 | |
| doctrine/dbal | 3.9 | ^4.5 | |
| doctrine/doctrine-bundle | 2.13 | ^3.3 | Requires PHP 8.4, DBAL 4, ORM ≥ 3.4.4, persistence 4; supports Symfony ^6.4 \|\| ^7 \|\| ^8 |
| doctrine/doctrine-migrations-bundle | 3.3 | ^4.0 | Requires PHP 8.4, DBAL 4 |
| doctrine/doctrine-fixtures-bundle | 3.6 | ^4.3 | |
| beberlei/doctrineextensions | dev-master | ^1.5 | Pin to a stable tag (supports ORM 3) |
| lexik/jwt-authentication-bundle | 2.18 | ^3.2 | |
| nelmio/api-doc-bundle | 4.26 | ^5.13 | **BC**: `Annotation\*` namespace removed → `Attribute\*` |
| nelmio/cors-bundle | 2.5 | ^2.6 | |
| knplabs/knp-paginator-bundle | 6.2 | ^6.11 | |
| endroid/qr-code-bundle | 4.3 | ^6.0 | **BC**: qr-code 6 Builder API (`->data()->build()` → `->build(data: …)`) |
| tattali/mobile-detect-bundle | 2.3 | ^8.0 | 8.x requires symfony ^7.4 → bump **in S4** |
| jms/serializer-bundle | 5.4 | ^5.5 | Or remove (Q5) |
| twig/extra-bundle, inky-extra, cssinliner-extra | 3.x | ^3.29 | Stay on 3.x (4.0 is alpha) |
| nebkam/symfony-traits / fluent-test | 4.1 / 4.2 | ^4.1.2 / ^4.2.1 | Support Symfony 8 too |
| league/csv | 9.16 | ^9.29 | |
| phpunit/phpunit | 9.6 | ^12.5 | DAMA 8.7 allows `^11.5.41 \|\| ^12.3.14 \|\| ^13.0`; Foundry 2.15 allows `^12` but only `~13.1`, so we use 12 |
| dama/doctrine-test-bundle | 7.3 | ^8.7 | PHPUnit 10+ extension format |
| zenstruck/foundry | 1.38 | ^2.15 | Factories migrated to `PersistentProxyObjectFactory` (or `PersistentObjectFactory`) |
| symfony/maker-bundle | 1.61 | ^1.68 | Requires 7.4 → bump in S4 |
| rector/rector | 1.2 | ^2.8 | |
| phpstan/phpstan | 1.12 | ^2.1 | |

**Removed** (unused or incompatible; each one gets its own commit):

| Package | Why |
|---|---|
| cboden/ratchet (+ `bin/chat-server.php`, `bin/push-server.php`, `src/Chat/*`, unused `use Ratchet\…` in `MessageHandler`) | Blocks Symfony 7+ (`symfony/http-foundation ≤ ^6`). Decision D2. |
| symfony/proxy-manager-bridge | Removed in Symfony 7.0; lazy ghost objects are already on. |
| doctrine/annotations | Abandoned; nothing uses it. Removing it also clears the `framework.annotations` deprecations. |
| gesdinet/jwt-refresh-token-bundle | Not registered in `bundles.php`, no entity, no firewall config. The route `/api/token/refresh` in `config/routes/gesdinet_jwt_refresh_token.yaml` has no controller → remove the route file too. **Public route change approved (D7).** |
| willdurand/geocoder-bundle | Registered, but its config is empty and the code doesn't use it. |
| gedmo/doctrine-extensions | Not registered and not used. |
| nyholm/psr7 | Not used (`php-http/discovery` provides PSR-17 factories). |
| symfony/notifier (+ `config/packages/notifier.yaml`, the unused `NotifierInterface` constructor argument in `NotifyExaminationByEmailCommand`) | Only injected, never called. Its 6.0 pin breaks `lint:container`. Approved (D7). |

There are no incompatible bundles left without an alternative.

---

## 3. Rector strategy

### 3.1 What the installed Rector actually provides (read from vendor, 2.8.3)

Rector 2.8.3 was installed in a scratch directory to read `vendor/rector/rector/vendor/rector/rector-symfony/`:

- `Rector\Symfony\Set\SymfonySetList` contains only: `CONFIGS`, `COMPOSER_BASED`, `SYMFONY_CODE_QUALITY`, `SYMFONY_CONSTRUCTOR_INJECTION`, `ANNOTATIONS_TO_ATTRIBUTES`.
- **There are no `SYMFONY_64` / `SYMFONY_74` / `SYMFONY_80` constants and no `SymfonyLevelSetList` any more.** All version rules live in `config/sets/symfony/composer-based.php` as `withComposerBasedRule(rule, config, 'symfony/<pkg>', '>=X.Y')`. That file has rules gated up to `>=8.2`. The rules relevant to us: `>=6.4` (Routing `Annotation\Route` → `Attribute\Route`, HttpFoundation/ErrorHandler class renames, Form return types), `>=7.0` (Serializer annotation → attribute renames, DI/Routing attribute renames), `>=7.1`–`>=7.4` (renames for Serializer, Mailer, Notifier, Console, FrameworkBundle, Routing).
- Doctrine and PHPUnit work the same way (`rector-doctrine/config/sets/composer-based.php`, `rector-phpunit/config/sets/composer-based.php`). Twig has no composer-based set in this version, so `twig: true` is a no-op.

**Consequence: we bump the composer constraints first, then run Rector.** The template's "add `SymfonySetList::SYMFONY_XX`" approach doesn't exist in this Rector version. In every step, `withComposerBased()` turns on only the rules whose gate the **installed** version satisfies:
- S2 (still on 6.4): the `>=6.4` and older rules → the Route/Serializer attribute renames that are already valid on 6.4.
- S4 (after `composer update` to 7.4): the `>=7.0 … >=7.4` rules run.
- Doctrine composer-based rules run after S3 (ORM 3 / DBAL 4 installed); PHPUnit rules run after S1 (PHPUnit 11 installed).

### 3.2 Container metadata

`var/cache/dev/App_KernelDevDebugContainer.xml` exists (704 KB). It's rebuilt with `bin/console cache:warmup` (dev) before every Rector run. `withSymfonyContainerPhp()` isn't needed: there's no `$this->get()` or string form types for it to resolve.

### 3.3 Final `rector.php` (draft; replaces the current non-runnable one in S0)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->withSkip([
        __DIR__ . '/config/bundles.php',
        __DIR__ . '/config/preload.php',
        __DIR__ . '/var',
        __DIR__ . '/migrations',            // never rewrite executed migrations
        '*/error_log',                      // hosting log files, not PHP
    ])
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    // version rules are gated by the *installed* package versions (see §3.1)
    ->withComposerBased(symfony: true, doctrine: true, phpunit: true)
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true)
    ->withSets([
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,   // expected no-op; kept as a guard
    ])
    // Nelmio 5 removes Nelmio\ApiDocBundle\Annotation\*; no Rector set covers it.
    // Enabled only in the commit that bumps nelmio/api-doc-bundle to ^5 (S4).
    ->withConfiguredRule(RenameClassRector::class, [
        'Nelmio\ApiDocBundle\Annotation\Model' => 'Nelmio\ApiDocBundle\Attribute\Model',
        'Nelmio\ApiDocBundle\Annotation\Security' => 'Nelmio\ApiDocBundle\Attribute\Security',
    ])
    // Enabled last, each as its own commit (S5):
    // ->withPreparedSets(symfonyCodeQuality: true, symfonyConfigs: true)
    // ->withPhpSets(php84: true)   // production runs PHP 8.4 (D5)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withParallel()
    ->withCache(__DIR__ . '/var/cache/rector');
```

Deliberately **not** enabled on this branch: `deadCode`, `codeQuality`, `codingStyle`, `typeDeclarations`, `privatization`, `naming` (they go in a later, separate branch). The current `rector.php` enables three of these plus two `TypeDeclaration` rules, and that config is replaced.

### 3.4 Order of runs (one set per commit)

1. S0: replace `rector.php` and commit it **without running it** against `src/` (dry-run output is attached to the commit message for review).
2. S2: `withComposerBased(symfony)` on 6.4 → expected: 7× `Routing\Annotation\Route` → `Routing\Attribute\Route`. Commit `rector: apply symfony composer-based rules (6.4)`.
3. S2: `withAttributesSets(...)` → expected near no-op (code is already attribute-based). Commit only if there's a diff.
4. S1/S2: `withComposerBased(phpunit)` after PHPUnit 11 → test-method annotations/attributes, `@dataProvider`, etc.
5. S3: `withComposerBased(doctrine)` after ORM 3 / DBAL 4.
6. S4: after `composer update` to 7.4 → `withComposerBased(symfony)` again (the 7.0–7.4 rules: `Serializer\Annotation\{Groups,SerializedName}` → `Serializer\Attribute\…`). Same commit as the Nelmio bump: the `RenameClassRector` for Nelmio.
7. S5: `symfonyCodeQuality`, then `symfonyConfigs`, then `withPhpSets(...)`. Three separate commits.

After each run: `vendor/bin/rector process --dry-run` → review → `process` → CS fixer (see 3.6) → tests → commit `rector: …`.

### 3.5 Custom rules

**None planned.** The repeated project-specific patterns are covered by built-ins (`RenameClassRector` for Nelmio). The single endroid call site (`src/Model/QRCode.php:38`) and the single DBAL call site (`src/Repository/UserRepository.php:97-107`) are fixed by hand, because a custom rule plus fixtures would be more code than the fix. `utils/rector/` won't be created unless the S4 diff shows a repeated pattern.

### 3.6 Workflow integration

- `composer.json` scripts: `"rector": "rector process"`, `"rector:check": "rector process --dry-run"`, `"phpstan": "phpstan analyse"`.
- **Makefile** (approved, D8): none exists today and PHP runs on the host, so targets call `php`/`vendor/bin/*` directly (`make rector`, `make rector-check`, `make test`, `make lint`, `make phpstan`, `make db-up` using `var/upgrade/compose.ports.yml`). No PHP container gets added.
- **CS fixer**: none configured. To keep "match the project style" without a big reformat, add **PHP-CS-Fixer with a minimal `@Symfony`-free ruleset** (only `no_unused_imports`, `ordered_imports`) and run it only on files Rector touched. Alternatively, skip it (Q7).
- **CI** (approved, D8): none exists (GitHub remote). Add `.github/workflows/ci.yml` (PHP 8.4, `mysql:8.4` service, `composer validate`, lint:*, PHPStan, PHPUnit) once S1 has a green suite. The `vendor/bin/rector process --dry-run` step is added **only in S5**, once the codebase is clean.

---

## 4. Blockers and risks

| Area | Risk | Where | Mitigation |
|---|---|---|---|
| Blocker | Ratchet blocks Symfony 7 | `composer.json`, `bin/*-server.php`, `src/Chat/*` | Remove (D2) |
| Broken baseline | `lint:container` fails | notifier 6.0 | S0: remove notifier (D7) |
| Broken baseline | Tests can't run reproducibly; fixtures broken | `tests/**`, `AppFixtures.php` | S1: tests seed their own data through Foundry. **Assertions stay unchanged** (D6) |
| DBAL 4 BC | `Statement::execute()` / `Result::fetchAll()` removed | `src/Repository/UserRepository.php:97-107` (`/vets/nearby` endpoint) | `$conn->executeQuery($sql, [...])->fetchAllAssociative()`; same result shape (assoc arrays) |
| DBAL 4 | `DC2Type` column comments dropped → schema diff | `messenger_messages` + every `datetime_immutable` column | Generates a comment-only migration. **Shown to you before commit.** |
| DBAL 4 | Wrong `server_version: '14'` for MySQL in prod | `config/packages/doctrine.yaml` `when@prod` | Set to `'8.4'` (D5). `DATABASE_URL` in your untracked `.env.local` also says `serverVersion=14`; that's yours to change (my `.env.dev.local` override already uses a real version). |
| Env parity | Local containers are **Percona 8.0**, production is **MySQL 8.4** | `docker-compose.yml` (untracked) | S3: switch the upgrade override `var/upgrade/compose.ports.yml` to `mysql:8.4` images, so migrations and `schema:validate` run against the production engine. Your `docker-compose.yml` stays untouched. |
| DBAL 4 | `doctrine.dbal.memory` + sqlite driver in `config_test.yaml` (loaded in every env) | `config/packages/config_test.yaml` | Delete the file; `url` already wins today, so behaviour doesn't change (verify the test env still uses MySQL) |
| ORM 3 | `ManyToOne` typed `private User $vet` but the column is nullable / `ON DELETE SET NULL` | `src/Entity/HealthRecord.php:27-29` | Unchanged behaviour in ORM 3. Listed under "found along the way", not fixed here. |
| DoctrineBundle | `controller_resolver.auto_mapping` default changes | `?Pet $pet` / `?User` arguments with `{id}` in 8 controllers | Set `auto_mapping: false` explicitly. `{id}` → `find()` keeps working; any route that relies on mapping **other** params by name gets `#[MapEntity]`. Verify against the route baseline. |
| DoctrineBundle 2.19 config | `auto_generate_proxy_classes`, `enable_lazy_ghost_objects`, `report_fields_where_declared` are deprecated/no-op with ORM 3 | `doctrine.yaml` | Apply the recipe update; keep the custom `dql`, `naming_strategy` and `when@prod` settings |
| Security | `enable_authenticator_manager` removed in 7.0 | `security.yaml:2` | Delete the line (no behaviour change; the new system is already on) |
| Security | `UserInterface::eraseCredentials()` deprecated in 7.3 | `src/Entity/User.php:208` | Empty body → add `#[\Deprecated]` / remove after 7.3 per UPGRADE-7.3; a no-op today |
| Security | `AppAuthenticator` stub (no return values) | `src/Security/AppAuthenticator.php` | Not wired in `security.yaml`. **Left untouched (D9)**; excluded from Rector via `withSkip` if Rector tries to change it. |
| Serializer/API contract | Rector renames `Serializer\Annotation\Groups` → `Attribute\Groups` | 9 files | Pure namespace alias; verify JSON output of the smoke tests is byte-identical |
| API docs | Nelmio 5 removes `Annotation\*`; may describe models differently, especially with JMS installed | 5 controllers, `/doc`, `/doc.json` | Diff `/doc.json` before and after |
| QR | endroid/qr-code 6 Builder API | `src/Model/QRCode.php:38`, `config/packages/endroid_qr_code.yaml` (option names changed in 5/6) | Hand fix + recipe; smoke-test `POST /qr-code` (mail to Mailcatcher). Also check that the bundle route `qr_code_generate` (`/qr-code/{builder}/{data}`, from `config/routes/endroid_qr_code.yaml`) survives bundle 6. |
| Routing | `@FrameworkBundle/Resources/config/routing/errors.xml` deprecated (7.3/7.4) | `config/routes/framework.yaml` | Recipe → `errors.php` (dev only) |
| Routing | Duplicate route name `app.swagger_ui` | `config/routes.yaml` + `config/routes/nelmio_api_doc.yaml` | Existing; the later definition wins. Unchanged. |
| Data | `user.popularity` legacy `"NN.00%"` strings break `Version20240215143949` on old DBs | migrations | Doesn't affect prod if prod already ran it (Q2b). Migrations are **never** edited. |
| Messenger | No worker, no failure transport → chat messages may never be persisted | §9 | Behaviour stays unchanged in this branch; the messenger recipe update in S4 is merged without changing the transport/routing |
| Data | Health-record CSV exports and pet images in git history | `public/exports`, `public/images` | Now untracked. A history rewrite is a separate decision (Q10). |
| Secrets | Real SMTP password in `.env` | `.env` (untracked) | Move to secrets / server env and rotate (Q10) |
| Deploy | Production runs PHP 8.4 / MySQL 8.4 (D5) | hosting | Check that `ext-intl`, `ext-gd` (QR PNG), `pdo_mysql` are enabled on the host's PHP 8.4 before deploying |

---

## 5. Ordered task checklist

Each item is one commit on `upgrade/symfony-7.4`. After every item: tests (once green) + `lint:container` + PHPStan don't get worse.

### S0: Housekeeping on 6.4 ✅ (2026-11-02)
- [x] Create branch `upgrade/symfony-7.4` from `main`
- [x] chore: commit the already-staged untracking of `public/images`, `public/exports` + the `.gitignore` `/.env.*.local` rule (`da2f93f`)
- [x] chore: untrack `**/error_log` + ignore rule (`59fdec9`)
- [x] fix: remove `symfony/notifier`, `config/packages/notifier.yaml` and the unused `NotifierInterface` argument (D7) → `lint:container` ✅ (`ee68941`)
- [x] chore: remove `cboden/ratchet` + `bin/chat-server.php`, `bin/push-server.php`, `src/Chat/`, unused `use Ratchet\…` (`0695a24`)
- [x] chore: remove `symfony/proxy-manager-bridge` (`eb32a57`)
- [x] chore: remove `doctrine/annotations`; all `framework.annotations` / Doctrine `Reader` deprecations gone (`7174eab`)
- [x] chore: remove `gesdinet/jwt-refresh-token-bundle` + its route/config files (D7). Route diff vs baseline: only `gesdinet_jwt_refresh_token` removed (`9ea2dd5`)
- [x] chore: remove `willdurand/geocoder-bundle` **+ php-http/\* + `nyholm/psr7`** (`5c8bccc`), see deviation 1
- [x] chore: remove `gedmo/doctrine-extensions` (`a7dfbaf`)
- [x] chore: pin `beberlei/doctrineextensions` to `^1.5`; DQL `RAND()` verified (`042dd65`)
- [x] chore: require `symfony/security-bundle: 6.4.*` explicitly (`753077b`)
- [x] build: `config.platform.php` `8.2.0` → `8.4.0`, `require.php` → `>=8.4`, `symfony/*` → 6.4.49, `lcobucci/jwt` 4.3 (`ec79b25`), see deviation 2
- [x] chore: delete `config/packages/config_test.yaml` (`5768cf8`), see deviation 3
- [x] build: Rector `2.8.3`, PHPStan `2.3.4`, new `rector.php`, composer scripts, Makefile (`b7a1133`), see deviation 4
- [x] build: `.gitattributes` keeps LF for `Makefile`, `bin/*`, `*.sh` (`0a7a256`), see deviation 5
- [x] build: PHPStan baseline (`b1afca9`), see deviation 6

**S0 deviations from the plan**
1. `nyholm/psr7` wasn't completely unused: it was the PSR-17 implementation that `php-http/discovery` found for `config/packages/http_discovery.yaml`. Removing the geocoder removed php-http/discovery, and nothing in `src/` or the bundles autowires PSR-7/17, so nyholm was removed in the same commit. The removed class `Http\Discovery\Exception\NotFoundException` was still thrown in `PetController::foundPet()`. It's replaced by `\RuntimeException` with the same message, so `GET /found_pet?id=<unknown>` keeps its current response (500). I first committed without noticing this and amended the commit before moving on. A new local check (`var/upgrade/check-imports.php`) now resolves every import/`new`/`throw` in `src/` and `tests/` after each dependency change.
2. The PHP 8.4 platform bump couldn't be done before the removals (`laminas-code`, via proxy-manager, and `lcobucci/clock` 2 don't allow PHP 8.4), so it moved after them and includes a 6.4 patch update.
3. The deleted file also set `charset: UTF8`, which applied to dev/test MySQL URLs without a `charset` parameter. Prod sets `utf8mb4` explicitly, so it's unaffected.
4. Rector dry run (6.4): 13 rule hits + `removeUnusedImports` in 28 files. **Skipped** `EventSubscriberToAttributeRector`: it would replace our two `EventSubscriberInterface` classes with `#[AsEventListener]`, which changes the pattern and conflicts with the explicit `kernel.event_subscriber` tags in `services.yaml`. Rector's DBAL rename (`execute()` → `executeQuery()`, `fetchAll()` → `fetchAllAssociative()`) covers the planned hand fix in `UserRepository::getNearbyVets()`. The dry-run output is in `var/upgrade/rector-s0-dryrun.json`.
5. New and not in the plan: with `core.autocrlf=true` a checkout turns `Makefile` / `bin/console` into CRLF, which breaks make and the shebang.
6. PHPStan 2 is stricter: 238 baselined errors instead of 120. `src/Security/AppAuthenticator.php` is excluded via `excludePaths`, because its 4 `return.missing` errors are non-ignorable and the file stays untouched (D9).

### S1: Test tooling on 6.4 ✅ (2026-11-02)
- [x] test: PHPUnit `12.5`, `phpunit.xml.dist` rewritten for PHPUnit 12 (bridge `SymfonyExtension` + DAMA extension instead of the PHPUnit 9 listener), symfony/phpunit-bridge `7.4` (`2b3e74f`)
- [x] rector: phpunit composer-based rules: **no-op** (no rule matched the tests)
- [x] test: DAMA `8.7` + `doctrine.dbal.use_savepoints: true` (`2b3e74f`)
- [x] test: Foundry `2.15`: the 4 factories → `PersistentObjectFactory`; `AppFixtures` builds health records with an anonymous Foundry factory; `HealthRecordFactory` unchanged (`2b3e74f`)
- [x] test: `DatabasePrimer` → Foundry `ResetDatabase` (schema once per run); controller tests seed their own data; assertions unchanged except where they compared to a hard-coded ID (`2b3e74f`)
- [x] **Gate: full suite green on 6.4**: 11 tests / 49 assertions, 3 consecutive runs
- [x] ci: GitHub Actions workflow (`8c332a1`), simulated in a clean clone (all steps green after `f034375`)

**S1 deviations**
1. **Regression found and fixed:** Rector 2.8 (S0) bundles `phpstan/phpdoc-parser` 2, and its `bootstrap.php` (in Composer's autoload `files`) could load that copy into the app process. `phpdocumentor/type-resolver` 1.8 then called the v1 API, so `POST/PUT /health_record` returned 500 in dev/test. Production (no dev packages) was never affected. Fix: the app's `phpstan/phpdoc-parser` → `^2.1` (type-resolver 1.12, reflection-docblock 5.6, jms/serializer 3.32, webmozart/assert 2, not used by our code).
2. DAMA 8 pulled `doctrine/doctrine-bundle` 2.13 → 2.19 early (still the 2.x line, ORM 2.19).
3. Foundry's own `UP_TO_FOUNDRY_2` Rector set doesn't load on Rector 2 (removed interface, plus it needs phpstan-doctrine), so the 4 factories were migrated by hand.
4. `UserFactory` and the test mock users now set `verified` (NOT NULL column; the old test DB predated it).
5. CI writes throwaway `.env` / `.env.test` and a throwaway JWT keypair, because both env files are git-ignored here. The `schema:validate` sync check is skipped until S3 (DBAL 3 `DC2Type` comment drift).
6. The PHPStan baseline was line-ending dependent (PHPDoc parse-error offsets differ between CRLF/LF). Offsets are now matched with `\d+` and `make phpstan-baseline` regenerates + normalizes. Baseline: 238 → 131 entries.

### S2: Clean up 6.4 deprecations ✅ (2026-11-02)
- [x] rector: unused imports split into their own commit (97 lines, 34 files). Serializer metadata for all entities/models identical (`d88a98e`)
- [x] rector: symfony composer-based rules (6.4): `Routing\Annotation\Route` → `Routing\Attribute\Route`, 7 controllers; route table identical (`c60b54b`)
- [x] rector: doctrine composer-based rules: DBAL `execute()`/`fetchAll()` → `executeQuery()`/`fetchAllAssociative()` (the planned S3 hand fix, done by Rector; output of `getNearbyVets()` identical on fixture data) (`5add8ea`); `@extends ServiceEntityRepository<…>` on 3 repositories (`79f1a67`)
- [x] rector: attributes sets: no-op. **`vendor/bin/rector process --dry-run` = 0 changes on 6.4**
- [x] fix: remove `security.enable_authenticator_manager`; effective security config identical (`2df796d`)
- [x] fix: `User::eraseCredentials(): void` (`e6434dc`)
- [x] fix: `controller_resolver.auto_mapping: false`. All 20 entity arguments come from `{id}` or `#[CurrentUser]`, so no `#[MapEntity]` was needed (`7f03da0`)
- [x] fix: implicit nullable params in tests (part of `2b3e74f`)
- [x] **Gate passed:** `debug:container --deprecations` has 0 entries from `App\` or our config. The 37 left are vendor (endroid/qr-code 21, Lexik 13, Knp 2, mobile-detect 1), all fixed by the S4 bumps

### S3: Doctrine ORM 3 / DBAL 4 (still on Symfony 6.4) ✅ (2026-11-02)
- [x] local: WSL DB containers → `mysql:8.4` (fresh volumes); the 6.4/DBAL 3 baseline was re-verified on 8.4 first (12 migrations from scratch, fixtures, tests)
- [x] fix: `User`/`Pet::preUpdate()` wrote `DateTimeImmutable` into `DATETIME_MUTABLE` columns, which DBAL 4 rejects; now `DateTime` (`1e34a07`), see deviation 1
- [x] deps: `doctrine/orm 3.7.6`, `doctrine/dbal 4.5.0`, `doctrine-bundle 3.3.4`, `migrations-bundle 4.0.1`, `fixtures-bundle 4.3.1`, `persistence 4.2` + `doctrine.yaml` per UPGRADE-3.0/3.1 (`4515f28`)
- [x] recipes: `doctrine/doctrine-bundle`, merged by hand (`13c88f4`), see deviation 2
- [x] recipes: `doctrine/doctrine-migrations-bundle`, **namespace change rejected** (`d702b70`), see deviation 3
- [x] fix: `getNearbyVets()`, already done by Rector in S2 (`5add8ea`)
- [x] fix: `server_version: '8.4'` + `collate` → `collation` in `when@prod` (`4515f28`); the prod container builds and lints
- [x] rector: doctrine composer-based rules with ORM 3 installed → **0 changes**
- [x] db: `Version20261102205151` (DC2Type comments, messenger index), **approved before commit** (`ef9813b`); up/down/up tested; row counts unchanged
- [x] **Gate passed:** `schema:validate` fully in sync on a fresh DB built from migrations; tests green; CI simulation 12/12 (`var/upgrade/ci-sim.sh`)

**S3 deviations / findings**
1. **Real DBAL 4 BC break found by the tests:** every User/Pet update would have thrown `InvalidType` (the vet popularity update after creating a health record hit it first). This wasn't in the plan's risk list.
2. The doctrine-bundle recipe **silently** switched `naming_strategy` to `underscore` during auto-merge. That was reverted to our `underscore_number_aware`. The recipe's prod query/result cache drivers weren't taken (deliberately commented out in this project).
3. The migrations-bundle 3.7 recipe moves migrations to `src/Migrations` / `App\Migrations`. That would make all executed migrations look new in production (the versions table stores FQCNs), so only the recipe version in `symfony.lock` was recorded.
4. All 20 custom repository queries return identical data on ORM 2.19/DBAL 3 and ORM 3.7/DBAL 4 (`var/upgrade/repo-probe.php`). The only difference is that ORM 3 returns real classes instead of `Proxies\__CG__\…`; no code depends on that.
5. Recreating a container keeps anonymous volumes. The first MySQL 8.4 start upgraded the old 8.0 data dir in place, which incidentally showed that an **8.0 → 8.4 data upgrade works** with this schema.

### S4: Symfony 7.4 ✅ (2026-11-09)
- [x] deps: `extra.symfony.require: 7.4.*`, all `symfony/*` → `7.4.22`; Lexik `3.2`, KnpPaginator `6.11`, endroid bundle `6.1` in the same step (their old lines cap Symfony at 6) (`7818a8b`)
- [x] fix: endroid qr-code 6 `->build(data: $url)`; our `endroid_qr_code.yaml` values equal the v6 defaults (`7818a8b`)
- [x] deps+rector: NelmioApiDocBundle `5.13` (swagger-php 7) + `RenameClassRector` (`8187fbf`); tattali mobile-detect `8` (`e555e44`); twig extras `3.29`, CVE-2026-46637 (`f591e7f`); nelmio-cors `2.6`, league/csv `9.29` (`d69065f`)
- [x] rector: symfony composer-based rules 7.x: `Serializer\Annotation\Groups` → `Serializer\Attribute\Groups` (`a2b192e`); generated `config/reference.php` skipped and untracked (`7091fd8`, `0a8ee22`)
- [x] recipes, one commit per package: framework-bundle (`e075c3e`), security-bundle (`46c646f`), routing (`4c0b9b5`), mailer (`29cf569`), messenger (`c8af47b`, transport/routing unchanged, §9), translation (`ca2a782`), twig-bundle (`867c12d`), validator (`4fbeb35`), console (`c24386e`), phpunit (`d3b498e`), phpunit-bridge (`0c2db77`), flex (`752ec0f`), apache-pack (`b6794b1`), dama (`3739907`), foundry (`2623f12`). `composer recipes -o` now lists only migrations-bundle (rejected on purpose, S3 deviation 3)
- [x] **Gate passed:** `cache:clear`; CI simulation (`var/upgrade/ci-sim.sh`, clean clone, fresh DB from migrations): composer validate, lint:container/yaml/twig, `schema:validate`, PHPStan 0 new errors, PHPUnit 11/49 green; `rector --dry-run` = 0 changes; route list = baseline minus `/api/token/refresh` (D7, 62 → 61); API smoke test (48 requests) byte-identical to the pre-recipe run (`var/upgrade/smoke-s4-recipes` vs `smoke-minor`)

**S4 deviations / findings**
1. `symfony/form` ≥ 7.2 brings a `csrf.yaml` recipe that enables form CSRF protection. **Not taken**: every JSON form submission of the API (`FormTrait::handleJSONForm`) would fail.
2. `templates/forgotten_password/index.html.twig` deleted: unused MakerBundle scaffold using the debug-only `file_link` filter, which fails `lint:twig` on 7.4.
3. The phpunit-bridge 7.3 recipe deletes `bin/phpunit`; the launcher now belongs to the phpunit/phpunit recipe, so phpunit was updated **first** to keep every commit runnable.
4. phpunit recipe: `failOnDeprecation/Notice/Warning="true"` and Doctrine/Symfony deprecation triggers taken; suite stays green. Its `method_exists(Dotenv::class, 'bootEnv')` guard is always true on 7.4 (PHPStan error), so `tests/bootstrap.php` calls `bootEnv()` directly (`673accf`).
5. Not taken from recipes: flex's empty `.env.dev` (env files stay out of git), Foundry's empty `src/Story/AppStory.php` placeholder (`AppFixtures` stays the entry point), Foundry's `reset.mode: auto` (ours: `schema`, D6). Taken: `FoundryExtension` in `phpunit.xml.dist` (suite 7.7 s → 2.3 s) and apache-pack's `/index.php/…` redirect 301 → **308** (keeps method and body).
6. `debug:container --deprecations` after S4: 2 left. `App\Security\UserChecker::checkPostAuth()` needs the `?TokenInterface $token` argument (**ours → S5**); `Nelmio\ApiDocBundle\Attribute\Security` extends a swagger-php class deprecated for 8.0 (vendor).

### S5: 7.x deprecations / tooling gate ✅ (2026-11-09)
- [x] fix: `UserChecker::checkPostAuth()` takes `?TokenInterface $token` (8.0 signature); `debug:container --deprecations` from `App\` = 0 (`efad76e`). PHPUnit `failOnDeprecation` (scoped to `src/`) green. The one remaining entry is vendor (Nelmio `Attribute\Security` extends a swagger-php class deprecated for 8.0)
- [x] rector: `symfonyCodeQuality`: 2 unused `Request` controller arguments removed; routes identical (`7b72209`)
- [x] rector: `symfonyConfigs`: no-op, kept enabled as a guard (`38f4a60`)
- [x] rector: `withPhpSets(php84: true)`, 18 files (`dcc6d54`), see deviation 1
- [x] ci: `rector process --dry-run` gate after PHPStan, with a dev `cache:warmup` first (`5add1bc`); CI simulation 13/13
- [x] docs: README → Symfony 7.4; deployment notes below

**S5 deviations / findings**
1. `ClassPropertyAssignToConstructorPromotionRector` is skipped for `src/Entity` and `src/Model`: promoting e.g. `ContactMessage`/`PaginatedResult` properties reorders their declarations, which changes the **JSON key order** of API responses and moves ORM mapping into constructors. With the skip, the 48-request smoke test is byte-identical (`var/upgrade/smoke-php84`).
2. `fputcsv()` got an explicit `escape: '\\'` (PHP 8.4 deprecates relying on the default). Output verified identical on quotes, backslashes, commas, newlines.
3. `MonthlyHealthRecordExportCommand` called `getLastMonthHealthRecords(7)`, but the method takes no argument (always last 30 days). Rector dropped the ignored `7`; behaviour unchanged. If a configurable range was intended, that's a feature for later.
4. PHPStan baseline: 4 entries removed (now fixed), nothing added.

**Deployment notes (production: PHP 8.4, MySQL 8.4, Apache)**
1. Host: PHP **8.4** with `ctype`, `iconv`, `intl`, `gd` (QR PNG), `pdo_mysql`. MySQL **8.4** (an in-place 8.0 → 8.4 data-dir upgrade worked locally with this schema, S3 deviation 5). Back up the DB first.
2. Read-only check first: `bin/console doctrine:migrations:status` (Q2b).
3. `composer install --no-dev --optimize-autoloader`, `APP_ENV=prod`, `DATABASE_URL=…?serverVersion=8.4&charset=utf8mb4`.
4. `bin/console doctrine:migrations:migrate`: one new migration, `Version20261102205151` (DBAL 4 type comments + messenger index; schema only, no data change). Migration namespace unchanged (`DoctrineMigrations`).
5. `bin/console cache:clear` (prod). JWT keys / passphrase are unchanged (Lexik 3 reads the same files).
6. Remove anything that runs `bin/chat-server.php` / `bin/push-server.php` (supervisor, cron), they are gone (D2). Cron command names are unchanged.
7. **API changes for the Angular frontend:** `POST /api/token/refresh` no longer exists (D7). Everything else is byte-identical in the smoke test. Apache now redirects `/index.php/…` with **308** instead of 301.

## 6. Verification strategy

**Each step**: `composer validate`, `lint:container`, `lint:yaml config`, `lint:twig templates`, `doctrine:schema:validate` (on a fresh DB from migrations), PHPUnit, PHPStan (no new errors versus the baseline), `debug:container --deprecations` (our code = 0), `debug:router` diffed against `var/upgrade/baseline-routes.json`.

**Smoke tests** (curl against `symfony serve` / `php -S`, dev env, local WSL DB with fixtures, mail → Mailcatcher). Each response's status code and JSON body is recorded before (6.4) and after (7.4) and diffed:

| Flow | Requests |
|---|---|
| Auth | `POST /login_check` (valid / invalid / unverified user → `UserChecker`), JWT on a protected route, `#[IsGranted('ROLE_ADMIN')]` route as user → 403 |
| Owners | register user (`POST /user`, welcome mail), `GET /user/me`, update user, verify account, forgotten-password flow |
| Pets | create / edit / get / upload image / `GET /found_pet`, `POST /qr-code` (QR mail) |
| Vets | list vets, `/vets/simplified`, nearby vets (**DBAL 4 change**), free vets in a time range |
| Appointments (health records) | create (`PostHealthRecordCreationEvent` → popularity), edit, cancel, list per user/pet, paginated list, delete |
| Examinations | CRUD (admin) |
| On-call / chat | create on-call, send contact message (Messenger `async` → run `messenger:consume async --limit=1`), list messages per chat |
| Search | the `SearchController` endpoints |
| API docs | `GET /doc.json` diff (Nelmio 5) |
| Scheduled jobs | `NotifyExaminationByEmail --time-range=…`, `MonthlyHealthRecordExportCommand` (CSV), `DeleteExpiredTokensCommand`, all with `--env=dev` on the local DB |

(The exact paths come from `var/upgrade/baseline-routes.txt`; this table lists the flows.)

**Phase 5**: fresh `docker compose down -v && up -d` (WSL stack with the ports override), migrations, fixtures, full suite, the smoke table above, `rector --dry-run` = 0 changes.

---

## 7. Open questions

Answered 2026-11-02: Q1 → PHP 8.4, Q2 → MySQL 8.4, Q3/Q4 → remove, Q6 → Makefile + CI, Q8 → fix test setup, Q9 → leave `AppAuthenticator` (see D5–D10).

Still open (each has a default that I'll use unless you say otherwise):

1. **Q2b – Has production already run `Version20240215143949` and later?** (i.e. is `user.popularity` already `DOUBLE`?) Default: check `doctrine:migrations:status` on prod **read-only** before deploying; never edit migrations.
2. **Q5 – JMS serializer:** unused by our code, but its presence can change how Nelmio documents models. *Default: keep it (safer for `/doc.json`).*
3. **Q7 – CS fixer:** *Default: add a minimal PHP-CS-Fixer config (imports only) and run it only on files Rector touched.*
4. **Q10 – Secrets / history:** rotate the SMTP password and move it out of `.env`; optionally rewrite git history to purge the health-record CSVs and images. *Default: outside the upgrade, so I won't touch it.*

## 8. Found along the way (not fixed in this branch)

- `HealthRecord::$vet` is typed `User` (non-nullable) but mapped `nullable: true, onDelete: SET NULL`.
- `UserRepository::findByRole()` returns `getResult()[0]` (undefined index when empty); `TokenEntityRepository` runs the same query twice.
- `ContextGroup::SHOW_PET` and `CREATE_PET` have the same value.
- `NotifyExaminationByEmailCommand` has the placeholder description "Add a short description for your command" and manually instantiates `TemplatedEmailService`.
- Explicit `kernel.event_subscriber` tags in `services.yaml` duplicate autoconfigure.
- `services.yaml` overrides the `kernel.environment` parameter with `%env(APP_ENV)%`.
- ~~The duplicate `app.swagger_ui` route is defined twice.~~ Fixed (`e2a1901`).
- `UserController` and the access_control in `security.yaml` have overlapping/ordered rules (`^/vets/` before `^/vets/free`).
- PHPStan: 120 level-6 errors.
- The `popularity` column history
- `GET /found_pet?id=<unknown>` answers 500 (an uncaught RuntimeException); the commented-out line above it shows a 404 was intended.
- `src/Entity/Token.php` imports a non-existent `App\Model\Token as ModelToken` (unused, so it's harmless).
- ~~**Security:** the `access_control` rule `^/health_records` (plural) didn't match the `/health_record/...` routes~~ → **fixed** on branch `fix/health-record-access-control` (`0ccc35d`, Swagger `15a3ee5`): all 8 health-record routes require login; 7 of them answered 200 to anonymous requests before. Still open: any logged-in user can read/edit/delete **any** health record (no owner/vet check).
- ~~**Security (wider):** most other routes were reachable without a token~~ → **fixed** on `main` 2026-11-09 (`e2a1901`, Swagger `d36bf6c`): default-deny `access_control` with a public allow-list (login, registration, account verification, password reset, `/found_pet`, `/vets/simplified`, `/active_vets`, `/doc[.json]`). `tests/Unit/Security/AccessControlTest.php` sends an anonymous request to every route and expects 401 unless allow-listed. Dead rules and the duplicate `app.swagger_ui` / controller-less `api_login_check` routes removed. Still open: **ownership checks** (any logged-in user can change another user's pets, profile, health records).
- `UserTest::testCreatedUser` and `testCreatedUserWithVet` are `protected`, so PHPUnit never runs them.
- `UserRepository::findByRole()` is declared `User|int` but returns `null` when nothing matches (TypeError).
- `tests/Unit/BaseTestCase.php` imports the generated `Symfony\Config\ZenstruckFoundry\DatabaseResetterConfig` without using it. (`VARCHAR` "NN.00%" → `DOUBLE`).

---

## 9. Follow-up (separate branch after the upgrade): Messenger chat queue

Asked for on 2026-11-02 ("fix the queue system for messaging … or write somewhere for later refactor"). It isn't fixed in the upgrade branch because the fix changes runtime behaviour and depends on what the hosting allows.

**Current flow**: `POST /message` (`OnCallController::sendMessage`) → `MessageType` form fills `App\Message\Message` → `MessageBusInterface::dispatch()` → transport `async` = `doctrine://default?auto_setup=0` (`.env`; table `messenger_messages`, created by `Version20240215143949`) → `App\MessageHandler\MessageHandler` persists a `ContactMessage`. The response is always `"message sent"` (200). Clients read messages through `GET /chat/{chatId}`.

**Problems found**

| # | Problem | Where | Effect |
|---|---|---|---|
| M1 | No worker defined anywhere: the compose `messenger-consumer` is commented out, and there's no supervisor/cron in the repo | `docker-compose.yml`, hosting | Unless prod runs `messenger:consume async`, messages are **never persisted** (they pile up in `messenger_messages`) |
| M2 | No `failure_transport`, no `retry_strategy` | `config/packages/messenger.yaml` | Failed messages are dropped after the default 3 retries, without a trace |
| M3 | `getUser()` returns `find()` typed as `User` | `src/MessageHandler/MessageHandler.php:43-46` | An unknown sender/receiver ID → `TypeError` inside the worker → retried, then lost |
| M4 | `Message::getCreatedAt()` returns a non-nullable type, but `createdAt` defaults to `null` and is never set before dispatch | `src/Message/Message.php:74-86` | A latent `TypeError` if anything calls it |
| M5 | The real-time push (Ratchet/ZMQ) is gone; the frontend relies on reload/polling | `OnCallController.php:99-104` (commented-out ZMQ) | Not a bug after the Ratchet removal; replacing it (e.g. Mercure) is a feature decision |
| M6 | `messenger_messages` columns keep `DC2Type` comments | DB | Fixed by the DBAL 4 migration in S3 |

**Proposed fix (branch `fix/messenger-chat-queue`, after 7.4 is merged)**
1. Decide where the work runs (needs your input):
   - **(a)** The host can run a long-lived process → supervisor/systemd `messenger:consume async --time-limit=3600 --memory-limit=128M`.
   - **(b)** Shared hosting with only cron → cron every minute: `messenger:consume async --limit=50 --time-limit=55`.
   - **(c)** No async needed → route `App\Message\Message` to `sync://`, so the message is persisted inside the request. This is the simplest option and matches the `"message sent"` response.
2. Add `failure_transport: failed` (`doctrine://default?queue_name=failed`) + `retry_strategy` (3 retries, ×2 backoff).
3. Handler: look up sender/receiver as `?User`; on `null`, throw `UnrecoverableMessageHandlingException` (no pointless retries).
4. Tests: an in-memory transport test for the handler + a functional test for `POST /message` → `GET /chat/{chatId}`.
5. Optional (feature): Mercure for real-time delivery instead of the removed Ratchet push.

---

## 10. Decision log

| Date | Decision |
|---|---|
| 2026-11-02 | Target 7.4 LTS first (D1); remove Ratchet (D2); work on WSL DBs at 13326/13396 (D3); restore FK on old dev DB (D4) |
| 2026-11-02 | Rector 2.8 has no `SYMFONY_XX` sets → strategy is "bump constraints, then run `withComposerBased`" |
| 2026-11-02 | Git: `core.filemode=false` set in the local repo config (the Windows IDE showed false mode changes for 755 files) |
| 2026-11-02 | D5–D10: prod PHP 8.4 + MySQL 8.4 → PHP 8.4 package lines (DoctrineBundle 3, Migrations-bundle 4, PHPUnit 12.5); fix test setup; remove `/api/token/refresh` + notifier; Makefile + CI; leave `AppAuthenticator`; Messenger queue written up as §9 |
