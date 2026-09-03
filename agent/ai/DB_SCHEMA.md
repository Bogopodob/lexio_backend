# DB Schema

> Любое изменение схемы — только через миграции и с подтверждением пользователя.
> Порядок миграций топологический (родители раньше детей): `users` → `languages` → `categories` → `learning`/`library` → `entries` → `phrases` → `entry_media`/`content_sources` → `translations`. Файлы лежат в `app/<Module>/Infrastructure/Persistence/Database/Migrations/` (+ `app/Shared/...` для `translations`).

## Таблицы

### users
Базовая таблица аккаунтов (создаётся миграцией User-модуля).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| name | string | not null |
| email | string | unique, not null |
| email_verified_at | timestamp | nullable |
| password | string | not null |
| remember_token | string | nullable |
| created_at / updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

### user_profiles
Расширенный профиль (1:1 к `users`, PK — `user_id`).

| Поле | Тип | Ограничения |
|---|---|---|
| user_id | string | PK (1:1 к users.id) |
| name / lastname / surname | string | nullable |
| avatar | string | nullable, путь/URL |
| city | string | nullable |
| birth_date | date | nullable, формат API `Y-m-d` |
| tags | json | nullable, массив строк, max 6 (валидация в Request) |
| is_active | boolean | default false |
| is_doctor | boolean | default false (зарезервировано) |
| created_at / updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

> Уровень (`level`) и изучаемый язык в профиле НЕ хранятся — они живут в `user_language_profiles` (у пользователя их несколько).

### auth_identities
Все способы входа пользователя — один пользователь может иметь несколько identity.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | ссылается на users.id |
| provider | enum (AuthProviderEnum: phone, email, google, vk, yandex) | not null |
| provider_id | string | not null, уникальный ID внутри провайдера |
| secret | string | nullable, хэш пароля/credential |
| access_token / refresh_token | text | nullable, внешние токены |
| token_expires_at | timestamptz | nullable, index |
| verified_at / last_used_at | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

Уникальность: `(provider, provider_id)` — `uq_provider_identity`.
Индекс: `(user_id, provider)` — `idx_user_provider`.

### auth_contacts
Телефонные контакты пользователя для авторизации.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | ссылается на users.id |
| country_code | string(5) | not null, напр. +7, +1 |
| country_iso | string(2) | not null, напр. RU, US |
| national_number | string(20) | not null, номер без кода страны |
| e164_format | string(25) | unique, канонический вид +79999999999 |
| is_primary | boolean | default false |
| verified_at | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

Индекс: `(country_iso, national_number)` — `idx_contact_lookup`.

### auth_otp_codes
Одноразовые коды (OTP) для регистрации/входа.

| Поле | Тип | Ограничения |
|---|---|---|
| uid | uuid | PK |
| provider_value | string(25) | not null, целевой канал |
| code_hash | string(64) | not null, хэш кода |
| purpose | enum (AuthPurposeEnum) | not null, register/auth |
| attempts | tinyInteger | default 0 |
| max_attempts | tinyInteger | default 3 |
| is_used | boolean | default false |
| expires_at | timestamptz | not null |
| used_at | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

Индекс: `(provider_value)` — `idx_otp_lookup`.

### auth_otp_rate_limits
Rate limit на отправку OTP.

| Поле | Тип | Ограничения |
|---|---|---|
| uid | uuid | PK |
| value | string(25) | unique, целевой канал (телефон/email) |
| ip_address | string(45) | nullable |
| send_count | tinyInteger | default 0 |
| windows_starts_at | timestamptz | not null |
| blocked_until | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

Индекс: `(ip_address)` — `idx_rate_limit_ip`.

### personal_access_tokens
API-токены (Sanctum-подобная схема) для полиморфных tokenable-моделей.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| tokenable_type / tokenable_id | uuidMorphs | полиморфная связь |
| name | string | not null |
| token | string(64) | unique, хэш токена |
| abilities | text | nullable |
| last_used_at / expires_at | timestamptz | nullable, expires_at — index |
| created_at / updated_at | timestamptz | |

### password_reset_tokens
Токены сброса пароля (создаётся только если таблицы ещё нет).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| email | string | unique, not null |
| token | string | not null |
| created_at | timestamptz | nullable |

### languages
Справочник языков. Сиды: `en, ru, es, de, fr`.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| code | string(5) | unique (`en`, `ru`, …) |
| name / native_name | string | not null (`English` / `English`) |
| direction | string(3) | default `ltr` |
| is_active | boolean | default true |
| sort | smallInteger | default 500 |
| created_at / updated_at | timestamptz | |

