# Архитектура проекта

## Архитектурный подход

Проект использует **Modular Monolith + Domain-Driven Design (DDD) + Hexagonal/Clean Architecture**.

Система разделена на независимые бизнес-модули (`Modules`), каждый из которых представляет отдельную предметную область / bounded context.

Внутри каждого модуля используется разделение на:

```text
Domain → Application → Infrastructure
```

Основные цели архитектуры:

- изолировать бизнес-логику от Laravel;
- отделить бизнес-правила от инфраструктуры;
- минимизировать связанность между модулями;
- сделать модули независимо развиваемыми;
- обеспечить возможность в будущем вынести отдельный модуль в отдельный сервис без переписывания Domain-логики.

Laravel рассматривается преимущественно как **Infrastructure / Framework**, а не как основа бизнес-логики.

---

## Основные слои системы

Общая зависимость:

```text
Presentation / HTTP
        ↓
Application
        ↓
Domain
        ↑
Infrastructure
```

Более точно:

```text
┌─────────────────────────────────────┐
│ Presentation / HTTP                 │
│ Controllers / Requests / Responses  │
└──────────────────┬──────────────────┘
                   ↓
┌─────────────────────────────────────┐
│ Application                         │
│ UseCases / DTO / orchestration      │
└──────────────────┬──────────────────┘
                   ↓
┌─────────────────────────────────────┐
│ Domain                              │
│ Entities / ValueObjects / Rules     │
│ Ports / Domain Services             │
└──────────────────┬──────────────────┘
                   ↑
┌─────────────────────────────────────┐
│ Infrastructure                      │
│ Eloquent / HTTP / AI / Files / etc. │
└─────────────────────────────────────┘
```

### Domain

Содержит бизнес-модель и правила предметной области.

Отвечает за:

- Entities;
- Value Objects;
- Domain Services;
- Domain Events;
- Enums;
- бизнес-инварианты;
- интерфейсы (`Ports`) для необходимых внешних зависимостей.

**Domain не должен зависеть от Laravel, Eloquent, HTTP, БД или конкретного AI-провайдера.**

### Application

Отвечает за **сценарии использования системы**.

Содержит:

- Use Cases;
- DTO;
- orchestration;
- Application Services;
- преобразование входных данных в вызовы Domain;
- координацию нескольких Domain-компонентов;
- Application-level exceptions.

Application может зависеть от Domain.

Application не должен содержать инфраструктурный код.

Типичный сценарий (модуль Learning):

```text
SubmitReviewUseCase
    ↓
ProgressRepositoryInterface + LanguageProfileRepositoryInterface
    ↓
EloquentProgressRepository (Infrastructure)
    ↓
Domain entities (ReviewProgress, ReviewResult)
```

Порты (`*RepositoryInterface`) — интерфейсы из Domain, их Eloquent-реализации находятся в Infrastructure. SM-2 пересчёт живёт в UseCase, а не в репозитории.

### Infrastructure

Содержит технические реализации интерфейсов и интеграции.

Отвечает за:

- Eloquent;
- SQLite (dev; тесты — `:memory:`);
- HTTP-клиенты;
- файловое хранилище (xlsx-словари в `storage/app/public/words`);
- внешние API;
- Laravel-specific код;
- конкретные реализации Domain Ports;
- persistence;
- framework configuration.

Infrastructure **не должна определять бизнес-правила**.

### Presentation / HTTP

Отвечает только за взаимодействие с внешним миром.

Например:

- Controllers;
- HTTP Requests;
- HTTP Responses;
- route handlers;
- serialization.

Типичный поток (конвенция lexio — слой Take между Controller и UseCase):

```text
Request (FormRequest-валидация)
   ↓
Controller (тонкий: только делегирует в Take)
   ↓
Take (собирает Command из Request, вызывает UseCase, отдаёт *ResponseResource)
   ↓
UseCase(Command)
   ↓
Response ({success, data})
```

Controller не должен самостоятельно выполнять бизнес-логику. Маршруты модуля — в `routes/router.php` (или `routes/User.php`), подключаются в провайдере модуля (`Route::middleware('api')->prefix('api')->group(...)`); биндинги портов и `loadMigrationsFrom` — там же.

---

## Структура проекта

