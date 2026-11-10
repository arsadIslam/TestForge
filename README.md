# TestForge — online exam & test-series platform

Teachers build question banks and timed tests. Students take them with a server-enforced timer and
auto-save, get scored by a queued job, and see their rank, percentile and weakest subjects.

Built with **Laravel 12** as a portfolio project. Every feature exists to show a common interview
topic: relationships, auth & policies, queues, events, caching, transactions with row locks,
indexing, window functions, and tests.

| Layer | Choice |
|---|---|
| Framework | Laravel 12, PHP 8.2+ |
| Database | MySQL 8.0+ or MariaDB 10.4+ (CTEs and window functions are required) |
| Web UI | Blade + Alpine.js + Tailwind (Laravel Breeze) |
| API | Laravel Sanctum personal access tokens, API Resources |
| Queue, cache, sessions | `database` driver (works on shared hosting); Redis is a `.env` change |
| Tests | Pest — 125 tests on MySQL |

---

## Contents

- [Features](#features)
- [Quick start](#quick-start)
- [Demo logins](#demo-logins)
- [Architecture](#architecture)
- [Database (ER diagram)](#database)
- [Raw SQL vs Eloquent — the key queries](#raw-sql-vs-eloquent)
- [Concurrency: where the locks are](#concurrency)
- [REST API](#rest-api)
- [Tests](#tests)
- [Performance](#performance)
- [Deploying to cPanel shared hosting](#deploying-to-cpanel)

---

## Features

**Admin**
- Dashboard with counts (users by role, content, tests by status).
- Users: search and filter, change role, activate / deactivate (deactivation also revokes API tokens). An admin can't change their own role or status.
- Subjects and topics: full CRUD. Slugs are generated from names; a subject or topic that still has questions can't be deleted.
- Reports: every teacher's tests with averages, published tests nobody has attempted, and the full analytics of any test.

**Teacher**
- Question bank: single / multiple choice, 2–6 options edited with Alpine, difficulty, marks, negative marks, explanation, tags. Filter by topic, difficulty and tag.
  - The bank is shared (any teacher can view and use a question); only the author can edit or delete it.
  - Once a question is in a published test its **answer key is frozen** (type, options, correct answers).
- Test builder: details, time window, attempts allowed, shuffle. Add questions from the bank, reorder them, remove them.
  - Publishing checks: at least one question, none inactive, pass mark ≤ total, end date not passed. A published test can only be archived.
- Analytics per test: average score, pass rate, top 10, **toughest 10 questions**, every result.
- Dashboard: own counts, latest results, published tests with no attempts yet.

**Student**
- Available tests with attempts used / left; start or resume.
- Test screen: one question at a time, navigator, countdown, **auto-save on every answer**, submit with a summary of unanswered questions. When time runs out it submits itself.
- Result page: score, pass/fail, correct / wrong / skipped, accuracy, time, rank and percentile, every question with the correct answer and explanation.
- Leaderboard per test (best attempt per student), and *My analytics*: accuracy by subject, weakest first.

**Behind the scenes**
- `EvaluateAttempt` queued job scores each submission (idempotent, retried 3×).
- `attempts:auto-submit` runs every minute and closes attempts whose time ran out.
- `ResultPublished` event → `ClearLeaderboardCache` listener.

---

## Quick start

Requirements: PHP 8.2+ with `pdo_mysql`, Composer, Node 20+, MySQL 8 / MariaDB 10.4+.

```bash
git clone https://github.com/<you>/TestForge.git && cd TestForge
composer install
cp .env.example .env && php artisan key:generate

# databases: one for the app, one for the tests (phpunit.xml uses testforge_testing)
mysql -uroot -e "CREATE DATABASE testforge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                 CREATE DATABASE testforge_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# set DB_USERNAME / DB_PASSWORD in .env if yours aren't root / empty

php artisan migrate --seed        # schema + demo data (~2 s)
npm install && npm run build

composer run dev                  # web server, queue worker, logs and Vite, together
php artisan schedule:work         # in a second terminal: runs attempts:auto-submit every minute
```

Open http://localhost:8000.

The seed creates 1 admin, 3 teachers, 50 students, 5 subjects × 4 topics, 200 questions, 3 published tests
and 90 scored attempts, so the leaderboards and analytics have data straight away. Results are produced by
the real `ScoringService`, not random numbers, so every report agrees with every other.

---

## Demo logins

Password for every account: **`password`**

| Role | Email |
|---|---|
| Admin | `admin@testforge.test` |
| Teacher | `teacher1@testforge.test` (also `teacher2`, `teacher3`) |
| Student | `student1@testforge.test` — has results in two tests; the *Computer Science Quiz* is still open to take |

---

## Architecture

```
app/
  Enums/              UserRole, QuestionType, Difficulty, TestStatus, AttemptStatus (backed enums, cast on models)
  Services/           business logic — controllers stay thin
    QuestionService     save question + options + tags in one transaction; frozen answer key
    TestService         attach / reorder / publish / archive under a row lock
    AttemptService      start / save answer / submit / auto-submit under row locks
    ScoringService      scores an attempt (idempotent) and fires ResultPublished
    LeaderboardService  window-function ranking, cached 5 min
    AnalyticsService    subject accuracy, toughest questions, untouched tests, summaries
  Http/
    Controllers/Web/{Admin,Teacher,Student}   Blade app
    Controllers/Api/V1                        REST API
    Requests/                                 all validation lives in Form Requests
    Resources/                                API Resources (JSON shape)
    Middleware/EnsureRole.php                 route-level role check ('role:teacher')
  Policies/           record-level checks (QuestionPolicy, TestPolicy, AttemptPolicy, UserPolicy)
  Jobs/EvaluateAttempt.php
  Events/ResultPublished.php  →  Listeners/ClearLeaderboardCache.php
  Console/Commands/AutoSubmitExpiredAttempts.php      (scheduled in routes/console.php)
  Exceptions/BusinessRuleException.php                (renders itself: flash on web, 422 on API)
resources/js/         Alpine components: question-form, question-order, test-taker
docs/                 performance.md, TestForge.postman_collection.json
```

**Two layers of authorization.** `EnsureRole` answers *"may this user enter the teacher area?"*;
a Policy answers *"may this teacher edit **this** test?"* (owner, and still a draft). Policies are
attached declaratively — `->can('update', 'user')` on routes, or `HasMiddleware` on controllers.
There is no `Gate::before()` that lets admins do everything: admin rights are listed explicitly.

**Errors from business rules.** Services throw `BusinessRuleException` ("no attempts left",
"question is used in a test"). It renders itself — redirect back with a flash message on the web,
`422 {"message": …}` on the API — so controllers never need `try/catch`.

### Life of an attempt

```mermaid
sequenceDiagram
    actor S as Student
    participant W as AttemptController
    participant A as AttemptService
    participant Q as Queue (database)
    participant J as EvaluateAttempt
    participant C as ScoringService

    S->>W: POST /student/tests/{slug}/attempts
    W->>A: start() — lock student row, check window + max_attempts
    A-->>S: attempt (expires_at = now + duration)
    loop every answer change (fetch, JSON)
        S->>W: PUT /attempts/{id}/answers/{question}
        W->>A: saveAnswer() — lock attempt, reject if now > expires_at, upsert
    end
    S->>W: POST /attempts/{id}/submit
    W->>A: submit() — lock attempt, in_progress → submitted
    A->>Q: EvaluateAttempt (afterCommit)
    Q->>J: worker picks it up
    J->>C: evaluate() — lock attempt, score, write Result
    C-->>C: ResultPublished (after commit) → clear leaderboard cache
    Note over S,W: result page shows "being marked" and refreshes until evaluated
```

---

## Database

13 application tables (InnoDB, `utf8mb4_unicode_ci`), plus Laravel's `sessions`, `cache`, `jobs`,
`failed_jobs` and Sanctum's `personal_access_tokens`.

```mermaid
erDiagram
    users ||--o{ questions : "writes (created_by)"
    users ||--o{ tests : "builds (created_by)"
    users ||--o{ attempts : takes
    users ||--o{ results : has
    subjects ||--o{ topics : has
    topics ||--o{ questions : groups
    questions ||--o{ question_options : has
    questions }o--o{ tags : "question_tag"
    tests ||--o{ test_question : contains
    questions ||--o{ test_question : "used in"
    tests ||--o{ attempts : has
    attempts ||--o{ attempt_answers : has
    questions ||--o{ attempt_answers : answers
    attempt_answers ||--o{ attempt_answer_options : selects
    question_options ||--o{ attempt_answer_options : "selected as"
    attempts ||--|| results : "scored as"
    tests ||--o{ results : "denormalised"

    users {
        bigint id PK
        string email UK
        enum role
        bool is_active
    }
    subjects {
        bigint id PK
        string name UK
        string slug UK
    }
    topics {
        bigint id PK
        bigint subject_id FK
        string slug "unique per subject"
    }
    questions {
        bigint id PK
        bigint topic_id FK
        bigint created_by FK
        enum type
        enum difficulty
        decimal marks
        decimal negative_marks
        bool is_active
        datetime deleted_at
    }
    question_options {
        bigint id PK
        bigint question_id FK
        text option_text
        bool is_correct
        tinyint sort_order
    }
    tags {
        bigint id PK
        string name UK
        string slug UK
    }
    tests {
        bigint id PK
        bigint created_by FK
        string slug UK
        smallint duration_minutes
        decimal total_marks
        decimal pass_marks
        tinyint max_attempts
        enum status
        datetime starts_at
        datetime ends_at
        datetime deleted_at
    }
    test_question {
        bigint test_id FK
        bigint question_id FK
        smallint sort_order
        decimal marks "copied"
        decimal negative_marks "copied"
    }
    attempts {
        bigint id PK
        bigint test_id FK
        bigint user_id FK
        tinyint attempt_no
        enum status
        datetime started_at
        datetime expires_at
        datetime submitted_at
    }
    attempt_answers {
        bigint id PK
        bigint attempt_id FK
        bigint question_id FK
        bool is_correct
        decimal marks_awarded
    }
    attempt_answer_options {
        bigint attempt_answer_id FK
        bigint question_option_id FK
    }
    results {
        bigint id PK
        bigint attempt_id FK
        bigint test_id FK
        bigint user_id FK
        decimal score
        int time_taken_seconds
        bool is_passed
    }
```

Design choices worth defending:

- **Marks are copied into `test_question`** when a question is attached, so editing a question later never changes an old test or result.
- **`results.test_id` / `user_id` are denormalised** (they're also reachable through `attempts`) so the leaderboard reads one table.
- **Multi-select answers use a child table (`attempt_answer_options`), not JSON**, so accuracy queries stay plain JOINs, protected by foreign keys.
- **Duplicates are stopped by the database, not just code:** `unique(test_id, user_id, attempt_no)`, `unique(attempt_id, question_id)` (the auto-save upsert key), `unique(results.attempt_id)`.
- **Enum values are hard-coded in migrations**, not read from the PHP enums, so a migration never changes after it has run.
- `(status, expires_at)` index for the auto-submit command; covering indexes added after EXPLAIN — see [Performance](#performance).

---

## Raw SQL vs Eloquent

The four key queries are written as raw SQL in `LeaderboardService` and `AnalyticsService`, each with its
Query Builder / Eloquent version in a comment next to it. Raw SQL is used where the query **is** the point
(window functions, CTEs, a careful GROUP BY) and you want to read it, EXPLAIN it, and paste it into a SQL
console unchanged. Eloquent is used everywhere else, because relationships, scopes, eager loading and casts
make normal CRUD shorter and safer. Every raw query uses `?` bindings — never string-concatenated input.

### 1. Leaderboard — rank and percentile by each student's best attempt

```sql
WITH best AS (                              -- each student's attempts, best first
    SELECT r.user_id, r.score, r.time_taken_seconds,
           ROW_NUMBER() OVER (PARTITION BY r.user_id
                              ORDER BY r.score DESC, r.time_taken_seconds ASC, r.id ASC) AS rn
    FROM results r
    WHERE r.test_id = ?
),
ranked AS (                                 -- rank the best attempts against each other
    SELECT b.user_id, b.score, b.time_taken_seconds,
           RANK()         OVER (ORDER BY b.score DESC, b.time_taken_seconds ASC) AS rank_no,
           PERCENT_RANK() OVER (ORDER BY b.score ASC,  b.time_taken_seconds DESC) AS percent_rank,
           COUNT(*)       OVER () AS total_students
    FROM best b
    WHERE b.rn = 1
)
SELECT ranked.rank_no, ranked.user_id, u.name, ranked.score, ranked.time_taken_seconds,
       ROUND(ranked.percent_rank * 100, 2) AS percentile, ranked.total_students
FROM ranked JOIN users u ON u.id = ranked.user_id
ORDER BY ranked.rank_no, u.name
LIMIT 50;
```

- A window function can't be filtered in the `WHERE` of its own `SELECT`, hence the CTE: compute `rn`, then keep `rn = 1`.
- `RANK()` gives ties the same rank and skips (1, 1, 3). `PERCENT_RANK()` = (rank − 1) / (n − 1) in ascending order = the share of students you beat.
- `COUNT(*) OVER ()` gives the number of students *before* the `LIMIT`.
- Cached for 5 minutes per test (`leaderboard:test:{id}`); a student's own rank (`rankOf`) uses the same SQL with `WHERE ranked.user_id = ?` in the outer query, so it works outside the top 50.

```php
$best = DB::table('results')
    ->select('user_id', 'score', 'time_taken_seconds')
    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY score DESC, time_taken_seconds, id) AS rn')
    ->where('test_id', $testId);

$ranked = DB::query()->fromSub($best, 'b')->where('b.rn', 1)
    ->select('b.user_id', 'b.score', 'b.time_taken_seconds')
    ->selectRaw('RANK() OVER (ORDER BY b.score DESC, b.time_taken_seconds) AS rank_no')
    ->selectRaw('PERCENT_RANK() OVER (ORDER BY b.score, b.time_taken_seconds DESC) AS percent_rank')
    ->selectRaw('COUNT(*) OVER () AS total_students');

DB::query()->fromSub($ranked, 'ranked')
    ->join('users as u', 'u.id', '=', 'ranked.user_id')
    ->select('ranked.*', 'u.name')
    ->orderBy('rank_no')->orderBy('u.name')->limit(50)->get();
```

### 2. Subject-wise accuracy for a student (weakest first)

```sql
SELECT s.id AS subject_id, s.name AS subject,
       COUNT(*) AS attempted,
       SUM(aa.is_correct) AS correct,
       ROUND(AVG(aa.is_correct) * 100, 2) AS accuracy
FROM attempt_answers aa
JOIN attempts  a ON a.id = aa.attempt_id
JOIN questions q ON q.id = aa.question_id
JOIN topics    t ON t.id = q.topic_id
JOIN subjects  s ON s.id = t.subject_id
WHERE a.user_id = ? AND aa.is_correct IS NOT NULL
GROUP BY s.id, s.name
ORDER BY accuracy ASC, attempted DESC;
```

`AVG(is_correct)` works because a boolean is stored as 0/1. `is_correct IS NOT NULL` keeps answers that
are still waiting in the queue out of the numbers.

```php
DB::table('attempt_answers as aa')
    ->join('attempts as a', 'a.id', '=', 'aa.attempt_id')
    ->join('questions as q', 'q.id', '=', 'aa.question_id')
    ->join('topics as t', 't.id', '=', 'q.topic_id')
    ->join('subjects as s', 's.id', '=', 't.subject_id')
    ->where('a.user_id', $userId)->whereNotNull('aa.is_correct')
    ->groupBy('s.id', 's.name')
    ->select('s.id as subject_id', 's.name as subject')
    ->selectRaw('COUNT(*) AS attempted, SUM(aa.is_correct) AS correct')
    ->selectRaw('ROUND(AVG(aa.is_correct) * 100, 2) AS accuracy')
    ->orderBy('accuracy')->get();
```

### 3. Toughest questions in a test

```sql
SELECT q.id AS question_id, q.body, q.difficulty, s.answered, s.correct_pct
FROM (
    SELECT aa.question_id,
           COUNT(*) AS answered,
           ROUND(AVG(aa.is_correct) * 100, 2) AS correct_pct
    FROM attempt_answers aa
    JOIN attempts a ON a.id = aa.attempt_id
    WHERE a.test_id = ? AND aa.is_correct IS NOT NULL
    GROUP BY aa.question_id
    HAVING COUNT(*) >= 10                -- HAVING, not WHERE: it filters on an aggregate
) s
JOIN questions q ON q.id = s.question_id
ORDER BY s.correct_pct ASC, s.answered DESC
LIMIT 10;
```

*Aggregate first, join later:* the first version grouped by `q.id, q.body, q.difficulty`; `body` is a TEXT
column, so MySQL grouped ~268k rows on disk (1.6 s). Grouping by the integer id first takes 166 ms with
the covering index ([details](docs/performance.md)).

```php
$stats = DB::table('attempt_answers as aa')
    ->join('attempts as a', 'a.id', '=', 'aa.attempt_id')
    ->where('a.test_id', $testId)->whereNotNull('aa.is_correct')
    ->groupBy('aa.question_id')
    ->select('aa.question_id')
    ->selectRaw('COUNT(*) AS answered, ROUND(AVG(aa.is_correct) * 100, 2) AS correct_pct')
    ->havingRaw('COUNT(*) >= ?', [10]);

DB::query()->fromSub($stats, 's')
    ->join('questions as q', 'q.id', '=', 's.question_id')
    ->select('q.id as question_id', 'q.body', 'q.difficulty', 's.answered', 's.correct_pct')
    ->orderBy('s.correct_pct')->orderByDesc('s.answered')->limit(10)->get();
```

### 4. Published tests with no attempts

```sql
SELECT t.id, t.title, t.slug, t.published_at, u.name AS teacher
FROM tests t
JOIN users u ON u.id = t.created_by
LEFT JOIN attempts a ON a.test_id = t.id
WHERE t.status = 'published' AND t.deleted_at IS NULL
  AND a.id IS NULL                       -- no matching attempt row
ORDER BY t.published_at DESC;
```

The `LEFT JOIN` keeps every test; tests without attempts get `NULL` attempt columns, and `a.id IS NULL`
keeps exactly those (an anti-join — EXPLAIN shows `Not exists`).

```php
// Query Builder
DB::table('tests as t')
    ->join('users as u', 'u.id', '=', 't.created_by')
    ->leftJoin('attempts as a', 'a.test_id', '=', 't.id')
    ->where('t.status', 'published')->whereNull('t.deleted_at')->whereNull('a.id')
    ->select('t.id', 't.title', 't.slug', 't.published_at', 'u.name as teacher')
    ->orderByDesc('t.published_at')->get();

// Eloquent (NOT EXISTS sub-query)
Test::published()->doesntHave('attempts')->get();
```

---

## Concurrency

Everywhere a double click or two tabs could cause a problem, the code runs in `DB::transaction()`, takes a
row lock with `lockForUpdate()` (`SELECT … FOR UPDATE`), and **re-checks the rule under the lock**
(a policy check that ran a moment earlier may no longer be true).

| Action | Locked row | Why that row |
|---|---|---|
| Start an attempt | the **student's** `users` row | two "Start" clicks queue behind each other, while different students never wait for one another (locking the test would serialise everyone starting it) |
| Save an answer | the attempt | status and `expires_at` checked under the lock; then `upsert` on `unique(attempt_id, question_id)` |
| Submit | the attempt | `in_progress → submitted` happens exactly once, so exactly one scoring job is queued |
| Score (job) | the attempt | already `evaluated` → returns the existing result; `unique(results.attempt_id)` as a last guard |
| Attach / reorder / publish / archive | the test | "attach in one tab" and "publish in another" can't interleave |
| Auto-submit command | each attempt | a student who submits a second before the command reaches them is left alone |

The scoring job is queued with `afterCommit()` and `ResultPublished` implements `ShouldDispatchAfterCommit`,
so nothing outside the transaction ever sees work that might still roll back.

---

## REST API

Base URL `/api/v1`. Auth: Sanctum personal access tokens — send `Authorization: Bearer <token>`.
Errors are **always JSON**, even without an `Accept` header.

| Method | Endpoint | Who | Response |
|---|---|---|---|
| POST | `/auth/register` | public | **201** `{ token, token_type, user }` — always a student |
| POST | `/auth/login` | public | **200** `{ token, … }` · 422 wrong credentials · 403 deactivated |
| POST | `/auth/logout` | any | **204** — revokes the current token only |
| GET | `/me` | any | the current user |
| GET | `/tests` | student | available tests with attempts left (paginated) |
| GET | `/tests/{test}/leaderboard` | any | top 50 + `me` (your own rank) · 404 for drafts |
| GET | `/me/results` | student | your results, newest first (paginated) |
| GET | `/me/analytics` | student | subject accuracy (weakest first) + summary |

401 = no or invalid token, 403 = wrong role, 422 = validation or business rule. Login and register are
limited to 6 requests a minute; everything else to 60 per user per minute.

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -d email=student1@testforge.test -d password=password | jq -r .token)
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/v1/tests/1/leaderboard | jq
```

A Postman collection is in [`docs/TestForge.postman_collection.json`](docs/TestForge.postman_collection.json)
— its Login request stores the token for all the others.

---

## Tests

```bash
php artisan test        # 125 tests, ~25 s, against the testforge_testing MySQL database
```

Tests run on MySQL (not SQLite) because the code relies on MySQL behaviour: window functions, enums,
`lockForUpdate()`. Each test runs inside a rolled-back transaction (`RefreshDatabase`).

| File | Covers |
|---|---|
| `Unit/ScoringRuleTest` | the scoring rule alone: single, exact set, partial / extra options, normalisation |
| `Feature/AuthTest` | login redirect per role, the role × area access matrix, deactivated users, no role injection at registration |
| `Feature/AttemptFlowTest` | start, resume, max attempts, closed tests, upsert, invalid answers, **expired save rejected**, **double submit**, auto-submit command, no answer key in the page |
| `Feature/ScoringTest` | single, multiple (exact / partial / superset), **negative marks**, skipped, pivot marks vs edited question, idempotent scoring |
| `Feature/LeaderboardTest` | **order** (score, then time), best attempt per student, `RANK` ties, percentile, top 50 vs `rankOf`, cache invalidation |
| `Feature/ApiTest` | tokens, 201/204/401/403/404/422, JSON errors without `Accept`, pagination shape |
| `Feature/TestBuilderTest` | publish rules, frozen answer key, ownership, question-in-use |
| `Feature/QueryCountTest` | N+1 guard: query budgets per page, and counts that don't grow with the data |
| `Feature/Auth/*` | Breeze's own auth tests |

---

## Performance

See [docs/performance.md](docs/performance.md). In short:

- **N+1:** `Model::preventLazyLoading()` outside production, plus a test that fails if any list page goes over its query budget or its query count grows with the rows.
- **EXPLAIN at realistic size** (20k students, 535k answers): the toughest-questions query went from **1,629 ms to 166 ms** (rewrite + covering index); the leaderboard became index-only; "my results" lost its filesort.

---

## Deploying to cPanel

Shared hosting has no Redis, no Supervisor and usually no Node, so the app is built to run with **one cron entry**.

1. **Build assets on your machine** — `npm ci && npm run build` — and upload `public/build/` with the code
   (it's git-ignored, so either upload it separately or build in a deploy step).
2. **Upload / clone** the project *outside* `public_html`, then point the domain's document root at the project's
   `public/` folder (or symlink `public_html` → `public`).
3. **PHP 8.2+** in *MultiPHP Manager*; extensions `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`.
4. Create the MySQL database and user in cPanel, then `.env`:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://exams.example.com
   APP_TIMEZONE=Asia/Dhaka          # whatever your users' local time is

   DB_CONNECTION=mysql
   DB_DATABASE=cpaneluser_testforge
   DB_USERNAME=cpaneluser_tf
   DB_PASSWORD=...

   QUEUE_CONNECTION=database        # jobs live in the `jobs` table
   CACHE_STORE=database
   SESSION_DRIVER=database
   QUEUE_RUN_VIA_SCHEDULER=true     # see step 6
   ```

5. Over SSH (or *Terminal* in cPanel):

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force      # only if you want the demo data
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

   `storage/` and `bootstrap/cache/` must be writable by PHP.
6. **One cron job** (*cPanel → Cron Jobs*, every minute):

   ```
   * * * * * cd /home/cpaneluser/testforge && php artisan schedule:run >> /dev/null 2>&1
   ```

   The scheduler then runs `attempts:auto-submit` **and**, because `QUEUE_RUN_VIA_SCHEDULER=true`,
   `queue:work --stop-when-empty --max-time=55`: a worker that drains the `jobs` table and exits before the
   next minute (`withoutOverlapping()` stops runs piling up). Results therefore appear within about a minute
   of submitting — the result page shows "being marked" and refreshes itself until then.
7. Failed jobs land in `failed_jobs`; re-run them with `php artisan queue:retry all`.

On a VPS, use Supervisor to keep `php artisan queue:work` running instead, set `QUEUE_RUN_VIA_SCHEDULER=false`,
and optionally switch `QUEUE_CONNECTION` / `CACHE_STORE` to `redis`.
