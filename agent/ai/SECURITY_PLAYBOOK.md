# Security Playbook: Laravel 13 DDD / qwicki

> Версия 2.0 · Backend: Laravel 13, DDD-архитектура (модули Auth/User/Catalog/Learning/Library) · Инфраструктура: Docker, SQLite (dev), CI
> Назначение: единый источник правды по безопасности для команды, использующей AI-ассистированную разработку («вайбкодинг»). Каждый пункт содержит: риск → как проверить → уязвимый код → безопасный код → автоматизация.
>
> Область применения: разделы про PostgreSQL/Redis/Kafka/Soketi/Horizon относятся к будущей прод-инфраструктуре — в qwicki backend их сейчас нет (sqlite, sync/array-драйверы). Читать как ориентир при переезде, чек-лист после сессий — по CHECK_SECURITY.md.

---

## Как пользоваться этим документом

1. **После каждого сеанса вайбкодинга** — прогоняете раздел [0. Чек-лист после вайбкодинга](#0-чек-лист-после-каждого-сеанса-вайбкодинга) целиком, без пропусков.
2. **Перед каждым PR/релизом** — раздел [Release Checklist](#release-checklist).
3. **Перед деплоем в prod** — раздел [Deploy Checklist](#deploy-checklist).
4. Все остальные разделы — справочник, на который чек-листы ссылаются, и обучающий материал для ревью кода, сгенерированного AI.
5. Каждый пункт снабжён командой для CI — стремитесь к тому, чтобы этот playbook был не «документом на полке», а набором `composer`/`make`-команд, которые физически нельзя проигнорировать (git hooks, CI gate).

**Важная оговорка:** это инженерный playbook, а не юридическое заключение. Раздел про медицинские данные описывает технические меры (шифрование, аудит, контроль доступа), а не даёт правовой оценки соответствия конкретному законодательству (HIPAA, GDPR, 152-ФЗ и т.д.) — для этого нужен профильный юрист/DPO.

---

## Оглавление

0. [Чек-лист после каждого сеанса вайбкодинга](#0-чек-лист-после-каждого-сеанса-вайбкодинга)
1. OWASP Top 10 (2021) применительно к Laravel
2. OWASP API Security Top 10 (2023)
3. OWASP ASVS, адаптированный под Laravel/DDD
4. Laravel 13 Security Best Practices
5. DDD-архитектура и границы безопасности
6. `.env`, `APP_KEY`, `APP_DEBUG`, логирование
7. Аутентификация: Sanctum / OAuth2 (Passport) / JWT
8. Авторизация: Policies, Gates, IDOR
9. Mass Assignment
10. Race Conditions
11. SSRF
12. XXE
13. RCE / Command & Code Injection
14. Path Traversal / LFI
15. Загрузка файлов (MIME, сигнатуры, SVG, PDF, EXIF)
16. Security Headers
17. CSP
18. CORS
19. Docker Security
20. PostgreSQL Security
21. Redis Security
22. Kafka Security
23. Soketi / WebSocket Security
24. Queue Security (Horizon)
25. Защита персональных данных (PII)
26. Автоматизация: команды для CI/pre-commit
27. Release Checklist
28. Deploy Checklist
29. Приложения (шаблоны конфигов)

---

## 0. Чек-лист после каждого сеанса вайбкодинга

> Цель: AI-агент (Claude/Copilot/Cursor и т.п.) мог за один сеанс внести уязвимость, о которой не думал ни он, ни вы — она "утекла" из общего контекста задачи. Этот чек-лист — обязательный ритуал, выполняется **каждый раз**, когда AI сгенерировал/изменил код, перед коммитом.

### 0.1 Быстрый gate (обязателен всегда, 5–10 минут)

- [ ] `git diff` прочитан **построчно**, целиком, человеком (не «выглядит нормально» — реально прочитан).
- [ ] Не появилось новых `env('...')` вызовов вне `config/*.php` (Laravel: `env()` должен использоваться только в конфиге, не в коде приложения).
- [ ] Не добавлено `DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw` с конкатенацией пользовательского ввода.
- [ ] Не добавлено `Model::unguard()`, `$fillable = ['*']`, `$guarded = []` без явного обоснования.
- [ ] Не добавлено `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `eval`, `assert(string)`, `create_function`, `unserialize()` на недоверенных данных.
- [ ] Не добавлено `file_get_contents($userUrl)`, `Http::get($userUrl)`, `curl` по URL из пользовательского ввода без allow-list (SSRF).
- [ ] Не добавлено новых публичных роутов без `middleware(['auth', ...])` и `Policy`/`Gate` там, где нужен контроль владения ресурсом.
- [ ] Не изменены `config/cors.php`, `config/sanctum.php`, `.env`, `Dockerfile`, `docker-compose*.yml`, `nginx/*.conf` без осознанного ревью (эти файлы — «горячая зона»: одна строчка ломает всю модель безопасности).
- [ ] Все новые формы/эндпоинты с приёмом данных имеют `FormRequest` с явными правилами валидации (не «доверяем модели»).
- [ ] Все новые эндпоинты загрузки файлов проверены по разделу 15 (MIME + сигнатура + re-encode, не только расширение).
- [ ] `composer audit` и `npm audit --audit-level=high` — 0 новых high/critical.
- [ ] Larastan/PHPStan (`vendor/bin/phpstan analyse`) — 0 новых ошибок.
- [ ] Тесты прошли, включая security-related feature-тесты (auth, policies, mass assignment).
- [ ] AI не оставил закомментированный «временный» код, отключающий проверки (`// $this->authorize(...)`, `// TODO remove before prod`, захардкоженные тестовые токены/пароли).
- [ ] Не появилось новых секретов в коде (grep по `password`, `secret`, `api_key`, `token` с литеральными значениями) — прогнать `gitleaks` / `trufflehog`.

### 0.2 Расширенный gate (для эндпоинтов, работающих с данными пациентов / PHI)

- [ ] Поле, содержащее медицинские данные, проходит через `casts` с шифрованием (`encrypted`, `encrypted:array`) либо явно задокументировано, почему нет.
- [ ] Доступ к записи проверяется через Policy, использующую владение/назначение (врач↔пациент), а не только `auth()->check()`.
- [ ] Действие с PHI пишет запись в audit log (кто, когда, что, IP) — раздел 25.
- [ ] Ответ API не «утекает» лишние поля пациента (нет `Model::all()` без `Resource`, нет `toArray()` в JSON-ответе без явного списка полей).
- [ ] Нет PHI в логах (`Log::info`, `dd()`, `dump()`, exception-трейсах, отправляемых в Sentry/Bugsnag без скраббинга).
- [ ] Нет PHI в query string (только в теле запроса/заголовках), т.к. query string оседает в access-логах прокси.

### 0.3 Ручное чтение diff — на что смотреть глазами

AI особенно часто ошибается в этих местах — проверяйте их вручную, даже если автоматика зелёная:

| Паттерн в diff | Что проверить |
|---|---|
| Новый `Route::` | middleware, rate limiting, авторизация владения |
| Новый `$request->all()` / `$request->input()` | используется ли `validated()` из FormRequest вместо сырого input |
| Новый `->create($request->...)` / `fill()` | нет ли передачи всего массива без whitelisting |
| Новая внешняя интеграция (`Http::`, SDK) | таймауты, allow-list хостов, обработка ошибок без утечки stack trace |
| Новый `Storage::` | публичный ли диск, нет ли пользовательского пути в `Storage::put($userPath, ...)` |
| Новый `Blade` с `{!! !!}` | точно ли нужен небезопасный вывод, откуда данные |
| Новая миграция с `text`/`json` полем | не нужно ли шифрование (PHI) |
| Новый `Cache::remember` с ключом из ввода | нет ли межпользовательской утечки кэша по предсказуемому ключу |
| Новый job / listener | идемпотентность, нет ли гонки (race condition), нет ли PHI в payload очереди в открытом виде |

### 0.4 Если хоть один пункт не выполнен

Коммит не делается. Это не рекомендация, а hard gate — используйте pre-commit hook (см. раздел 26), который физически блокирует коммит при провале `composer audit`, `phpstan`, `gitleaks`.

## 1. OWASP Top 10 (2021) применительно к Laravel

### A01:2021 — Broken Access Control

**Риск:** пользователь получает доступ к чужим данным/действиям (см. также IDOR, раздел 8).

Уязвимо:
```php
// Контроллер — любой авторизованный пользователь читает любую карту пациента по id
Route::get('/patients/{id}', function ($id) {
    return Patient::findOrFail($id);
})->middleware('auth:sanctum');
```

Безопасно:
```php
Route::get('/patients/{patient}', [PatientController::class, 'show'])
    ->middleware('auth:sanctum');

// PatientController
public function show(Patient $patient): PatientResource
{
    $this->authorize('view', $patient); // PatientPolicy проверяет владение/назначение
    return new PatientResource($patient);
}
```

Проверка: каждый роут с `{id}`/`{model}` — есть ли `authorize()`/Policy; тест «пользователь A не может получить ресурс пользователя B» (403/404).

### A02:2021 — Cryptographic Failures

- Медицинские данные (диагнозы, документы) — только через Laravel `encrypted` casts или отдельный сервис шифрования уровня приложения, не полагаться только на шифрование диска БД.
- Проверить: нет ли `md5()/sha1()` для паролей (только `Hash::make`, bcrypt/argon2id), TLS enforced (`APP_FORCE_HTTPS`, `URL::forceScheme('https')`, HSTS в разделе 16).

```php
// Модель Patient — шифрование PHI-полей на уровне приложения
protected $casts = [
    'diagnosis_notes' => 'encrypted',
    'medical_history' => 'encrypted:array',
];
```

### A03:2021 — Injection

SQL/NoSQL/Command/LDAP-инъекции. Laravel Query Builder/Eloquent параметризует запросы автоматически — опасность там, где это обходят.

Уязвимо:
```php
$patients = DB::select("SELECT * FROM patients WHERE last_name = '{$request->input('name')}'");
```

Безопасно:
```php
$patients = DB::select('SELECT * FROM patients WHERE last_name = ?', [$request->string('name')]);
// или предпочтительно — Eloquent
$patients = Patient::where('last_name', $request->string('name'))->get();
```

Проверка: `grep -rn "DB::raw\|whereRaw\|selectRaw\|orderByRaw\|statement(" app/` — каждое вхождение вручную ревьюится на конкатенацию пользовательского ввода.

### A04:2021 — Insecure Design

- Для DDD: бизнес-инварианты (например, «нельзя выписать рецепт без активного визита») должны проверяться в Domain-слое (Aggregate/Entity), а не только в контроллере — иначе AI, сгенерировавший новый контроллер, обойдёт правило.
- Threat modeling для новых фич с PHI — обязателен пункт «кто не должен видеть эти данные» до реализации.

### A05:2021 — Security Misconfiguration

См. разделы 6 (`.env`), 16 (Headers), 19 (Docker). Частые ошибки AI-генерации: `APP_DEBUG=true` в примерах, `'*'` в CORS, дефолтные пароли в docker-compose.

### A06:2021 — Vulnerable and Outdated Components

```bash
composer audit
npm audit --audit-level=high
composer show -o   # outdated packages
```
Встроить в CI как gate (раздел 26). Dependabot/Renovate для автоматических PR на патч-версии.

### A07:2021 — Identification and Authentication Failures

- Rate limiting на login/2FA/password-reset (`throttle` middleware), учёт по IP+login.
- Session fixation: `Auth::login()` должен сопровождаться `$request->session()->regenerate()`.
- Для медицинского проекта — рассмотреть обязательную 2FA для ролей врач/админ (`laravel/fortify` + TOTP).

```php
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 попыток в минуту
```

### A08:2021 — Software and Data Integrity Failures

- `composer.lock`/`package-lock.json` коммитятся всегда, `--ignore-platform-reqs` в CI запрещён.
- Проверка подписи Docker-образов (cosign) при желании продвинутого supply-chain security.
- Не десериализовать недоверенные данные (`unserialize()` → использовать `json_decode`).

### A09:2021 — Security Logging and Monitoring Failures

- Логировать: неудачные авторизации, изменение ролей, доступ к PHI, экспорт данных, изменение критичных настроек.
- Не логировать: пароли, токены, полные номера документов, PHI в открытом виде (раздел 25).
- Алертинг на аномалии (много 403/401 подряд с одного IP/аккаунта) — например через Laravel + внешний SIEM/Sentry alert rules.

### A10:2021 — Server-Side Request Forgery (SSRF)

См. раздел 11 отдельно — актуально для медицинских интеграций (запросы к внешним EHR/лабораториям по URL, который может контролировать пользователь).

---

## 2. OWASP API Security Top 10 (2023) применительно к Laravel API

| # | Категория | Laravel-специфика | Проверка |
|---|---|---|---|
| API1 | Broken Object Level Authorization | Route Model Binding без Policy | тест «чужой id → 403» на каждый эндпоинт |
| API2 | Broken Authentication | Sanctum токены без expiration, слабые guard-конфиги | `config/sanctum.php` — `expiration` задан, refresh-логика проверена |
| API3 | Broken Object Property Level Authorization | `Resource` отдаёт лишние поля (напр. `is_admin`, внутренние ID) | ревью каждого `JsonResource::toArray()` |
| API4 | Unrestricted Resource Consumption | Нет rate limiting, нет лимита на размер выгрузки/пагинации | `throttle` middleware на всех API-роутах, `paginate()` вместо `get()` на больших коллекциях |
| API5 | Broken Function Level Authorization | Админ-эндпоинты защищены только фронтенд-скрытием | `middleware(['auth', 'role:admin'])` на бэкенде обязательно |
| API6 | Unrestricted Access to Sensitive Business Flows | Нет капчи/лимита на бизнес-критичные операции (регистрация, запись на приём) | rate limit + anti-automation на такие эндпоинты |
| API7 | Server Side Request Forgery | См. раздел 11 | allow-list хостов для исходящих запросов |
| API8 | Security Misconfiguration | CORS `*`, verbose ошибки в проде | разделы 6, 18 |
| API9 | Improper Inventory Management | «Забытые» dev/debug роуты (`/telescope`, `/horizon` без auth) в проде | `php artisan route:list` в CI diff против allow-list |
| API10 | Unsafe Consumption of APIs | Доверие ответам внешних API (лаборатории, СМЭВ и т.п.) без валидации схемы | валидировать внешние ответы через `FormRequest`-подобные DTO/Data классы |

Пример API3 (утечка лишних полей):
```php
// Уязвимо — сериализует модель целиком
return response()->json($patient);

// Безопасно — явный whitelist
class PatientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth,
            // намеренно не включаем: internal_notes, insurance_raw_payload и т.п.
        ];
    }
}
```

Пример API9 — забытые dev-панели:
```php
// routes/web.php — забыто AI после отладки
Route::get('/debug-info', fn () => phpinfo()); // КРИТИЧНО: убрать

// config/telescope.php / horizon
'middleware' => ['web', 'auth', 'can:viewTelescope'], // не оставлять открытым в проде
```
## 3. OWASP ASVS адаптированный под Laravel/DDD

Ниже — сокращённая проекция ASVS 4.x на стек Laravel 13 + DDD. Для медицинского проекта рекомендуется целиться в **Level 2**, для самых чувствительных потоков (доступ к PHI, аутентификация, платежи если есть) — **Level 3**.

### V1 — Архитектура

- [ ] Domain-слой не знает о HTTP/Eloquent напрямую (интерфейсы репозиториев в Domain, реализация в Infrastructure) — это же и security-граница: бизнес-правила нельзя обойти через «короткий путь» из контроллера.
- [ ] Единая точка входа для авторизации — Policies/Gates, не разбросанные `if ($user->role === 'admin')` по контроллерам.
- [ ] Явная Threat Model для bounded context «Пациенты/PHI» — задокументирована отдельно.

### V2 — Аутентификация

- [ ] Пароли — `Hash::make` (bcrypt/argon2id), минимум 12 символов для медицинского персонала.
- [ ] Блокировка после N неудачных попыток (`throttle`, либо кастомный lockout по аккаунту).
- [ ] MFA для ролей с доступом к PHI (Fortify TOTP или WebAuthn).
- [ ] Сессии/токены имеют срок жизни, ревокацию (`tokenable->tokens()->delete()` при смене пароля/логауте на всех устройствах).

### V3 — Управление сессией

- [ ] `session.secure = true`, `session.http_only = true`, `session.same_site = 'lax'`/`'strict'` в `config/session.php`.
- [ ] `$request->session()->regenerate()` после логина; `invalidate()` после логаута.
- [ ] Sanctum SPA: `stateful` домены строго перечислены, не wildcard.

### V4 — Контроль доступа

- [ ] Каждый Eloquent-модель с PHI имеет Policy.
- [ ] Проверка на уровне Domain Service (не только Controller) для критичных операций — двойная защита.
- [ ] Deny-by-default: новый роут без явного middleware не проходит code review.

### V5 — Валидация, кодирование, санитизация ввода

- [ ] Все входные точки — через `FormRequest` с `rules()`.
- [ ] Вывод в Blade — только `{{ }}`, не `{!! !!}`, кроме отревьюженных случаев с явной санитизацией (HTMLPurifier).
- [ ] Файлы — раздел 15.

### V7 — Обработка ошибок и логирование

- [ ] `APP_DEBUG=false` в проде (раздел 6).
- [ ] Кастомные страницы ошибок не раскрывают stack trace/SQL/пути.
- [ ] Логи структурированы (JSON), без PHI (раздел 25), отправляются в централизованное хранилище с ограниченным доступом.

### V8 — Защита данных

- [ ] PHI — encrypted casts / отдельное шифрование по полям, ключи ротируемы, `APP_KEY` не совпадает между окружениями.
- [ ] Данные в S3/MinIO — приватный bucket по умолчанию, presigned URL с коротким TTL для доступа к медицинским документам.
- [ ] Бэкапы БД — тоже зашифрованы, доступ ограничен.

### V9 — Коммуникация

- [ ] TLS 1.2+ везде, HSTS (раздел 16).
- [ ] Внутренний трафик (app↔postgres, app↔redis, app↔kafka) — либо изолированная docker-сеть без внешнего доступа, либо TLS/mTLS при выходе за пределы одного хоста.

### V10 — Вредоносный код

- [ ] `composer audit`, `npm audit`, статический анализ на CI (раздел 26); ревью новых зависимостей вручную (кто автор, сколько загрузок, дата последнего обновления).

### V11 — Бизнес-логика

- [ ] Race conditions на критичных операциях (запись на приём, списание квоты) — раздел 10.
- [ ] Rate limiting на бизнес-потоки, подверженные злоупотреблению (массовая запись, бронирование слотов).

### V12 — Файлы и ресурсы

- [ ] Раздел 15 полностью; загруженные файлы никогда не выполняются как код (отдельный домен для статики, `X-Content-Type-Options: nosniff`).

### V13 — API и веб-сервисы

- [ ] Раздел 2 (API Top 10) полностью применяется.

### V14 — Конфигурация

- [ ] Раздел 6, 19; секреты не в git, читаются из vault/секрет-менеджера в проде, а не только `.env`-файла на диске контейнера.

---

## 4. Laravel 13 Security Best Practices (сводно)

```php
// config/app.php — прод
'debug' => (bool) env('APP_DEBUG', false), // ensure default false, не true

// bootstrap/app.php (Laravel 11+/13 стиль) — middleware глобально
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*'); // явно указать реальные прокси в проде, не '*'
    $middleware->throttleApi(); // rate limit по умолчанию для api
})
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->dontReport([BusinessRuleException::class]);
    // не раскрывать внутренние исключения пользователю
})
```

Ключевые практики:
- Всегда `php artisan config:cache` в проде (и пересобирать при каждом деплое — иначе `.env`, изменённый вручную на сервере, «не подхватится» и введёт в заблуждение).
- `php artisan route:cache`, `view:cache` — плюс к производительности снижает риск случайных debug-роутов, забытых включёнными.
- Модели: `$guarded = ['id']` минимум, лучше явный `$fillable`.
- Никогда не логировать `$request->all()` целиком в проде (может содержать пароли/PHI) — только явно выбранные нечувствительные поля.
- `php artisan down --secret=...` вместо публичного maintenance-режима при обслуживании с чувствительными данными.

## 5. DDD-архитектура и границы безопасности

Рекомендуемая укладка security-ответственности по слоям:

```
app/
├── Domain/
│   ├── Patient/
│   │   ├── Entity/Patient.php          # бизнес-инварианты, НЕ знает про HTTP/Eloquent
│   │   ├── Repository/PatientRepositoryInterface.php
│   │   └── Exception/PatientAccessDeniedException.php
├── Application/
│   ├── Patient/
│   │   ├── UseCase/ViewPatientUseCase.php   # оркестрация + вызов авторизации
│   │   └── DTO/PatientDTO.php               # явный whitelist полей на входе/выходе
├── Infrastructure/
│   ├── Persistence/Eloquent/PatientModel.php
│   ├── Persistence/Eloquent/EloquentPatientRepository.php
│   └── Http/Controllers/PatientController.php  # тонкий, только HTTP↔UseCase
```

Правила:
- **Авторизация (кто может)** — на границе Application-слоя (UseCase проверяет Policy/permission перед вызовом Domain-логики), не только в контроллере — иначе, если появится второй вход в UseCase (CLI-команда, очередь, другой контроллер), проверка не потеряется.
- **Валидация формата (что пришло)** — на границе HTTP (`FormRequest`), в DTO Application-слоя — уже нормализованные, доверенные данные.
- **Бизнес-инварианты (можно ли так)** — в Domain-слое (Entity/Aggregate/Domain Service), независимо от того, кто вызвал — контроллер, job, artisan-команда.
- Такое разделение особенно важно при вайбкодинге: AI генерирует новый «вход» (например, artisan-команду импорта пациентов) и **обязан** пройти через тот же UseCase/Domain, а не писать в БД напрямую — тогда авторизация и инварианты не обходятся случайно.
## 6. `.env`, `APP_KEY`, `APP_DEBUG`, логирование

### Чек-лист

- [ ] `.env` в `.gitignore`, нет ни одной версии `.env` в истории git (`git log --all --full-history -- .env`).
- [ ] `.env.example` содержит все ключи **без реальных значений** (пустые/placeholder), чтобы AI-агент видел структуру, но не секреты.
- [ ] `APP_KEY` — уникальный per-окружение (`php artisan key:generate`), не переиспользуется между dev/stage/prod, хранится в секрет-менеджере (Vault/SSM/Doppler), не в образе Docker.
- [ ] `APP_DEBUG=false` в stage/prod — проверка в CI: деплой блокируется, если в `.env`-шаблоне для прод-окружения `APP_DEBUG` не `false`.
- [ ] `APP_ENV=production` в проде (влияет на дефолты множества пакетов, включая Sanctum/Sentry).
- [ ] `LOG_CHANNEL` не `single` в проде (ротация обязательна: `daily` или отправка в стек агрегации), доступ к лог-файлам ограничен.
- [ ] В логах нет PHI/паролей/токенов — grep по продовым логам на предмет `password`, `token`, номеров документов; настроен log scrubbing (кастомный `Monolog\Processor`).

Уязвимо:
```php
// AI сгенерировал debug-логирование "для диагностики" и забыл убрать
Log::info('Incoming request', $request->all()); // может содержать пароль, PHI, токены
```

Безопасно:
```php
Log::info('Patient record accessed', [
    'patient_id' => $patient->id,
    'user_id' => $request->user()->id,
    'ip' => $request->ip(),
    // намеренно НЕ логируем содержимое медицинских полей
]);
```

Проверка секретов в git:
```bash
gitleaks detect --source . --verbose
trufflehog filesystem . --only-verified
```

---

## 7. Аутентификация: Sanctum / OAuth2 (Passport) / JWT

### Sanctum (SPA / мобильный клиент, первая рекомендация Laravel)

```php
// config/sanctum.php
'expiration' => 60 * 24, // токены не «вечные» — в минутах, задать разумный TTL
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')), // явный список, не '*'
```

Уязвимо:
```php
// Токен создаётся без ability/expiration, живёт вечно
$token = $user->createToken('api')->plainTextToken;
```

Безопасно:
```php
$token = $user->createToken(
    name: 'mobile-app',
    abilities: ['patient:read'], // ограниченный scope
    expiresAt: now()->addHours(8),
)->plainTextToken;

// Отзыв всех токенов при смене пароля
$user->tokens()->delete();
```

### OAuth2 (Laravel Passport) — если нужны сторонние клиенты/делегированный доступ

- [ ] `redirect_uri` клиентов — точное совпадение (без wildcard-паттернов), иначе open redirect/token leak.
- [ ] PKCE обязателен для публичных клиентов (мобильные/SPA).
- [ ] Scope-based доступ — не выдавать `*`-scope по умолчанию.

### JWT (если используется вместо/поверх Sanctum, напр. для межсервисной коммуникации)

- [ ] Алгоритм — `RS256`/`ES256` (асимметричный), не `HS256` с общим секретом между сервисами, которым не обязательно доверять на запись.
- [ ] Обязательная проверка `exp`, `iss`, `aud`.
- [ ] **Никогда** не принимать `alg: none` — явно белым списком разрешать только ожидаемые алгоритмы в конфиге библиотеки (`firebase/php-jwt`, `lcobucci/jwt`).

Уязвимо:
```php
// Библиотека декодирует без проверки алгоритма/issuer
$payload = JWT::decode($token, new Key($publicKey, null)); // alg не зафиксирован
```

Безопасно:
```php
$payload = JWT::decode($token, new Key($publicKey, 'RS256')); // алгоритм зафиксирован явно
if ($payload->iss !== config('services.auth.issuer') || $payload->exp < time()) {
    throw new AuthenticationException();
}
```

---

## 8. Авторизация: Policies, Gates, IDOR

IDOR (Insecure Direct Object Reference) — самая частая уязвимость AI-сгенерированного CRUD-кода: модель ищется по id из URL, но не проверяется владение.

Уязвимо:
```php
class AppointmentController extends Controller
{
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update($request->all()); // любой авторизованный пользователь может изменить чужую запись
        return $appointment;
    }
}
```

Безопасно:
```php
class AppointmentController extends Controller
{
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $appointment->update($request->validated());
        return new AppointmentResource($appointment);
    }
}

class AppointmentPolicy
{
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->patient_id
            || $user->id === $appointment->doctor_id
            || $user->hasRole('admin');
    }
}
```

Регистрация проверки политик глобально (страховка от забытого `authorize()`):
```php
// В тестах — принудительная проверка, что каждый роут с Model Binding вызывает Gate
Gate::before(function (User $user, string $ability) {
    if ($user->isSuperAdmin()) return true; // единственный legit bypass
});
```

Тест на IDOR (обязателен для каждого ресурса с PHI):
```php
public function test_user_cannot_view_other_users_patient(): void
{
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $patient = Patient::factory()->for($owner, 'primaryDoctor')->create();

    $this->actingAs($intruder)
        ->getJson("/api/patients/{$patient->id}")
        ->assertForbidden();
}
```

---

## 9. Mass Assignment

Уязвимо:
```php
class Patient extends Model
{
    protected $guarded = []; // ВСЁ разрешено к массовому присвоению
}

// Контроллер
Patient::create($request->all()); // пользователь может передать is_verified=true, doctor_id=<чужой> и т.п.
```

Безопасно:
```php
class Patient extends Model
{
    protected $fillable = ['full_name', 'date_of_birth', 'phone'];
    // критичные поля (is_verified, doctor_id, role) отсутствуют в fillable —
    // выставляются только явным кодом сервисного слоя
}

class StorePatientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
        ];
    }
}

// Use case
$patient = Patient::create($request->validated());
$patient->primaryDoctor()->associate($currentDoctor); // связь выставляется явно кодом, не из ввода
$patient->save();
```

Проверка: `grep -rn "guarded = \[\]\|fillable = \['\*'\]\|unguard(" app/` — 0 совпадений (или явно задокументированное исключение).

---

## 10. Race Conditions

Типичный кейс для медицинского проекта: двойная запись на один и тот же слот приёма, двойное списание квоты страховки.

Уязвимо:
```php
public function book(Request $request, Slot $slot)
{
    if ($slot->appointments()->count() >= $slot->capacity) {
        abort(409);
    }
    // между проверкой и записью — гонка: два параллельных запроса оба проходят check
    Appointment::create(['slot_id' => $slot->id, 'patient_id' => $request->user()->id]);
}
```

Безопасно (пессимистичная блокировка + уникальный констрейнт как последний рубеж):
```php
public function book(Request $request, Slot $slot)
{
    DB::transaction(function () use ($request, $slot) {
        $locked = Slot::where('id', $slot->id)->lockForUpdate()->first();

        if ($locked->appointments()->count() >= $locked->capacity) {
            throw new SlotFullException();
        }

        Appointment::create([
            'slot_id' => $locked->id,
            'patient_id' => $request->user()->id,
        ]);
    });
}
```
```sql
-- Дополнительный рубеж защиты на уровне БД
ALTER TABLE appointments ADD CONSTRAINT unique_patient_slot UNIQUE (slot_id, patient_id);
```

Для распределённых сценариев (несколько app-инстансов) — атомарный `Redis::set($key, 1, ['nx', 'ex' => 10])` как distributed lock перед критической секцией, либо `SELECT ... FOR UPDATE` в Postgres (предпочтительно, т.к. транзакционно согласовано с записью).
## 11. SSRF (Server-Side Request Forgery)

Актуально для интеграций с внешними лабораториями/страховыми/webhook'ами, где URL может влиять пользователь.

Уязвимо:
```php
public function fetchLabResult(Request $request)
{
    $response = Http::get($request->input('callback_url')); // пользователь передаёт http://169.254.169.254/latest/meta-data/
    return $response->body();
}
```

Безопасно:
```php
class AllowedHostValidator
{
    private const ALLOWED_HOSTS = ['lab-provider.example.com', 'insurance-api.example.com'];

    public function assertAllowed(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new InvalidArgumentException('Host not allowed');
        }
        // дополнительно: резолвим DNS и проверяем, что IP не приватный/локальный (защита от DNS rebinding)
        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new InvalidArgumentException('Resolved IP not allowed');
        }
    }
}

public function fetchLabResult(Request $request, AllowedHostValidator $validator)
{
    $url = $request->string('callback_url');
    $validator->assertAllowed($url);

    $response = Http::withOptions(['allow_redirects' => false]) // редиректы могут обходить allow-list
        ->timeout(5)
        ->get($url);

    return $response->body();
}
```

Дополнительно: на сетевом уровне (Docker) app-контейнер не должен иметь маршрута к cloud metadata endpoint (`169.254.169.254`) — блокировать через firewall/egress-политику, это защита "в глубину" сверх кода.

---

## 12. XXE (XML External Entity)

Актуально, если проект принимает XML (например, обмен с медицинскими системами по HL7/CDA, СМЭВ и т.п.).

Уязвимо:
```php
libxml_disable_entity_loader(false); // по умолчанию в старых версиях PHP была включена загрузка внешних сущностей
$doc = new DOMDocument();
$doc->loadXML($request->getContent(), LIBXML_NOENT); // разрешает внешние сущности → чтение /etc/passwd, SSRF
```

Безопасно:
```php
$doc = new DOMDocument();
$doc->loadXML(
    $request->getContent(),
    LIBXML_NONET | LIBXML_NOENT === false ? LIBXML_NONET : LIBXML_NONET
); // ключевое: НЕ передавать LIBXML_NOENT; запретить сетевой доступ флагом LIBXML_NONET

libxml_disable_entity_loader(true); // на PHP < 8 явно отключить (на PHP 8+ внешние сущности по умолчанию не резолвятся, но явная проверка не помешает)

// Дополнительно — валидация по строгой XSD-схеме перед обработкой
$doc->schemaValidate(resource_path('schemas/hl7-message.xsd'));
```

Правило: **никогда** не использовать `LIBXML_NOENT` вместе с недоверенным вводом; для парсинга внешних медицинских форматов предпочитать проверенные библиотеки (`symfony/serializer` с XML-адаптером в безопасном режиме) вместо ручного `DOMDocument`.

---

## 13. RCE / Command & Code Injection

Уязвимо:
```php
// "Генерация PDF через wkhtmltopdf" — классика AI-кода
exec("wkhtmltopdf {$request->input('url')} output.pdf"); // command injection через url

// Динамическое выполнение "формул" пользователя
eval('$result = ' . $request->input('formula') . ';');
```

Безопасно:
```php
// 1) Никогда не передавать пользовательский ввод в shell без escapeshellarg,
//    и предпочитать библиотеки, не вызывающие shell вовсе
$process = new Symfony\Component\Process\Process([
    'wkhtmltopdf', $validatedUrl, $outputPath, // массив аргументов — Symfony Process сам избегает shell-инъекции
]);
$process->setTimeout(30);
$process->mustRun();

// 2) Никогда не eval() пользовательский ввод.
//    Для "формул" — использовать безопасный expression evaluator с whitelisted операциями (напр. symfony/expression-language в sandbox-режиме),
//    либо заранее определённый набор операций без произвольного кода.
```

Проверка: `grep -rn "eval(\|exec(\|shell_exec(\|passthru(\|proc_open(\|system(\|assert(\$" app/` — каждое вхождение обязательно ревьюится, в идеале — 0 вхождений в приложении медицинского профиля.

---

## 14. Path Traversal / LFI

Уязвимо:
```php
public function download(Request $request)
{
    $path = storage_path('app/documents/' . $request->input('file'));
    return response()->file($path); // file=../../../../etc/passwd
}
```

Безопасно:
```php
public function download(Request $request, Document $document)
{
    $this->authorize('view', $document); // владение документом, не просто имя файла

    // путь строится из ID документа в БД, а не из пользовательской строки
    $path = Storage::disk('private')->path($document->storage_path);

    abort_unless(Storage::disk('private')->exists($document->storage_path), 404);

    return response()->file($path, ['Content-Disposition' => 'attachment; filename="' . $document->original_name . '"']);
}
```

Если имя всё же строится из пользовательского ввода — обязательна нормализация и проверка, что итоговый путь остаётся внутри разрешённой директории:
```php
$safeName = basename($request->input('file')); // отбрасывает directory traversal компоненты
$realPath = realpath(storage_path('app/documents/' . $safeName));
abort_unless($realPath && str_starts_with($realPath, realpath(storage_path('app/documents'))), 404);
```

---

## 15. Загрузка файлов (MIME, сигнатуры, SVG, PDF, EXIF)

Многослойная защита — расширение файла **никогда** не является источником истины.

### 15.1 Базовая валидация Laravel

```php
class UploadDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB, в килобайтах
                'mimes:pdf,jpg,jpeg,png', // проверка по расширению+MIME из fileinfo — не единственный рубеж
            ],
        ];
    }
}
```

### 15.2 Проверка сигнатуры файла (magic bytes) — обязательна поверх `mimes:`

```php
class FileSignatureValidator
{
    private const SIGNATURES = [
        'pdf' => ["\x25\x50\x44\x46"],                 // %PDF
        'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'jpg' => ["\xFF\xD8\xFF"],
    ];

    public function assertValid(UploadedFile $file, string $expectedType): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        foreach (self::SIGNATURES[$expectedType] as $sig) {
            if (str_starts_with($header, $sig)) {
                return;
            }
        }
        throw new InvalidFileSignatureException();
    }
}
```

### 15.3 SVG — отдельная опасность (XSS/XXE внутри "картинки")

SVG — это XML, может содержать `<script>`, `on*` обработчики событий, внешние сущности.

Уязвимо:
```php
Storage::put('avatars/' . $file->hashName(), $file->get()); // SVG сохраняется и отдаётся как есть → XSS при просмотре
```

Безопасно — либо запретить SVG полностью для загрузки пользователями (рекомендация по умолчанию для медицинского проекта), либо санитизировать:
```php
// Вариант А (рекомендуется): запретить SVG в rules() — 'mimes:jpg,jpeg,png,pdf' без svg

// Вариант Б: если SVG обязателен — санитизация через enshrined/svg-sanitize
$sanitizer = new \enshrined\svgSanitize\Sanitizer();
$clean = $sanitizer->sanitize(file_get_contents($file->getRealPath()));
Storage::disk('public')->put($path, $clean);
// плюс отдача с Content-Security-Policy: default-src 'none' на статике, X-Content-Type-Options: nosniff
```

### 15.4 PDF — может содержать JS/встроенные объекты

- Не открывать/рендерить загруженные PDF на сервере без изоляции (headless-конвертер в отдельном непривилегированном контейнере/sandbox).
- При необходимости "чистого" PDF — прогонять через `qpdf --decrypt --linearize` или конвертацию в изображения, а не отдавать оригинал напрямую при высоких требованиях к безопасности.

### 15.5 EXIF и метаданные изображений

Фото могут содержать геолокацию пациента/врача в EXIF — утечка при простой публикации.

```php
// Удаление EXIF при сохранении (через Intervention Image / GD)
$image = Image::make($file)->orientate(); // сохраняем поворот, но...
$image->save($destination); // GD backend не сохраняет EXIF по умолчанию — метаданные отбрасываются

// Явная проверка отсутствия GPS-данных перед сохранением, если backend сохраняет EXIF (Imagick):
$exif = @exif_read_data($file->getRealPath());
if (isset($exif['GPSLatitude'])) {
    throw new SensitiveMetadataException('Image contains GPS metadata');
}
```

### 15.6 Общий чек-лист загрузки файлов

- [ ] Ограничение размера (`max:` в rules + `client_max_body_size`/`upload_max_filesize` в инфраструктуре).
- [ ] Whitelist расширений и MIME (не blacklist).
- [ ] Проверка magic bytes/сигнатуры.
- [ ] Файлы сохраняются с новым сгенерированным именем (`hashName()`), не с оригинальным (защита от path traversal и коллизий).
- [ ] Каталог загрузок — вне webroot либо на приватном диске без прямого HTTP-доступа; выдача — через контроллер с `authorize()` и presigned/temporary URL.
- [ ] Веб-сервер не исполняет загруженную директорию как код (`php_admin_flag engine off` в nginx-location для `/storage/`, отдельный домен для user-generated контента).
- [ ] SVG запрещён или санитизирован.
- [ ] EXIF/метаданные проверены/очищены для изображений.
- [ ] Антивирусное сканирование при необходимости (ClamAV в отдельном сервисе, файл проверяется до попадания в постоянное хранилище).
## 16. Security Headers

Middleware, который стоит подключать глобально:

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }
}
```

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

| Заголовок | Значение | Зачем |
|---|---|---|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | запрет отката на HTTP |
| `X-Frame-Options` | `DENY` | защита от clickjacking (для медицинского UI критично) |
| `X-Content-Type-Options` | `nosniff` | запрет MIME-sniffing (актуально при отдаче загруженных файлов) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | не «сливать» полный URL (может содержать patient_id) в Referer стороннему домену |
| `Permissions-Policy` | явный запрет ненужных API браузера | снижение поверхности атаки |
| `Content-Security-Policy` | см. раздел 17 | защита от XSS |

Проверка: `curl -I https://your-domain` и сверка со списком; автоматизация — `securityheaders.com`-подобный скрипт в CI на stage-окружении.

---

## 17. CSP (Content-Security-Policy)

Уязвимо (типичный "быстрый" CSP от AI, который ничего не защищает):
```
Content-Security-Policy: default-src *; script-src * 'unsafe-inline' 'unsafe-eval';
```

Безопасно (стартовая строгая политика, дальше сужается под реальные источники):
```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'nonce-{RANDOM}';
  style-src 'self' 'nonce-{RANDOM}';
  img-src 'self' data: https://cdn.your-domain.com;
  font-src 'self';
  connect-src 'self' https://api.your-domain.com wss://ws.your-domain.com;
  object-src 'none';
  base-uri 'self';
  form-action 'self';
  frame-ancestors 'none';
  upgrade-insecure-requests;
```

Генерация nonce на бэкенде (Laravel + Blade):
```php
// Middleware
$nonce = base64_encode(random_bytes(16));
View::share('cspNonce', $nonce);
$response->headers->set('Content-Security-Policy', "script-src 'self' 'nonce-{$nonce}'; ...");
```
```blade
<script nonce="{{ $cspNonce }}">/* инлайн-скрипт, явно разрешённый через nonce */</script>
```

Правила:
- [ ] `unsafe-inline` и `unsafe-eval` — не использовать в проде; если легаси-код требует — временное исключение с тикетом на устранение.
- [ ] `report-uri`/`report-to` настроен, чтобы видеть нарушения CSP в проде до того, как это станет инцидентом.
- [ ] Политика сначала выкатывается в режиме `Content-Security-Policy-Report-Only`, метрики нарушений анализируются, затем включается в блокирующем режиме.

---

## 18. CORS

`config/cors.php` — частое место, где AI "чтобы заработало" ставит wildcard.

Уязвимо:
```php
return [
    'paths' => ['api/*'],
    'allowed_origins' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true, // КРИТИЧНО: '*' + credentials=true — это уже CVE-класс проблема,
                                     // браузер такое не разрешает исполнить корректно, но конфиг сигнализирует о непонимании модели
];
```

Безопасно:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'https://app.your-domain.com'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-XSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => true, // допустимо только с явным списком origin, никогда с '*'
];
```

Проверка: `allowed_origins` не содержит `*` при `supports_credentials => true`; список origin ревьюится при каждом изменении (staging/prod разделены переменной окружения, не хардкодом).
## 19. Docker Security

Уязвимо:
```dockerfile
FROM php:8.3-fpm
USER root
COPY . /var/www
RUN chmod -R 777 /var/www/storage   # избыточные права
CMD ["php-fpm"]
```
```yaml
# docker-compose.yml
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_PASSWORD: postgres   # дефолтный пароль
    ports:
      - "5432:5432"                 # БД торчит наружу из хоста
```

Безопасно:
```dockerfile
FROM php:8.3-fpm-alpine AS base
RUN addgroup -g 1000 app && adduser -D -u 1000 -G app app
WORKDIR /var/www
COPY --chown=app:app . .
RUN chmod -R 750 storage bootstrap/cache
USER app
CMD ["php-fpm"]
```
```yaml
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_PASSWORD: ${DB_PASSWORD}   # из секрет-менеджера/CI-секрета, не литерал
    networks:
      - internal
    # порт НЕ публикуется наружу — доступ только из docker-сети приложения
  app:
    networks:
      - internal
      - public
networks:
  internal:
    internal: true   # без выхода в интернет и с хоста
  public: {}
```

### Чек-лист

- [ ] Контейнеры не запускаются от `root` (`USER` директива, non-root UID).
- [ ] `.dockerignore` исключает `.env`, `.git`, `storage/logs`, `node_modules` — секреты и мусор не попадают в образ.
- [ ] Multi-stage build — dev-зависимости (`composer install --dev`, xdebug) не попадают в прод-образ.
- [ ] БД/Redis/Kafka не публикуют порты на хост (`ports:`) в проде — только внутренняя docker-сеть; наружу — только `app`/`nginx`.
- [ ] Секреты передаются через Docker secrets / переменные окружения из CI-хранилища, не зашиты в `Dockerfile`/образ.
- [ ] Образ сканируется (`trivy image your-app:latest`) на CVE перед деплоем, gate на critical/high.
- [ ] `read_only: true` + `tmpfs` для директорий, куда действительно нужна запись — минимизация поверхности атаки на файловую систему контейнера.
- [ ] Ресурсные лимиты (`mem_limit`, `cpus`) заданы — защита от DoS через один скомпрометированный/забагованный контейнер.
- [ ] Health-check и `restart: unless-stopped`, но без автоматического `--privileged` и без монтирования `/var/run/docker.sock` в app-контейнер (иначе — эскалация до контроля над хостом).

```bash
trivy image --severity HIGH,CRITICAL your-app:latest
docker scout cves your-app:latest
```

---

## 20. PostgreSQL Security

- [ ] Отдельный DB-пользователь для приложения с минимально необходимыми правами (не `postgres` суперюзер).
```sql
CREATE ROLE app_user LOGIN PASSWORD '...';
GRANT CONNECT ON DATABASE medical_app TO app_user;
GRANT USAGE ON SCHEMA public TO app_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO app_user;
-- НЕ выдавать: DROP, CREATE, ALTER, суперпользовательские права
```
- [ ] `pg_hba.conf` — доступ только по `scram-sha-256`, не `trust`/`md5`; подключение только из docker-сети приложения, не `0.0.0.0/0`.
- [ ] SSL/TLS для соединений app↔postgres, если трафик выходит за пределы одного docker host (`sslmode=verify-full` в `DB_*` env).
- [ ] Row-Level Security (RLS) как дополнительный рубеж для мультитенантных медицинских данных:
```sql
ALTER TABLE patients ENABLE ROW LEVEL SECURITY;
CREATE POLICY patients_isolation ON patients
    USING (clinic_id = current_setting('app.current_clinic_id')::uuid);
```
```php
// Установка контекста перед запросом (например, в middleware)
DB::statement("SET app.current_clinic_id = ?", [$user->clinic_id]);
```
- [ ] Регулярные бэкапы, зашифрованные at rest, с проверкой восстановления (restore drill), доступ к бэкапам аудируется отдельно.
- [ ] `log_statement = 'mod'` / pgAudit-расширение для аудита DML на чувствительных таблицах (раздел 25).
- [ ] Чувствительные колонки — рассмотреть `pgcrypto` (`pgp_sym_encrypt`) как альтернативу/дополнение к шифрованию на уровне Laravel.

---

## 21. Redis Security

Уязвимо — Redis без пароля, доступный извне (классика массовых компрометаций через открытый 6379 порт с последующей записью cron/SSH-ключа).

```yaml
# docker-compose — уязвимо
redis:
  image: redis:7
  ports:
    - "6379:6379"   # открыт наружу без auth
```

Безопасно:
```yaml
redis:
  image: redis:7
  command: redis-server --requirepass ${REDIS_PASSWORD} --protected-mode yes
  networks:
    - internal   # без публикации порта на хост
```
```
# redis.conf дополнительно
rename-command FLUSHALL ""
rename-command FLUSHDB ""
rename-command CONFIG ""
```
```php
// config/database.php
'redis' => [
    'default' => [
        'password' => env('REDIS_PASSWORD'),
        'options' => ['prefix' => env('APP_NAME') . '_database_'], // изоляция ключей между окружениями
    ],
],
```

- [ ] `requirepass` всегда задан, пароль — из секрет-хранилища.
- [ ] Порт не опубликован наружу, только внутренняя сеть.
- [ ] Опасные команды (`FLUSHALL`, `FLUSHDB`, `CONFIG`, `KEYS`) переименованы/отключены в проде.
- [ ] TLS для Redis 6+ (`tls-port`), если трафик пересекает границы доверенной сети.
- [ ] Кэш-ключи с PHI не хранят сырые данные пациента дольше необходимого — TTL обязателен, не `Cache::forever` для чувствительных данных.
- [ ] ACL (Redis 6+) — отдельный ограниченный пользователь для приложения вместо единственного `default`.

---

## 22. Kafka Security

- [ ] SASL/SCRAM или mTLS для аутентификации producer/consumer — не `PLAINTEXT` listener в проде.
```properties
# server.properties (прод)
listeners=SASL_SSL://0.0.0.0:9093
security.inter.broker.protocol=SASL_SSL
sasl.mechanism.inter.broker.protocol=SCRAM-SHA-512
ssl.client.auth=required
```
- [ ] ACL на топики — producer/consumer имеют доступ только к нужным топикам, не wildcard:
```bash
kafka-acls --add --allow-principal User:app-service \
  --operation Write --topic patient-events --bootstrap-server broker:9093
```
- [ ] PII в сообщениях Kafka — либо не отправлять напрямую (только идентификаторы + событие, детали дозапрашивать через защищённый API), либо шифровать payload на уровне приложения перед публикацией.
```php
// Producer — шифруем чувствительную часть payload перед отправкой в топик
$event = [
    'event' => 'appointment.created',
    'patient_id' => $appointment->patient_id, // идентификатор — ок
    'payload' => Crypt::encryptString(json_encode($sensitiveDetails)), // детали — зашифрованы
];
```
- [ ] Ретеншн и доступ к топикам с событиями PHI — минимально необходимый срок хранения, отдельные ACL для аналитики/BI (read-only, без доступа к «сырым» PHI-топикам).
- [ ] Мониторинг несанкционированных consumer-групп (unexpected `group.id` подключается к чувствительному топику — алерт).

---

## 23. Soketi / WebSocket Security

Soketi — self-hosted аналог Pusher для Laravel Echo/Broadcasting.

- [ ] `app_key`/`app_secret` — не дефолтные значения из документации, уникальны per-окружение.
- [ ] Приватные/presence-каналы для любых данных, связанных с пациентом — не `public` каналы:
```php
// routes/channels.php
Broadcast::channel('patient.{patientId}', function (User $user, int $patientId) {
    return $user->canAccessPatient($patientId); // явная авторизация канала
});
```
```js
// клиент — приватный канал
Echo.private(`patient.${patientId}`).listen('AppointmentUpdated', (e) => { ... });
```
- [ ] Endpoint авторизации broadcasting (`/broadcasting/auth`) защищён `auth:sanctum`, стандартный Laravel-механизм, не кастомный небезопасный обход.
- [ ] Soketi доступен только через TLS (`wss://`), не `ws://` в проде.
- [ ] Rate limiting на подключения/сообщения (Soketi поддерживает лимиты на уровне app) — защита от DoS через масштабное открытие сокетов.
- [ ] Не транслировать PHI в открытом виде через публичные/незащищённые каналы даже "временно для теста" — это частая ошибка вайбкодинга при быстрой демонстрации фичи.

---

## 24. Queue Security (Horizon / Redis Queue)

- [ ] Horizon dashboard (`/horizon`) защищён авторизацией, не открыт всем аутентифицированным пользователям:
```php
// app/Providers/HorizonServiceProvider.php
protected function gate(): void
{
    Gate::define('viewHorizon', function (User $user) {
        return $user->hasRole('admin');
    });
}
```
- [ ] Payload задач с PHI — не хранить чувствительные данные "как есть" в сериализованном job (Redis доступен нескольким сервисам) — передавать ID сущности, job сам дозагружает данные из БД при выполнении:
```php
// Уязвимо — весь пациент, включая PHI, лежит в очереди Redis в открытом виде
class SendReportJob implements ShouldQueue
{
    public function __construct(public Patient $patient) {}
}

// Безопасно — только идентификатор, данные подгружаются в момент выполнения
class SendReportJob implements ShouldQueue
{
    public function __construct(public int $patientId) {}

    public function handle(): void
    {
        $patient = Patient::findOrFail($this->patientId);
        // ...
    }
}
```
- [ ] `failed_jobs` таблица/Horizon failed tab — тоже может содержать PHI в трейсах, доступ ограничен так же строго, как к прод-БД.
- [ ] Идемпотентность обработчиков (уникальный `WithoutOverlapping`/`uniqueId`) — защита от повторной обработки при retry, особенно критично для операций типа "списать квоту"/"отправить уведомление пациенту".
- [ ] Лимиты на retry и backoff — защита от бесконечных циклов, способных стать DoS для внешних медицинских API.
## 25. Защита персональных данных (PII)

> Технические меры. Юридическую квалификацию (какой режим данных применим — 152-ФЗ, GDPR и т.д.) должен подтвердить юрист/DPO проекта — этот раздел не заменяет такую оценку. В qwicki нет медицинских данных; чувствительные данные — учебный профиль и контакты для входа.

### 25.1 Классификация данных

Перед реализацией любой новой фичи с персональными данными — явно промаркировать поля:

| Класс | Примеры в qwicki | Требования |
|---|---|---|
| PII (персональные) | email, телефон (auth_contacts), имя, город, дата рождения (user_profiles) | минимизация в логах/кэше, аудит доступа, шифрование желательно |
| Секреты | OTP-хэши, секреты identity, токены | только хэши/шифрование, никогда в логи и ответы API |
| Публичные/служебные | словарные статьи каталога, категории, уровни CEFR | без спецтребований |

AI-агенту при генерации новой миграции/модели стоит явно указывать в промпте/комментарии, какие поля относятся к PII/секретам — иначе он не расставит защиту самостоятельно.

### 25.2 Шифрование

```php
class Patient extends Model
{
    protected $casts = [
        'diagnosis_notes' => 'encrypted',
        'lab_results' => 'encrypted:array',
        'psychiatric_notes' => 'encrypted',
    ];
}
```

- [ ] `APP_KEY` — раздел 6; для дополнительного разделения секретов рассмотреть отдельный ключ шифрования PHI, не совпадающий с `APP_KEY` приложения (компрометация одного ключа не даёт немедленного доступа ко всем данным):
```php
// Кастомный Encrypter для PHI-полей на отдельном ключе
Crypt::extend('phi', fn () => new Encrypter(base64_decode(env('PHI_ENCRYPTION_KEY')), 'AES-256-CBC'));
```
- [ ] Ротация ключей — задокументирован процесс (re-encrypt при компрометации/плановой ротации).
- [ ] Поиск по зашифрованным полям — не строить `WHERE diagnosis_notes LIKE '%...%'` (невозможно/небезопасно на encrypted); для поиска — отдельный blind index (HMAC от нормализованного значения в отдельной колонке) при необходимости.

### 25.3 Контроль доступа и минимизация

- [ ] Каждый доступ к PHI проходит Policy, основанную на реальном клиническом отношении (лечащий врач, экстренный доступ с обоснованием), а не просто на роли "doctor" в вакууме.
- [ ] "Break-glass" доступ (экстренный просмотр карты вне обычных прав) — разрешён, но обязательно логируется с указанием причины и подсвечивается для последующего аудита/расследования.
- [ ] API-ответы возвращают только необходимые поля под конкретный сценарий (`Resource` с явным whitelist, раздел 2/API3) — не «вся карта пациента» для UI, которому нужны только ФИО и дата визита.
- [ ] Экспорт/выгрузка PHI (CSV, PDF-отчёты) — отдельно защищённый, ограниченный по частоте (rate limit) и логируемый функционал, т.к. это главный вектор массовой утечки.

### 25.4 Аудит

```php
// Простая audit-таблица + Observer
class PatientObserver
{
    public function retrieved(Patient $patient): void
    {
        // осторожно: retrieved вызывается часто, логировать выборочно
        // (напр. только просмотр карточки целиком, не каждый list-запрос)
    }

    public function updated(Patient $patient): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'patient.updated',
            'entity_id' => $patient->id,
            'changes' => $patient->getChanges(), // без значений PHI-полей — только имена изменённых полей
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
```
- [ ] Audit-лог **неизменяем** приложением (append-only; отдельная БД/схема, права `INSERT` без `UPDATE`/`DELETE` для app-пользователя), иначе скомпрометированное приложение может замести следы.
- [ ] Хранит: кто, когда, что (действие+сущность), откуда (IP/устройство) — но не сами значения PHI, только факт доступа/изменения.
- [ ] Ретеншн audit-логов — дольше, чем ретеншн самих данных, доступ к логам аудита сам по себе ограничен (кто аудирует аудиторов).
- [ ] Алерты на аномальные паттерны: один аккаунт просматривает необычно много карт подряд (возможный массовый экспорт/компрометация аккаунта).

### 25.5 Передача и хранение вне БД

- [ ] Медицинские документы (сканы, PDF) — приватный storage (S3/MinIO), доступ через presigned URL с TTL 5–15 минут, не постоянные публичные ссылки.
- [ ] Email/SMS-уведомления пациентам — не включают диагноз/детали визита в открытом тексте письма (утечка через почтового провайдера/перехват); только нейтральное "у вас новое сообщение в личном кабинете".
- [ ] Резервные копии и staging/dev окружения — не содержат реальных PHI; для dev/test использовать анонимизированные/синтетические данные (см. ниже).

```php
// Пример анонимизации при клонировании prod → staging (артизан-команда)
class AnonymizePatientsCommand extends Command
{
    public function handle(): void
    {
        Patient::query()->update([
            'full_name' => DB::raw("'Test Patient ' || id"),
            'phone' => DB::raw("'+000000000' || id"),
            'diagnosis_notes' => null,
        ]);
    }
}
```

### 25.6 Чек-лист PHI (сводный)

- [ ] Поле классифицировано (PHI/PII/публичное) до реализации.
- [ ] PHI-поля — encrypted casts / отдельный ключ шифрования.
- [ ] Доступ — через Policy с клинической логикой владения.
- [ ] API-ответы — whitelist полей под конкретный use case.
- [ ] Все обращения к PHI — в append-only audit log, без самих значений.
- [ ] Логи приложения/APM/Sentry — без PHI (скраббинг настроен).
- [ ] Кэш/очереди — без сырых PHI или с TTL и шифрованием.
- [ ] Файлы — приватный storage, presigned URL с коротким TTL.
- [ ] Dev/staging — только анонимизированные данные.
- [ ] Экспорт/выгрузка — rate limited, логируется, требует повышенных прав.
## 26. Автоматизация: команды для CI/pre-commit

### 26.1 Состав инструментов

| Инструмент | Назначение | Установка |
|---|---|---|
| `composer audit` | известные CVE в PHP-зависимостях | встроено в Composer 2.4+ |
| `npm audit` / `pnpm audit` | известные CVE в JS-зависимостях | встроено |
| Larastan (`nunomaduro/larastan`) | статический анализ уровня Laravel поверх PHPStan | `composer require --dev larastan/larastan` |
| Psalm | альтернативный/дополнительный статический анализ, сильнее в taint-анализе (отслеживание потока недоверенных данных → полезно для поиска injection) | `composer require --dev vimeo/psalm` |
| Laravel Pint | code style (не безопасность напрямую, но единый стиль упрощает ревью diff) | входит в Laravel |
| `enlightn/security-checker` | сканирование Laravel-специфичных security misconfig | `composer require --dev enlightn/security-checker` |
| `roave/security-advisories` | conflict-пакет, физически не даёт установить пакеты с известными уязвимостями | `composer require --dev roave/security-advisories:dev-latest` |
| `gitleaks` / `trufflehog` | поиск секретов в коде/истории git | бинарники, ставятся в CI-образ |
| `trivy` / `docker scout` | сканирование Docker-образов на CVE | бинарники |
| PHPUnit / Pest + security feature-тесты | функциональные тесты авторизации, IDOR, mass assignment | входит в Laravel |
| `deptrac` | контроль архитектурных границ DDD-слоёв (Domain не зависит от Infrastructure и т.п.) | `composer require --dev qossmic/deptrac` |
| `phpmd` | доп. проверки на code smells, потенциально опасные конструкции | `composer require --dev phpmd/phpmd` |

### 26.2 Единая команда для локального запуска (Makefile)

```makefile
.PHONY: security
security:
	composer audit
	composer require --dry-run roave/security-advisories:dev-latest || true
	./vendor/bin/phpstan analyse --memory-limit=1G
	./vendor/bin/psalm --taint-analysis
	./vendor/bin/pint --test
	./vendor/bin/deptrac analyse
	php artisan test --filter=Security
	gitleaks detect --source . --no-git -v
	npm audit --audit-level=high
```

### 26.3 Pre-commit hook (Husky / Composer script)

```json
// composer.json
{
    "scripts": {
        "pre-commit": [
            "@php artisan config:clear",
            "gitleaks protect --staged -v",
            "./vendor/bin/pint --dirty",
            "./vendor/bin/phpstan analyse --memory-limit=1G"
        ]
    }
}
```
```bash
# .git/hooks/pre-commit (или через Husky)
#!/bin/sh
composer run pre-commit || exit 1
```

### 26.4 GitHub Actions пример gate'а

```yaml
name: security
on: [pull_request]
jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with: { fetch-depth: 0 }
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: composer audit
      - run: ./vendor/bin/phpstan analyse --error-format=github
      - run: ./vendor/bin/psalm --taint-analysis --output-format=github
      - run: ./vendor/bin/deptrac analyse --fail-on-uncovered
      - run: php artisan test --filter=Security --stop-on-failure
      - uses: gitleaks/gitleaks-action@v2
      - run: npm ci && npm audit --audit-level=high
      - name: Build image for scan
        run: docker build -t app:ci .
      - uses: aquasecurity/trivy-action@master
        with:
          image-ref: 'app:ci'
          severity: 'CRITICAL,HIGH'
          exit-code: '1'
```

### 26.5 Пример security feature-теста, обязательного в наборе `--filter=Security`

```php
class MassAssignmentSecurityTest extends TestCase
{
    public function test_patient_cannot_set_protected_fields_via_api(): void
    {
        $doctor = User::factory()->doctor()->create();
        $otherDoctor = User::factory()->doctor()->create();

        $this->actingAs(User::factory()->patient()->create())
            ->postJson('/api/patients', [
                'full_name' => 'Test',
                'date_of_birth' => '1990-01-01',
                'phone' => '+10000000000',
                'primary_doctor_id' => $otherDoctor->id, // попытка подмены
                'is_verified' => true,                    // попытка подмены
            ])->assertCreated();

        $this->assertDatabaseMissing('patients', ['is_verified' => true]);
    }
}
```
## 27. Release Checklist

> Прогоняется перед мержем в `main`/созданием релизного тега.

- [ ] Все пункты [раздела 0](#0-чек-лист-после-каждого-сеанса-вайбкодинга) выполнены для каждого коммита в релизе.
- [ ] `composer audit`, `npm audit --audit-level=high` — 0 high/critical.
- [ ] Larastan/Psalm — 0 новых ошибок (baseline не расширен без обоснования в PR).
- [ ] `deptrac analyse` — архитектурные границы DDD не нарушены (Domain не зависит от Infrastructure/Eloquent напрямую).
- [ ] Полный набор тестов зелёный, включая `--filter=Security`.
- [ ] `php artisan route:list` — сверен с ожидаемым списком, нет неожиданных публичных/debug роутов (`/telescope`, `/horizon`, `/_debugbar` защищены или отключены для прод-сборки).
- [ ] Ревью diff по `config/cors.php`, `config/sanctum.php`, `.env.example`, `Dockerfile*`, `docker-compose*.yml`, `nginx/*` — выполнено человеком, не только AI-агентом.
- [ ] CHANGELOG/security-notes обновлены, если релиз меняет модель доступа/добавляет новые PHI-поля.
- [ ] Для новых PHI-полей — раздел 25.6 пройден полностью.
- [ ] Миграции проверены на обратимость (`down()` реализован) и на отсутствие `DROP COLUMN`/`DROP TABLE` без явного согласования (риск потери медицинских данных).
- [ ] Секреты, добавленные новым функционалом (API-ключи внешних интеграций), заведены в секрет-хранилище прод-окружения, а не только в `.env.example`.

---

## 28. Deploy Checklist

> Прогоняется непосредственно перед выкаткой в stage/production.

### Перед деплоем

- [ ] `APP_ENV=production`, `APP_DEBUG=false` в прод `.env`/секрет-хранилище — проверено автоматической проверкой в CI/CD, не только вручную.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`, `event:cache` выполняются в рамках деплой-скрипта после каждого релиза.
- [ ] `php artisan migrate --force` — миграции прогнаны с бэкапом БД непосредственно перед этим шагом (снапшот/pg_dump).
- [ ] Docker-образ прошёл сканирование (`trivy`) без critical/high CVE.
- [ ] TLS-сертификат валиден и не истекает в ближайшие 30 дней (мониторинг).
- [ ] Security headers (раздел 16) и CSP (раздел 17) проверены на реальном stage-URL (`curl -I`, онлайн-сканер заголовков).
- [ ] Rate limiting активен на login/API (не отключён по ошибке в проде debug-конфигом).
- [ ] Redis/PostgreSQL/Kafka порты не опубликованы на внешний интерфейс хоста — проверка `docker compose ps` / `nmap` по внешнему IP.
- [ ] Horizon/Telescope/любые dev-панели — недоступны анонимно на prod-URL.
- [ ] Логирование настроено на прод-уровень (`LOG_LEVEL`), алертинг подключён (Sentry/аналог), скраббинг PHI в логах активен.
- [ ] Резервное копирование БД включено, расписание подтверждено, последний restore-drill не старше квартала.
- [ ] Плейбук инцидент-реагирования (кто, куда звонить/пишет при подозрении на утечку PHI) актуален и доступен дежурному.

### После деплоя (smoke checks)

- [ ] Health-check эндпоинт отвечает 200, ключевые сценарии (логин, просмотр карты пациента с валидными правами) работают.
- [ ] Попытка доступа к чужому ресурсу с тестового аккаунта возвращает 403 (быстрый ручной IDOR-смок-тест на проде).
- [ ] Заголовки ответа (HSTS, CSP, X-Frame-Options) присутствуют на прод-домене.
- [ ] Ошибка (искусственно вызванная на staging-копии) не показывает stack trace/путь на сервере.
- [ ] Мониторинг ошибок (Sentry/аналог) не показывает всплеска 500/401/403 в первые 15 минут после деплоя.

---

## 29. Приложения

### 29.1 Аннотированный `.env.example` (фрагмент)

```dotenv
APP_NAME="Medical App"
APP_ENV=local            # local | staging | production — production ⇒ APP_DEBUG обязан быть false
APP_KEY=                 # генерируется `php artisan key:generate`, НИКОГДА не коммитить реальное значение
APP_DEBUG=false          # true допустим только в local
APP_URL=https://localhost

LOG_CHANNEL=stack
LOG_LEVEL=info            # debug — только local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=medical_app
DB_USERNAME=app_user       # НЕ postgres/суперюзер
DB_PASSWORD=               # из секрет-хранилища в проде

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=            # обязателен, не пусто в staging/production
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=app.your-domain.com   # без wildcard
SESSION_SECURE_COOKIE=true                     # true в staging/production
SESSION_SAME_SITE=lax

FRONTEND_URL=https://app.your-domain.com       # используется в config/cors.php, не хардкодить '*'

PHI_ENCRYPTION_KEY=        # отдельный ключ для encrypted-полей с PHI, если используется кастомный Encrypter
```

### 29.2 Пример hardened nginx location для storage/загрузок

```nginx
location /storage/ {
    # запрет исполнения любых скриптов в директории загрузок
    location ~ \.(php|phtml|php\d)$ {
        deny all;
        return 403;
    }
    add_header X-Content-Type-Options "nosniff" always;
    add_header Content-Security-Policy "default-src 'none'" always;
}

location ~ /\.env {
    deny all;
    return 404;
}

location ~ /\.git {
    deny all;
    return 404;
}
```

### 29.3 Быстрый grep-набор для аудита diff вручную

```bash
# Опасные конструкции
grep -rnE "eval\(|exec\(|shell_exec\(|passthru\(|proc_open\(|assert\(\\\$|unserialize\(" app/

# Возможная SQL-инъекция
grep -rnE "DB::raw\(|whereRaw\(|selectRaw\(|orderByRaw\(" app/

# Небезопасный Mass Assignment
grep -rnE "guarded\s*=\s*\[\]|fillable\s*=\s*\['\*'\]|unguard\(" app/

# Небезопасный Blade-вывод
grep -rn "{!! " resources/views/

# Возможные секреты
grep -rnE "(password|secret|api[_-]?key|token)\s*=\s*['\"][^'\"]{6,}" app/ config/ --exclude-dir=vendor
```

### 29.4 Минимальный набор security feature-тестов на новый модуль (шаблон для AI-промпта)

При постановке задачи AI-агенту на новый модуль с PHI явно требуйте сгенерировать вместе с фичей:
1. Тест: чужой пользователь получает 403/404 на чтение/запись ресурса (IDOR).
2. Тест: попытка передать protected-поле через API не проходит (Mass Assignment).
3. Тест: неавторизованный запрос получает 401.
4. Тест: запись действия появляется в audit log (если ресурс — PHI).
5. Тест: ответ API не содержит полей вне whitelist Resource.

---

## Итог

Этот playbook — живой документ. При появлении новых интеграций (новая внешняя медицинская система, новый способ авторизации, новый тип файла для загрузки) — соответствующий раздел обновляется **до** того, как AI-агент начнёт генерировать код для этой интеграции, чтобы у него был явный контекст ограничений, а не только "работающий код любой ценой".
