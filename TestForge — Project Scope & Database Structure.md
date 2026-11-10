# TestForge — Project Scope & Database Structure

Nov 10, 2026 · @Arsad Islam

## Overview

TestForge is an online exam and test-series platform. Teachers build question banks and timed tests. Students attempt them, get auto-scored results, and see their rank and weak areas.

The project is a portfolio piece for PHP/Laravel interviews. Every feature is chosen to cover a common interview topic: relationships, auth, queues, caching, transactions, indexing and advanced SQL.

| Layer | Choice |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8.0 (window functions needed) |
| Auth | Laravel Sanctum (token-based API) |
| Queue & cache | Redis (database driver as fallback on shared hosting) |
| Admin panel | Blade + Tailwind, or Filament 4 |
| Testing | Pest |
| Docs | README + ER diagram + Postman collection |

## Scope

Version 1 covers three roles and seven modules. Anything outside this list is deferred so the project can be finished in 3–4 weeks.

**Roles**

| Role | Can do |
| --- | --- |
| Admin | Manage users, subjects, categories; view all reports |
| Teacher | Create questions, build tests, publish/unpublish, view results of own tests |
| Student | Browse published tests, attempt a test, view results, leaderboard and analytics |

**In scope (v1)**

1. **Auth & roles** — register, login, logout (Sanctum tokens); role-based access with Policies and middleware.
2. **Subjects & topics** — subject → topic hierarchy used to tag questions.
3. **Question bank** — single-choice and multi-choice MCQs, difficulty (easy/medium/hard), marks, negative marks, explanation, tags.
4. **Test builder** — title, duration, total marks, pass marks, start/end window, shuffle option, attempts allowed; attach questions in order.
5. **Test attempt** — start attempt, save answers one by one, server-side timer, submit; auto-submit when time ends (scheduled command).
6. **Result processing** — queued job scores the attempt, writes the result, fires a ResultPublished event.
7. **Leaderboard & analytics** — rank and percentile per test (cached), subject-wise accuracy per student, toughest questions per test.

**Out of scope (v1)**

- Payments and subscriptions
- Descriptive / file-upload answers
- Live proctoring, webcam, tab-switch detection
- Mobile app and a full React frontend (API only; Postman is the client)

**Non-functional goals**

- No N+1 queries on list endpoints (checked with Laravel Debugbar / `preventLazyLoading`).
- Every list endpoint paginated.
- One attempt per student per test enforced at DB level (unique key), not only in code.
- Feature tests for login, attempt flow, scoring and leaderboard.

## Database structure

The schema has 13 app tables, plus Laravel's defaults (`personal_access_tokens`, `jobs`, `failed_jobs`, `cache`, `sessions`). Engine InnoDB, charset `utf8mb4_unicode_ci`. All `*_id` columns are `foreignId()->constrained()`.

### 1. users

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint unsigned PK |  |
| name | varchar(100) |  |
| email | varchar(150) | unique |
| password | varchar(255) | hashed |
| role | enum('admin','teacher','student') | default 'student', indexed |
| is\_active | boolean | default true |
| email\_verified\_at, remember\_token, timestamps |  | Laravel default |

### 2. subjects

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| name | varchar(100) | unique |
| slug | varchar(120) | unique |
| timestamps |  |  |

### 3. topics

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| subject\_id | FK → subjects | cascade on delete |
| name | varchar(120) |  |
| slug | varchar(140) | unique(subject\_id, slug) |
| timestamps |  |  |

### 4. questions

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| topic\_id | FK → topics | restrict on delete |
| created\_by | FK → users | the teacher |
| type | enum('single','multiple') |  |
| body | text | question text |
| explanation | text, nullable | shown after result |
| difficulty | enum('easy','medium','hard') |  |
| marks | decimal(5,2) | default 1.00 |
| negative\_marks | decimal(5,2) | default 0.00 |
| is\_active | boolean | default true |
| timestamps, deleted\_at |  | soft deletes |

Indexes: (topic\_id, difficulty), (created\_by).

### 5. question\_options

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| question\_id | FK → questions | cascade on delete |
| option\_text | text |  |
| is\_correct | boolean | default false |
| sort\_order | tinyint unsigned |  |

