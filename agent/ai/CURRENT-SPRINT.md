# Current Sprint

> Здесь только актуальные задачи. Закрытые спринты — переносить в `agent/ai/archive/`.

**Спринт**: 5 (фазы 0–3 закрыты, см. архивные карточки ниже)
**Период**: текущий
**Цель спринта**: закрыть Phase 4-хвосты (auth на пользовательских роутах, тематические подкатегории импорта) и подключить фронт Профиля к API.

---

## Задачи

### [NEXT-001] Auth на пользовательских роутах
- **Подробное описание**: `GET/PUT /api/users/{userId}/profile`, `/api/learning/users/{userId}/*`, `/api/library/users/{userId}/*` были открыты (только `api`-middleware). Закрыты JWT: `jwt.auth` (проверка подписи/срока/iss/aud) + `user.owner` (URL-`userId` обязан совпадать с токеном).
- **Приоритет**: high
- **Статус**: done
- **Связанные файлы**: `bootstrap/app.php` (алиасы), `AuthProvider` (бинд `TokenGeneratorInterface`), `JwtTokenGenerator` (ключ как у issuer + толерантные claims), `JwtTokenIssuer` (добавлен `nbf` — без него `StrictValidAt` режектил все логин-токены), `EnsureRouteUserMatchesToken`, роуты User/Learning/Library, `/api/user` переведён с `auth:sanctum` на `jwt.auth`, `User::$casts` без `hashed` (убрано двойное хэширование пароля)
- **Ограничения**: контракт ответов не менялся; Catalog остался публичным (системный словарь)
- **Ожидаемый результат**: аноним — 401, чужой userId — 403, свой — 200 (проверено вживую через nginx + 7 новых тестов)
- **Что сделано**: матрица 401/200/403/401 подтверждена curl; тесты 35/35 (136 assertions); pint PASS (280 файлов)

### [NEXT-002] Фронт Профиля на API
- **Подробное описание**: `Profile.tsx` сейчас на моках. Подключить: загрузка/создание профиля (`PUT /api/users/{id}/profile`: `city`, `birth_date` как `Y-m-d`, `tags`), список/создание/обновление языковых профилей (`/api/learning/users/{id}/profiles`), статистика (`.../stats`). Формат даты уже совместим с `BirthDatePicker`.
- **Приоритет**: high
- **Статус**: open (ждёт NEXT-001)
- **Ограничения**: UI и позиции элементов не менять

### [NEXT-003] Тематические подкатегории импорта
- **Подробное описание**: `catalog:import-words` сейчас цепляет только grammar-категории по папке. Добавить маппинг имён файлов (`Движение.xlsx` → `movement`, …) на слаги из `CategorySeeder`, опция `--map-file` (json) для ручной донастройки.
- **Приоритет**: medium
- **Статус**: open

### [NEXT-004] UseCases для content_sources
- **Подробное описание**: CRUD источников контента (фильмы/музыка) поверх `content_sources` + привязка медиа (`entry_media.content_source_id`). Сейчас таблица и модель есть, API нет.
- **Приоритет**: low
- **Статус**: open

---

### [DOCS-001] Порядок в backend/agent (было от другого проекта)
- **Подробное описание**: переписать доки под lexio, структуру DDD-каркаса сохранить
- **Приоритет**: medium
- **Статус**: done
- **Что сделано**: AGENTS.md (пути lexio, команды lexio_php), TECHSTACK.md (реальные зависимости + фронт), LINKS.md, ARCHITECTURE.md (модули Auth/User/Catalog/Learning/Library, конвенция Take), DB_SCHEMA.md (вся схема lexio), CURRENT-SPRINT.md, CHECK_SECURITY.md + scope SECURITY_PLAYBOOK.md (PII вместо PHI), IMPLEMENTED_CONCEPT.md и LINKEDIN_ARTICLE.md переписаны под изучение языков