### categories
Тематические и грамматические категории (дерево через `parent_id`). Отображаемые имена — НЕ колонка, а записи в полиморфной `translations` (`field = name`, см. сидер категорий).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| parent_id | uuid | nullable, self-FK, null on delete |
| user_id | uuid | nullable (пользовательские категории; зарезервировано) |
| slug | string | not null |
| is_system | boolean | default false |
| type | string | default `theme` (`theme` / `grammar`) |
| color | string(15) | nullable |
| icon | string | nullable |
| sort | smallInteger | default 500 |
| created_at / updated_at | timestamptz | |

Уникальность: `(slug, user_id)` — `unique_slug_user`. Индекс: `(type, sort)`.

### entries
Языконезависимые концепты слов. Один entry — одно «слово-понятие», все языковые формы — в `entry_translations`.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| image_path | string | nullable, обложка слова |
| level | string(2) | default `A1` (CEFR) |
| frequency_rank | unsignedInteger | nullable, ранг из частотных словарей |
| created_at / updated_at | timestamptz | |

Индекс: `(level, frequency_rank)`.

### entry_meanings
Смыслы (значения) концепта. Ключевая таблица полисемии: «мир» → meaning «peace» и meaning «world».

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| entry_id | uuid | FK → entries.id, cascade delete |
| note | string | nullable, глосса смысла |
| created_at / updated_at | timestamptz | |

### entry_translations
Переводы концепта, сгруппированные по смыслу. Несколько переводов на один язык — нормально (разные/соседние строки `meaning_id`).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| entry_id | uuid | FK → entries.id, cascade delete |
| meaning_id | uuid | FK → entry_meanings.id, cascade delete |
| language_id | uuid | FK → languages.id, cascade delete |
| text | string | not null |
| transcription | string | nullable |
| audio_path | string | nullable |
| part_of_speech | string | nullable (`verb`, `noun`, …) |
| created_at / updated_at | timestamptz | |

Уникальность: `(meaning_id, language_id, text)` — именно она разрешает «мир»→peace и «мир»→world на английском.
Индекс: `(language_id, text)` — поиск.

### word_forms
Формы слова, привязаны к конкретному переводу (`ran`, `peaceful`, `worlds`).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| entry_translation_id | uuid | FK → entry_translations.id, cascade delete |
| form | string | not null |
| form_type | string | not null (`plural noun`, `adjective`, …) |
| created_at / updated_at | timestamptz | |

Индекс: `(entry_translation_id)`.

### entry_category
Пивот концепт ↔ категории (один концепт — в нескольких).

| Поле | Тип | Ограничения |
|---|---|---|
| entry_id | uuid | FK → entries.id, cascade delete |
| category_id | uuid | FK → categories.id, cascade delete |

Составной PK: `(entry_id, category_id)`.

### phrases
Языконезависимые фразы/примеры (аналог entries для фраз).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| level | string(2) | default `A1` |
| phrase_type | string | default `phrase` (`phrase` / `example` / `idiom` / …) |
| image_path | string | nullable |
| created_at / updated_at | timestamptz | |

Индекс: `(phrase_type, level)`.

### phrase_translations
Переводы фраз (пока один перевод на язык — unique; при нужде снять по образцу entry_translations).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| phrase_id | uuid | FK → phrases.id, cascade delete |
| language_id | uuid | FK → languages.id, cascade delete |
| text | text | not null |
| transcription | text | nullable |
| audio_path | string | nullable |
| created_at / updated_at | timestamptz | |

Уникальность: `(phrase_id, language_id)`.

### phrases_categories
Пивот фраза ↔ категории. Составной PK `(phrase_id, category_id)`.

### entry_phrase
Примеры употребления слова: привязка фраз к entry, опционально к конкретному смыслу.

| Поле | Тип | Ограничения |
|---|---|---|
| entry_id | uuid | FK → entries.id, cascade delete |
| phrase_id | uuid | FK → phrases.id, cascade delete |
| meaning_id | uuid | nullable, FK → entry_meanings.id, null on delete |

Составной PK: `(entry_id, phrase_id)`. Индекс: `(meaning_id)`.

### entry_media
Картинки/аудио/видео слов. Привязка «слово звучало в фильме/песне» — через `source` + `content_source_id`.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| entry_id | uuid | nullable, FK → entries.id, cascade delete |
| entry_translation_id | uuid | nullable, FK → entry_translations.id, cascade delete |
| type | string(10) | `image` / `audio` / `video` |
| path | string | путь в storage или внешний URL |
| source | string(10) | default `own` (`own` / `movie` / `music`) |
| source_title | string | nullable, название фильма/песни |
| content_source_id | uuid | nullable, FK → content_sources.id, null on delete |
| sort | smallInteger | default 500 |
| created_at / updated_at | timestamptz | |