Index: (question\_id).

### 6. tags and 7. question\_tag

| Table | Columns | Notes |
| --- | --- | --- |
| tags | id, name (unique), slug (unique), timestamps |  |
| question\_tag | question\_id FK, tag\_id FK | composite PK (question\_id, tag\_id), both cascade |

### 8. tests

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| created\_by | FK → users |  |
| title | varchar(200) |  |
| slug | varchar(220) | unique |
| description | text, nullable |  |
| duration\_minutes | smallint unsigned |  |
| total\_marks | decimal(6,2) | recalculated when questions change |
| pass\_marks | decimal(6,2) |  |
| max\_attempts | tinyint unsigned | default 1 |
| shuffle\_questions | boolean | default false |
| status | enum('draft','published','archived') | default 'draft' |
| starts\_at, ends\_at | datetime, nullable | availability window |
| published\_at | datetime, nullable |  |
| timestamps, deleted\_at |  | soft deletes |

Index: (status, starts\_at).

### 9. test\_question (pivot with data)

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| test\_id | FK → tests | cascade |
| question\_id | FK → questions | restrict |
| sort\_order | smallint unsigned |  |
| marks | decimal(5,2) | copied from question when attached |
| negative\_marks | decimal(5,2) | copied, so later edits don't change old results |

Unique: (test\_id, question\_id).

### 10. attempts

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| test\_id | FK → tests |  |
| user\_id | FK → users | the student |
| attempt\_no | tinyint unsigned | 1, 2, … up to max\_attempts |
| status | enum('in\_progress','submitted','auto\_submitted','evaluated') |  |
| started\_at | datetime |  |
| expires\_at | datetime | started\_at + duration |
| submitted\_at | datetime, nullable |  |
| ip\_address | varchar(45), nullable |  |
| timestamps |  |  |

Unique: (test\_id, user\_id, attempt\_no). Index: (status, expires\_at) for the auto-submit command.

### 11. attempt\_answers and 12. attempt\_answer\_options

| Table | Columns | Notes |
| --- | --- | --- |
| attempt\_answers | id, attempt\_id FK (cascade), question\_id FK, is\_correct (bool, nullable until scored), marks\_awarded decimal(5,2) default 0, answered\_at datetime, timestamps | unique (attempt\_id, question\_id) — upsert on each save |
| attempt\_answer\_options | attempt\_answer\_id FK (cascade), question\_option\_id FK | composite PK; one row per option picked (handles multi-select) |

### 13. results

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint PK |  |
| attempt\_id | FK → attempts | unique (one result per attempt) |
| test\_id | FK → tests | denormalised for fast leaderboard |
| user\_id | FK → users | denormalised |
| score | decimal(7,2) |  |
| correct\_count, wrong\_count, skipped\_count | smallint unsigned |  |
| accuracy | decimal(5,2) | correct / attempted × 100 |
| time\_taken\_seconds | int unsigned |  |
| is\_passed | boolean |  |
| evaluated\_at | datetime |  |
| timestamps |  |  |

Indexes: (test\_id, score), (user\_id).

**Design choices to explain in the interview**

- Marks are copied into `test_question` so editing a question never changes past results.
- `test_id` and `user_id` are repeated in `results` on purpose: the leaderboard query reads one table instead of joining through `attempts`.
- Multi-select answers use a child table, not JSON, so accuracy queries stay plain SQL with JOINs.
- The unique keys on `attempts` and `attempt_answers` stop duplicates even if two requests arrive together.

## Relationships & Eloquent models

Ten models cover the 13 tables; `question_tag`, `test_question` and `attempt_answer_options` are pivots with no model of their own.