```text
app/
├── Modules/
│   ├── Auth/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Providers/
│   │   └── routes/
│   │
│   ├── User/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Providers/
│   │   └── routes/
│   │
│   └── <Module>/
│       ├── Domain/
│       │   ├── Entities/
│       │   ├── ValueObjects/
│       │   ├── Enums/
│       │   ├── Ports/
│       │   ├── Services/
│       │   └── Exceptions/
│       │
│       ├── Application/
│       │   ├── UseCases/
│       │   ├── DTO/
│       │   ├── AI/
│       │   └── Exceptions/
│       │
│       ├── Infrastructure/
│       │   ├── Persistence/
│       │   ├── External/
│       │   └── Http/
│       │
│       ├── Providers/
│       └── routes/
│
├── Shared/
│   ├── Core/
│   │   ├── Application/   # DTO (пагинация и т.п.)
│   │   └── Domain/        # общие Enums, константы
│   └── Laravel/
│       └── Infrastructure/
│           ├── Http/          # ApiController, AppFormRequest
│           ├── Mapper/
│           ├── Persistence/   # Translation, HasTranslations, касты
│           └── Security/      # JWT, middleware
│
└── Providers/             # AppServiceProvider (регистрирует провайдеры модулей)
```

---

## Правило создания новых модулей

Новый бизнес-модуль создаётся внутри:

```text
app/Modules/
```

Например:

```text
app/Modules/Learning/
```

Фактический инвентарь модулей lexio: `Auth`, `User`, `Catalog`, `Learning`, `Library` (+ кросс-каттинговый `Shared`).

Стандартная структура:

```text
<Module>/
├── Domain/
├── Application/
├── Infrastructure/
├── Providers/
└── routes/
```

Внутри `Domain`:

```text
Domain/
├── Entities/
├── ValueObjects/
├── Enums/
├── Ports/
├── Services/
└── Exceptions/
```

Внутри `Application`:

```text
Application/
├── UseCases/
├── DTO/
├── AI/
└── Exceptions/
```

Внутри `Infrastructure`:

```text
Infrastructure/
├── Persistence/
├── External/
└── Http/
```

**Не создавать новые верхнеуровневые директории внутри модуля без архитектурной необходимости.**

---

## Shared

`Shared` содержит только действительно общие компоненты, используемые несколькими модулями.

Например:

```text
Shared/
├── AI/
├── Core/
└── File/
```

Shared не должен использоваться как «склад различных утилит».

Если компонент относится исключительно к одному модулю, он должен находиться внутри этого модуля.

Неправильно:

```text
Shared/Catalog/Entry.php
```

если компонент используется только `Catalog`.

Правильно:

```text
Modules/
└── Catalog/
    └── Domain/
        └── Entities/
            └── CatalogEntry.php
```

---

## Laravel Infrastructure

Laravel-specific код должен находиться в Infrastructure.

Например:

```text
Shared/
└── Laravel/
    └── Infrastructure/
        ├── Http/
        ├── Mapper/
        ├── Persistence/
        ├── Providers/
        └── Security/
```

Laravel-код не должен проникать в Domain.

В Domain запрещено использовать:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
```

---

## Правило для Eloquent Models

Eloquent Model и Domain Entity — **разные вещи**.

Eloquent Model отвечает за persistence и относится к Infrastructure.

Domain Entity отвечает за бизнес-модель и находится в Domain.

Связь:

```text
Database
    ↓
Eloquent Model
    ↓
Mapper
    ↓
Domain Entity
```

Не использовать Eloquent Model непосредственно как Domain Entity.

---

## Взаимодействие модулей

Модули должны быть максимально изолированы.

```text
Module A
    ↓
Public Contract / Interface
    ↓
Module B
```

Запрещено обращаться к внутренней реализации другого модуля:

```text
❌ Modules/User/Domain/Entities/User.php
   ↓
Modules/Learning/Application/...
```

Если модулю `Learning` требуется функциональность `User`, необходимо определить публичный контракт.

Например:

```php
interface UserProvider
{
    public function getUser(UserId $id): User;
}
```

Конкретная реализация предоставляется через Dependency Injection.

---

## Направление зависимостей

Ключевое правило:

```text
Infrastructure
      ↓
Application
      ↓
Domain
```

Но Domain **никогда не должен зависеть от Infrastructure**.

Например:

```text
Domain
  └── AiAnalyzer.php          ← interface

Infrastructure
  └── OpenAiAnalyzer.php      ← implementation