Индексы: `(entry_id)`, `(entry_translation_id)`.
> Хотя бы один из `entry_id` / `entry_translation_id` обязан быть заполнен — контролируется на уровне приложения (UseCase), не CHECK-ограничением.

### content_sources
Источники контента: фильмы, песни, тексты, где встречаются слова.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| type | string(10) | `movie` / `music` / `text` |
| title | string | not null |
| language_id | uuid | nullable, FK → languages.id, null on delete |
| level | string(2) | nullable (CEFR) |
| external_id | string | nullable, ссылка на внешний каталог |
| created_at / updated_at | timestamptz | |

Индекс: `(type, language_id)`.

### translations
Полиморфные переводы полей сущностей (имена категорий и т.п.).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| entity_type | string | класс сущности |
| entity_id | uuid | id сущности |
| locale | string | локаль (`ru`, `en`) |
| field | string | имя поля (`name`) |
| value | string | переведённое значение |
| created_at / updated_at | timestamptz | |
| deleted_at | timestamptz | soft delete |

Индекс: `(locale, field, value)`.

### user_language_profiles
Языковые профили пользователя: один профиль = один изучаемый язык. Уровень и цель — здесь, а не в `user_profiles`.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users.id, cascade delete |
| target_language_id | uuid | FK → languages.id, cascade delete |
| native_language_id | uuid | FK → languages.id, cascade delete |
| level | string(2) | default `A2` (CEFR) |
| daily_goal | unsignedInteger | default 10 (слов в день) |
| is_active | boolean | default true |
| created_at / updated_at | timestamptz | |

Уникальность: `(user_id, target_language_id)`.

### user_language_stats
Агрегированная статистика по языковому профилю (1:1).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| profile_id | uuid | FK → user_language_profiles.id, cascade delete, unique |
| words_learned | unsignedInteger | default 0 |
| streak_days / best_streak | unsignedInteger | default 0 |
| xp | unsignedBigInteger | default 0 |
| accuracy | float | default 0 (доля верных ответов 0..1) |
| last_activity_at | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

### user_progresses
SM-2 прогресс повторений (что учить и когда). Полиморфная цель: entry/phrase/user_entry/user_phrase.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users.id, cascade delete |
| profile_id | uuid | FK → user_language_profiles.id, cascade delete |
| learnable_type | string | `entry` / `phrase` / `user_entry` / `user_phrase` |
| learnable_id | uuid | id цели (без FK — полиморф) |
| easiness_factor | float | default 2.5 (SM-2, минимум 1.3) |
| interval_days | unsignedInteger | default 1 |
| repetition | unsignedInteger | default 0 |
| quality_last | unsignedSmallInteger | default 0 (последняя оценка 0..5) |
| next_review_at | timestamptz | nullable |
| last_reviewed_at | timestamptz | nullable |
| created_at / updated_at | timestamptz | |

Уникальность: `(profile_id, learnable_type, learnable_id)`.
Индексы: `(profile_id, next_review_at)` — `user_progress_review_idx` (очередь повторений); `(learnable_type, learnable_id)` — `user_progress_learnable_idx`.

### user_streaks
Дневные стрики: глобальные (`profile_id = null`) и по языкам.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users.id, cascade delete |
| profile_id | uuid | nullable, FK → user_language_profiles.id, null on delete; null = общий стрик |
| date | datetimeTz | день (хранится начало дня) |
| words_reviewed / words_new | unsignedInteger | default 0 |
| created_at / updated_at | timestamptz | |

Уникальность: `(user_id, profile_id, date)`.
> Внимание: в NULL-семантике unique не блокирует дубли глобальных стриков на СУБД-уровне — единственность дня обеспечивает `SubmitReviewUseCase` (find-then-save). При переезде на Postgres рассмотреть partial unique-индексы.

### user_entries
Свои слова пользователя (личная библиотека, в отличие от системного каталога).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users.id, cascade delete |
| category_id | uuid | nullable, FK → categories.id, null on delete |
| image_path | string | nullable |
| created_at / updated_at | timestamptz | |

### user_entry translations
Переводы своих слов (допускается несколько переводов на язык — unique только пара).

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_entry_id | uuid | FK → user_entries.id, cascade delete |
| language_id | uuid | FK → languages.id, cascade delete |
| text | string | not null |
| transcription | string | nullable |
| part_of_speech | string | nullable |
| notes | text | nullable |
| created_at / updated_at | timestamptz | |

Уникальность: `(user_entry_id, language_id)`.

### user_phrases
Свои фразы пользователя.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK → users.id, cascade delete |
| category_id | uuid | nullable, FK → categories.id, null on delete |
| phrase_type | string | default `phrase` |
| created_at / updated_at | timestamptz | |