| Model | Relationship | Method |
| --- | --- | --- |
| User | hasMany Question (as teacher) | `questions()` via created\_by |
| User | hasMany Test (as teacher) | `tests()` via created\_by |
| User | hasMany Attempt, hasMany Result | `attempts()`, `results()` |
| Subject | hasMany Topic | `topics()` |
| Subject | hasManyThrough Question | `questions()` through Topic |
| Topic | belongsTo Subject, hasMany Question | `subject()`, `questions()` |
| Question | belongsTo Topic, belongsTo User (creator) | `topic()`, `creator()` |
| Question | hasMany QuestionOption | `options()` |
| Question | belongsToMany Tag | `tags()` |
| Question | belongsToMany Test (pivot test\_question) | `tests()->withPivot('sort_order','marks','negative_marks')` |
| Test | belongsToMany Question | `questions()->withPivot(...)->orderByPivot('sort_order')` |
| Test | hasMany Attempt, hasMany Result | `attempts()`, `results()` |
| Attempt | belongsTo Test, belongsTo User | `test()`, `student()` |
| Attempt | hasMany AttemptAnswer, hasOne Result | `answers()`, `result()` |
| AttemptAnswer | belongsTo Attempt, belongsTo Question | `attempt()`, `question()` |
| AttemptAnswer | belongsToMany QuestionOption (pivot attempt\_answer\_options) | `selectedOptions()` |
| Result | belongsTo Attempt, Test, User | `attempt()`, `test()`, `student()` |

**Model extras worth showing**

- Enums as PHP 8.1 backed enums: `UserRole`, `QuestionType`, `Difficulty`, `TestStatus`, `AttemptStatus`, cast in `casts()`.
- Scopes: `Test::published()`, `Test::available()` (inside the start/end window), `Question::active()`.
- `SoftDeletes` on Question and Test.
- `Model::preventLazyLoading()` in `AppServiceProvider` for local, to catch N+1 early.

## Key SQL queries to practise

Write each query in raw SQL first, run `EXPLAIN` on it, then rewrite it in Eloquent / Query Builder. Keep both versions in the README.

**1. Leaderboard with rank and percentile (window functions)**

```sql
SELECT u.name, r.score, r.time_taken_seconds,
       RANK() OVER (ORDER BY r.score DESC, r.time_taken_seconds ASC) AS rank_no,
       ROUND(PERCENT_RANK() OVER (ORDER BY r.score) * 100, 2) AS percentile
FROM results r
JOIN users u ON u.id = r.user_id
WHERE r.test_id = ?
ORDER BY rank_no
LIMIT 50;
```

**2. Subject-wise accuracy for one student**

```sql
SELECT s.name AS subject,
       COUNT(aa.id) AS attempted,
       SUM(aa.is_correct) AS correct,
       ROUND(SUM(aa.is_correct) / COUNT(aa.id) * 100, 2) AS accuracy
FROM attempt_answers aa
JOIN attempts a  ON a.id = aa.attempt_id
JOIN questions q ON q.id = aa.question_id
JOIN topics t    ON t.id = q.topic_id
JOIN subjects s  ON s.id = t.subject_id
WHERE a.user_id = ?
GROUP BY s.id, s.name
ORDER BY accuracy ASC;
```

**3. Toughest questions in a test (lowest correct rate, minimum 10 answers)**

```sql
SELECT q.id, LEFT(q.body, 80) AS question,
       COUNT(*) AS answered,
       ROUND(AVG(aa.is_correct) * 100, 2) AS correct_pct
FROM attempt_answers aa
JOIN attempts a  ON a.id = aa.attempt_id
JOIN questions q ON q.id = aa.question_id
WHERE a.test_id = ?
GROUP BY q.id, q.body
HAVING COUNT(*) >= 10
ORDER BY correct_pct ASC
LIMIT 10;
```

**4. Each student's best score per test (subquery / CTE)**

```sql
WITH ranked AS (
  SELECT r.*, ROW_NUMBER() OVER (PARTITION BY r.user_id ORDER BY r.score DESC) AS rn
  FROM results r WHERE r.test_id = ?
)
SELECT user_id, score FROM ranked WHERE rn = 1;
```

**5. Expired attempts to auto-submit (uses the status, expires\_at index)**

```sql
SELECT id FROM attempts
WHERE status = 'in_progress' AND expires_at <= NOW();
```

**6. Tests with no attempts yet (LEFT JOIN … IS NULL)**

```sql
SELECT t.id, t.title
FROM tests t
LEFT JOIN attempts a ON a.test_id = t.id
WHERE t.status = 'published' AND a.id IS NULL;
```

