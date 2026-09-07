# Tech Stack

## Языки и рантайм
| Компонент | Версия |
|---|---|
| PHP | 8.5 (`lexio_php`, образ `lexio-php`) |
| Node | 22 (frontend) |
| БД (основная) | PostgreSQL 17 (`lexio_postgres`, база `lexio`) — миграции/сиды/импорт идут через неё |
| БД (тесты) | sqlite `:memory:` (см. `phpunit.xml`) |

## Фреймворки
| Назначение | Фреймворк | Версия |
|---|---|---|
| Backend | Laravel | ^13.0 |
| Frontend | React + Vite | ^19.2 / ^7.1 |
| Стили фронта | Tailwind CSS | ^4.1 |
| UI-кит фронта | HeroUI | ^3.0 |
| Анимации фронта | framer-motion | ^12.38 |

## Библиотеки backend
| Библиотека | Версия | Назначение |
|---|---|---|
| lcobucci/clock | ^3.5 | общая реализация времени |
| lcobucci/jwt | ^5.6 | выпуск/проверка JWT access-токенов (модуль Auth) |
| laravel/sanctum | ^4.0 | guard для `auth:sanctum` роутов |
| laravel-lang/lang | ^15.28 | локализация |
| avadim/fast-excel-reader | ^3.0 | чтение xlsx-словарей (`catalog:import-words`) |
| laravel/tinker | ^3.0 | отладка |

## Библиотеки backend (dev)
| Библиотека | Версия | Назначение |
|---|---|---|
| laravel/pint | ^1.27 | code style (`./vendor/bin/pint --test app database tests routes`) |
| phpunit/phpunit | ^12.5 | тесты (`php artisan test`, sqlite memory) |
| vimeo/psalm | ^6.15 | статический анализ |
| fakerphp/faker | ^1.23 | фабрики для тестов |
| mockery/mockery | ^1.6 | моки в тестах |

## Важные зависимости и почему они важны
- **lcobucci/jwt** — выпуск и проверка access-токенов проходят только через него (`JwtTokenIssuer`, `JwtAccessTokenVerifier`).
- **avadim/fast-excel-reader** — единственный разрешённый парсер xlsx для импорта словарей.
- **ramsey/uuid** (`^4.9`, прямая зависимость) — `Uuid::uuid4()` в сидах/репозиториях.

## Инфраструктура
- Docker: `lexio_php` (PHP 8.5), `lexio_nginx` (:8080), `lexio_postgres` (PostgreSQL 17, :5432) — основная БД backend.
- CI: `.github/workflows/ci.yml` — backend (install → pint → migrate → seed → test), frontend (install → build).

## Правило добавления новых зависимостей
Любая новая библиотека — только после согласования с пользователем.
Агент может предложить, но не должен ставить пакет самостоятельно без подтверждения.