### user_phrase_translations
Переводы своих фраз.

| Поле | Тип | Ограничения |
|---|---|---|
| id | uuid | PK |
| user_phrase_id | uuid | FK → user_phrases.id, cascade delete |
| language_id | uuid | FK → languages.id, cascade delete |
| text | text | not null |
| transcription | text | nullable |
| notes | text | nullable |
| created_at / updated_at | timestamptz | |

Уникальность: `(user_phrase_id, language_id)`.

### sessions / cache / jobs
Стандартные Laravel-таблицы, без предметной специфики.

## Связи

- `users.id` → `user_profiles.user_id` (one-to-one, PK)
- `users.id` → `user_language_profiles.user_id` (one-to-many)
- `users.id` → `user_progresses.user_id`, `user_streaks.user_id` (one-to-many)
- `users.id` → `user_entries.user_id`, `user_phrases.user_id` (one-to-many)
- `users.id` → `auth_identities.user_id`, `auth_contacts.user_id` (one-to-many, логически)
- `users.id` → `personal_access_tokens.tokenable_id` (полиморфная)
- `user_language_profiles.id` → `user_language_stats.profile_id` (one-to-one)
- `user_language_profiles.id` → `user_progresses.profile_id` (one-to-many)
- `user_language_profiles.id` → `user_streaks.profile_id` (one-to-many, nullable)
- `languages.id` → `*_translations.language_id`, `*_profiles.target/native_language_id`, `content_sources.language_id`
- `entries.id` → `entry_meanings.entry_id` → `entry_translations.meaning_id` (цепочка смысл→переводы)
- `entries.id` ↔ `categories.id` через `entry_category` (many-to-many)
- `entries.id` ↔ `phrases.id` через `entry_phrase` (many-to-many, опционально `meaning_id`)
- `entries.id` / `entry_translations.id` → `entry_media` (one-to-many)
- `content_sources.id` → `entry_media.content_source_id` (one-to-many, nullable)
- `phrases.id` ↔ `categories.id` через `phrases_categories` (many-to-many)
- `translations` и `user_profiles.avatar` — самостоятельные/ссылки без FK: `translations(entity_type, entity_id)` полиморфно

## Ограничения (constraints)

- `entry_translations(meaning_id, language_id, text)` — unique: разрешает несколько переводов на язык, запрещает полные дубли.
- `user_progresses(profile_id, learnable_type, learnable_id)` — unique: одна SM-2 строка на цель в профиле.
- `user_language_profiles(user_id, target_language_id)` — unique: один профиль на язык.
- `user_streaks(user_id, profile_id, date)` — unique с оговоркой про NULL (см. выше).
- `entries.level`, `user_language_profiles.level`, `content_sources.level` — CEFR `A1..C2`, валидация в Request (`size:2`), не enum в БД.
- `user_progresses.learnable_type` — `entry`/`phrase`/`user_entry`/`user_phrase`, валидация `in:` в `SubmitReviewRequest`.
- `user_profiles.tags` — json-массив строк, max 6, валидация в `UpsertUserProfileRequest`.
- Cascade delete у большинства FK — удаление пользователя/концепта чистит связанные строки; `entry_media.content_source_id` и пивоты к категориям — null on delete / cascade соответственно.
- Системный каталог (`entries`, `phrases`, `categories` с `is_system`) приложением не удаляется через пользовательские действия — удаление своих слов идёт только через `user_entries`/`user_phrases`.

## Миграции

- Инструмент: Laravel Migrations (`php artisan migrate`, проверка в `q_php`: `migrate:fresh --seed`, `rollback`, `migrate`)
- Правило: любое изменение схемы — только через новый файл миграции (старые правились лишь на dev-этапе до релиза), никогда через ручной ALTER
- Расположение: `app/<Module>/Infrastructure/Persistence/Database/Migrations/`; общая `translations` — `app/Shared/Laravel/Infrastructure/Persistence/Database/Migrations/`
- Порядок файлов топологический: `users` (04_04) → `languages` → `categories` → языковые профили → `learning`/`library` → `entries` → `phrases` → `entry_media`/`content_sources` → `translations`

## Правила чтения и записи

- Чтение — через repository в infrastructure-слое (Eloquent/query-builder), возврат Domain-сущностей
- Запись — только через repository-методы (`save`, `updateOrCreate`), не сырыми запросами в бизнес-логике; сложные сохранения (слово + переводы) — в транзакции внутри репозитория
- SM-2 математика — в `SubmitReviewUseCase`, не в репозитории
- Импорт словарей — только через `catalog:import-words` (идемпотентен по unique-ключам), не через HTTP-роуты
- Массовые операции (переимпорт каталога) — с явного подтверждения пользователя
