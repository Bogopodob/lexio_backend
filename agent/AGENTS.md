# AGENTS.md — точка входа

Этот файл — карта контекста проекта. Читай его первым в каждой новой сессии.

Проект: **qwicki** — приложение для изучения языков (backend Laravel + frontend React рядом, в `../frontend`).

## Ограничения: frontend находится на одном уровне с backend. Frontend можно читать и редактировать по запросу пользователя.

## Порядок чтения

1. **Всегда читай перед началом работы:**
   - `agent/ai/TECHSTACK.md` — какие технологии разрешены
   - `agent/ai/ARCHITECTURE.md` — как устроена система
   - `agent/ai/CURRENT-SPRINT.md` — что делать сейчас

2. **Читай по необходимости, в зависимости от задачи:**
   - Работаешь с БД → `agent/ai/DB_SCHEMA.md`
   - Нужна документация библиотеки → `agent/ai/LINKS.md`
   - Что уже реализовано и почему так → `agent/ai/IMPLEMENTED_CONCEPT.md`

## Общие правила

- Не выбирай технологии/библиотеки самостоятельно — если нужной нет в `TECHSTACK.md`, спроси у пользователя.
- Не придумывай структуру БД — если её нет в `DB_SCHEMA.md`, предложи изменения, но не применяй их без подтверждения.
- После завершения задачи обнови статус в `CURRENT-SPRINT.md`.

- После каждой задачи проверяй `CHECK_SECURITY.md`

## Работа с экосистемой
Docker-окружение — в `/home/laptop/Projects/qwicki/infractructure`, PHP-контейнер — `q_php` (код смонтирован в `/var/www/backend`).
```bash
# Шелл в PHP-контейнер (вариант 1 — через make из infractructure)
make backend-shell

# То же напрямую
docker exec q_php sh

# Команда для доступа к бд (sqlite-файл)
docker exec q_php sh -c 'php -r "\$db=new PDO(\"sqlite:/var/www/backend/database/database.sqlite\");"'

# Команда получения логов
make logs

# Работа с docker системой
make up
make down
make restart
```

Типовой цикл проверки backend (внутри `q_php`, каталог `/var/www/backend`):
```bash
php artisan migrate:fresh --seed --force
php artisan test
./vendor/bin/pint --test app database tests routes
```