```

Зависимость направлена **к абстракции**, а не к реализации.

---

## Правила

Конкретные AI-провайдеры не должны находиться в Domain:

```text
❌ Domain/OpenAI
❌ Domain/Claude
```

Domain определяет абстракцию:

```text
Domain/
└── Ports/
    └── AiAnalyzer.php
```

Application определяет сценарий (пример для внешнего AI-провайдера, если появится):

```text
Application/
└── AI/
    └── AnalyzeText.php
```

Infrastructure реализует конкретного провайдера:

```text
Infrastructure/
└── External/
    ├── OpenAI/
    └── Claude/
```

Схема:

```text
                 ┌───────────────┐
                 │ AiAnalyzer    │
                 │   interface   │
                 └───────┬───────┘
                         ↑
             ┌───────────┴───────────┐
             │                       │
       OpenAiAnalyzer          ClaudeAnalyzer
```

Замена AI-провайдера не должна требовать изменения бизнес-логики.

---

## Правило создания файлов

Новый файл должен находиться в соответствующем слое.

### Business Entity

```text
Modules/Catalog/Domain/Entities/
```

### Value Object

```text
Modules/Catalog/Domain/ValueObjects/
```

### Repository / Port interface

```text
Modules/Catalog/Domain/Ports/
```

### Use Case (+ Command рядом)

```text
Modules/Catalog/Application/UseCases/GetEntry/
```

### DTO

```text
Modules/Auth/Application/DTO/
```

### Eloquent repository

```text
Modules/Catalog/Infrastructure/Persistence/Eloquent/
```

### Console-команда

```text
Modules/Catalog/Infrastructure/Console/Commands/ImportWordsCommand.php
```

### Controller / Take / Request / Resource

```text
Modules/Catalog/Infrastructure/Http/{Controllers,Takes,Requests,Resources}/
```

---

## Важные ограничения

### Запрещено

```text
Presentation → Database
Presentation → Eloquent
Domain → Laravel
Domain → Infrastructure
Domain → HTTP
Domain → конкретный AI provider
```

### Разрешено

```text
Presentation → Application
Application → Domain
Infrastructure → Domain
Infrastructure → Application
```

Конкретные зависимости должны по возможности проходить через интерфейсы.

---

## Границы ответственности

| Слой | Может | Не может |
|---|---|---|
| **Presentation / HTTP** | принимать запросы, валидировать формат, формировать response | содержать бизнес-логику, напрямую обращаться к БД |
| **Application** | запускать Use Cases, координировать Domain, DTO | содержать инфраструктурную реализацию |
| **Domain** | Entities, Value Objects, бизнес-правила, Domain Services, Ports | знать о Laravel, HTTP, БД, Eloquent, AI-провайдерах |
| **Infrastructure** | БД, Eloquent, HTTP API, AI, файловую систему, Laravel | определять бизнес-правила |

---

## Checklist перед изменением кода

Перед созданием или изменением нужно проверить:

- [ ] Определён модуль / bounded context.
- [ ] Определён архитектурный слой.
- [ ] Бизнес-логика не попадает в Infrastructure.
- [ ] Бизнес-логика не попадает в Controller.
- [ ] Domain не зависит от Laravel.
- [ ] Domain не зависит от Eloquent.
- [ ] Domain не зависит от конкретного AI-провайдера.
- [ ] Eloquent используется только в Infrastructure.
- [ ] Внешние сервисы используются через Ports / Interfaces.
- [ ] Межмодульное взаимодействие не использует внутренние классы другого модуля.
- [ ] Новый общий компонент действительно является общим.
- [ ] Не создаётся лишняя директория или абстракция.
- [ ] Существующие компоненты переиспользованы там, где это возможно.

---

## Краткая архитектурная модель

```text
                    Laravel
                       │
                       ▼
              Infrastructure / HTTP
                       │
                       ▼
                  Application
                       │
                       ▼
                    Domain
                       ▲
                       │
              Infrastructure
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
       Database      AI APIs     External APIs
```

Каждый модуль представляет отдельную предметную область:

```text
app/Modules/
├── Auth/
├── User/
├── Catalog/
├── Learning/
└── Library/
```

Назначение: `Auth` — вход/токены; `User` — аккаунт и профиль; `Catalog` — системный словарь (чтение) + импорт; `Learning` — языковые профили, SM-2, статистика; `Library` — свои слова/фразы пользователя.

Модули должны быть максимально независимыми, а Domain каждого модуля должен оставаться независимым от Laravel и конкретных инфраструктурных технологий.