### [FEAT-001] Язык = профиль, цели, достижения, статистика по профилям
- **Статус**: done
- **Backend**: `user_goals` CRUD (лимит 3, `.../profiles/{id}/goals`); переключение активного профиля (создание и `is_active:true` гасят остальные); `SubmitReview` считает streak/best из `user_streaks` и дёргает движок достижений (`newly_unlocked` в ответе); `achievements` + `user_achievements` (правила: streak_days, words_learned, xp_total, reviews_total, reviews_of_type, goals_completed, accuracy), сид 9 штук, `GET .../achievements`, `POST .../achievements/evaluate`; `GetStats` считает точность живьём из `user_progresses`
- **Frontend**: выбор языка в профиле создаёт/активирует языковой профиль; цели грузятся/создаются/правятся/удаляются по активному профилю (гости — моки); достижения — с API с маппингом иконок по коду; Stats — табы профилей + реальные цифры (слова, серия, рекорд, XP, точность, due); графики пока демо (нет таблицы истории)
- **Проверено**: live-цикл register → profile → goal → review → unlock `first_lesson`; тесты 44/44; pint 320 PASS; фронт build чист

### [FEAT-002] Друзья с заявками
- **Статус**: done
- **Backend**: `friendships` (requester/addressee/status + unique, User-модуль); UseCases Send/List/Answer/Remove/Search (встречная заявка = авто-принятие, ответить может только адресат, удалить — любая сторона); API `GET|POST /users/{id}/friends[/requests]`, `POST .../requests/{rid}/accept|decline`, `DELETE .../{fid}`, `GET .../search` (имя/email, min 2 символа, relation в выдаче); список друзей обогащён (имя, аватар, уровень активного профиля, стрик)
- **Frontend**: блок «Друзья учат» на API — дебаунс-поиск, бейджи relation, входящие заявки с принять/отклонить, удаление; гости — моки как раньше
- **Проверено**: live-цикл заявка → принятие → список с уровнем/стриком; тесты 48/48; pint 349 PASS; фронт build чист

### [OPS-001] Переименование qwicki → Lexio
- **Статус**: done
- **Что сделано**: продукт (титул, бренд Auth, письма + логотип L, `APP_NAME=Lexio`), localStorage-ключи `qwicki*` → `lexio*` (нужен повторный вход), package `lexio-frontend`, инфра (`lexio_*` контейнеры/сеть, БД/пользователь `lexio`, бэкап `/tmp/opencode/lexio_backup.sql` залит 1-в-1: 12 phrase-категорий, 113 фраз, 5 юзеров), доки agent/**
- **Проверено**: `rg -i qwick` пуст; tsc чист; фронт build чист; `AuthCodeMailTest|OtpAbuseProtectionTest|PremiumTest` 9/9; smoke `/`, `/learn`, `request-code` 200, письмо «Добро пожаловать в Lexio!»; старые `qwick_*` вольюмы/образы удалены

## Закрыто (архивная сводка, детали — в git-истории)

- **Phase 0 — гигиена**: починены падающие миграции (entries/phrases/categories/learning/auth-down), порядок `users` раньше зависимых, провайдеры Catalog/Learning/Library, сиды (5 языков, 80 категорий), smoke-тест схемы.
- **Phase 1 — схема**: `user_language_profiles` + `user_language_stats`, `profile_id` в progresses/streaks, `entry_meanings` + unique `(meaning,language,text)`, `entry_phrase`, `entry_media`, `content_sources`, `city/birth_date/tags` в профиле.
- **Phase 2 — DDD-слои**: Catalog (4 read-UseCase), Learning (профили + SM-2 review + due + stats), Library (свои слова/фразы), расширение User-профиля; 16+ роутов; 23 теста.
- **Phase 3 — контент**: `catalog:import-words` (xlsx/csv, dry-run, frequency), импорт 2783 слов; `pint` чист (278 файлов); CI `.github/workflows/ci.yml`.