Also practise: the scoring logic inside `DB::transaction()` with `lockForUpdate()` on the attempt row, so a double submit can't score twice.

## API endpoints & folder structure

All routes live in `routes/api.php` under the `/api/v1` prefix. Everything except register and login needs `auth:sanctum`.

| Method | Endpoint | Role | Purpose |
| --- | --- | --- | --- |
| POST | /auth/register, /auth/login | public | Get a Sanctum token |
| POST | /auth/logout | any | Revoke token |
| GET/POST/PUT/DELETE | /subjects, /topics | admin | Manage subjects and topics |
| GET/POST/PUT/DELETE | /questions | teacher | Question bank CRUD (filter by topic, difficulty, tag) |
| GET/POST/PUT/DELETE | /tests | teacher | Test CRUD |
| POST | /tests/{test}/questions | teacher | Attach questions with order |
| PATCH | /tests/{test}/publish | teacher | Publish (validates it has questions) |
| GET | /student/tests | student | Available published tests |
| POST | /tests/{test}/attempts | student | Start an attempt |
| PUT | /attempts/{attempt}/answers/{question} | student | Save or change one answer (upsert) |
| POST | /attempts/{attempt}/submit | student | Submit; dispatches EvaluateAttempt job |
| GET | /attempts/{attempt}/result | student | Result with explanations |
| GET | /tests/{test}/leaderboard | any | Ranked list (cached 5 min) |
| GET | /student/analytics | student | Subject-wise accuracy |
| GET | /tests/{test}/analytics | teacher | Toughest questions, average score |

**Folder structure (beyond the Laravel 12 defaults)**

```
app/
  Enums/            UserRole, QuestionType, Difficulty, TestStatus, AttemptStatus
  Http/
    Controllers/Api/V1/   AuthController, QuestionController, TestController,
                          AttemptController, LeaderboardController, AnalyticsController
    Requests/       StoreQuestionRequest, StoreTestRequest, SaveAnswerRequest, ...
    Resources/      QuestionResource, TestResource, ResultResource, ...
    Middleware/     EnsureRole
  Models/
  Policies/         QuestionPolicy, TestPolicy, AttemptPolicy
  Services/         ScoringService, LeaderboardService, AttemptService
  Jobs/             EvaluateAttempt
  Events/           ResultPublished
  Listeners/        ClearLeaderboardCache, NotifyStudent
  Console/Commands/ AutoSubmitExpiredAttempts   (scheduled every minute)
database/
  migrations/  factories/  seeders/   (seed 5 subjects, 200 questions, 3 tests, 50 students)
tests/Feature/      AuthTest, AttemptFlowTest, ScoringTest, LeaderboardTest
```

## Build plan

The build runs about four weeks part-time, one module at a time. Tick each item off as you finish it.

**Week 1 — Foundation**

- [ ] `laravel new testforge`, install Sanctum (`php artisan install:api`), set up MySQL and Redis
- [ ] All migrations, models, enums, relationships
- [ ] Factories and seeders with realistic data
- [ ] Draw the ER diagram (dbdiagram.io or MySQL Workbench)

**Week 2 — Auth and content**

- [ ] Register, login, logout, EnsureRole middleware, Policies
- [ ] Subjects/topics CRUD, question bank CRUD with Form Requests and API Resources
- [ ] Test builder: create, attach questions, publish

**Week 3 — Attempt and scoring**

- [ ] Start attempt, save answers (upsert), submit
- [ ] EvaluateAttempt job + ScoringService inside a transaction with `lockForUpdate()`
- [ ] AutoSubmitExpiredAttempts command, registered in `routes/console.php` schedule
- [ ] ResultPublished event and listeners

**Week 4 — Reports, tests, launch**

- [ ] Leaderboard (raw SQL + cache) and both analytics endpoints
- [ ] Pest feature tests for the attempt flow and scoring
- [ ] Check N+1 and add any missing indexes using `EXPLAIN`
- [ ] README (setup, ER diagram, raw SQL vs Eloquent), Postman collection, deploy to cPanel, push to GitHub
