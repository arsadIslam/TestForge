# Performance pass (Step 13)

Two questions: **does any page run an N+1?** and **do the key queries use indexes at a realistic size?**

## 1. N+1 queries

Two guards, both in the code base:

1. `Model::preventLazyLoading()` is on everywhere except production (`AppServiceProvider`).
   Lazy-loading a relation on a model that came from a collection throws
   `LazyLoadingViolationException`, so an N+1 in a list fails loudly in development and in tests.
2. `tests/Feature/QueryCountTest.php` renders every list page on the full demo data and checks:
   - each page stays inside a small query budget (4–11 queries), and
   - the count is **identical** after adding 5 more tests and 30 more questions — it doesn't grow with the rows.

Typical counts on the demo data:

| Page | Queries |
|---|---|
| `/admin/users` | 2 |
| `/admin/topics` | 4 |
| `/teacher/questions` (15 per page, with topic, subject, tags, author) | 9 |
| `/teacher/tests/{id}` (20 questions with topic and subject) | 5–6 |
| `/student/tests` | 4 |
| test-taking screen (15 questions with options) | 6 |
| `/student/attempts/{id}/result` | 9 |

The tools used: `with()` / `load()` for relations, `withCount()` / `withAvg()` for numbers (one sub-select,
not a query per row), `toBase()` + `GROUP BY` for dashboard counts.

## 2. EXPLAIN at realistic size

The demo seed has only 90 results, and at that size MySQL scans whole tables because it's cheaper, so
EXPLAIN tells you nothing. The queries were measured on a separate `testforge_perf` database:
the normal seed **plus 20,000 students, 26,756 attempts and results on one test, and 535,095 answers**
(script at the end). Timings are the median of 5 runs on MariaDB 10.4, on a laptop.

### Before → after

| Query | Before | After | What changed |
|---|---:|---:|---|
| #3 Toughest questions in a test | 1,629 ms | **166 ms** | rewrite (aggregate first, join later) + covering index |
| #1 Leaderboard top 50 (cache cleared) | 52 ms | 44 ms | covering index |
| #1b Leaderboard — one student's rank | 36 ms | 30 ms | covering index |
| My results, newest first (API) | filesort | no filesort | `(user_id, evaluated_at)` |
| #2 Subject accuracy for a student | 0.4 ms | 0.4 ms | already index-driven; now index-only on answers |
| #4 Published tests with no attempts | 0.2 ms | 0.2 ms | `LEFT JOIN … IS NULL` → `Not exists` in the plan |
| Auto-submit candidates | 0.3 ms | 0.3 ms | `(status, expires_at)` from Step 2: `range`, `Using index` |

### What EXPLAIN showed, and the fixes

**#3 Toughest questions — 1.6 s.** The first version did
`GROUP BY q.id, q.body, q.difficulty`. `body` is a `TEXT` column, so MySQL built its temporary
table on disk and grouped/sorted ~268k answer rows by text.

*Fix 1 — aggregate first, join later.* Group the answers by the integer `question_id` in a derived
table, keep only the rows that pass `HAVING COUNT(*) >= 10`, and join `questions` just for those
(see `AnalyticsService::toughestQuestions()`). 1,629 ms → 524 ms.

*Fix 2 — covering index* `attempt_answers (attempt_id, question_id, is_correct)`. The query needs only
those three columns of each answer; with all three in the index, InnoDB never reads the table row
(`Using index` in the plan). 524 ms → 166 ms.

```
DERIVED  a   ref  attempts_test_id_user_id_attempt_no_unique   rows=13481  Using index
DERIVED  aa  ref  attempt_answers_scoring_covering_index       rows=10     Using where; Using index
```

**#1 Leaderboard.** The CTE reads one test's results through `(test_id, score)` but also needs
`user_id` and `time_taken_seconds`, so every row meant a lookup in the table. Covering index
`results (test_id, user_id, score, time_taken_seconds)` makes it index-only:

```
DERIVED  r  ref  results_leaderboard_covering_index  rows=13359  Using index; Using temporary
```

The remaining cost is the window-function sort itself, which is why the top 50 is cached for 5 minutes
and the cache is cleared by the `ResultPublished` listener.

**My results (newest first).** `results (user_id)` found the rows but then sorted them (`Using filesort`).
Replaced by `results (user_id, evaluated_at)`, which serves the filter and the order. The new index is
created *before* the old one is dropped: the `user_id` foreign key needs an index starting with
`user_id` at every moment (MySQL error 1553 otherwise).

All three indexes are in `database/migrations/2026_11_10_130000_add_performance_indexes.php` — a new
migration rather than an edit, because migrations that may already have run on a server are never changed.

### Deliberately not changed

- **Teacher dashboard "latest results"** (~22 ms at 26k results): `WHERE test_id IN (…teacher's tests…)
  ORDER BY evaluated_at LIMIT 8`. An index can't give one global order across several `test_id`s, so
  MySQL still sorts. Fine at this size; the next step would be caching it or a `(test_id, evaluated_at)`
  index with a `UNION` of per-test top-8s.
- **`tests` scanned with `type=ALL`** in #4: the table has 3 rows, so a scan is the cheapest plan. The
  `(status, starts_at)` index is there for when it grows.
- **Test summary** (~17 ms): reads one test's results through `(test_id, score)`. Adding `is_passed` etc.
  to make it covering isn't worth the extra index for a page only teachers open.
- Extra indexes cost some INSERT/UPDATE time. `results` gets one write per attempt and `attempt_answers`
  one UPDATE per answer at scoring time, so read-heavy covering indexes are a good trade here.

## Reproducing the numbers

```bash
mysql -uroot -e "CREATE DATABASE testforge_perf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
DB_DATABASE=testforge_perf php artisan migrate:fresh --seed
mysql -uroot testforge_perf < volume.sql      # the script below
```

```sql
-- numbers 1..100000 from a cross join of digits (no recursive-CTE depth limits)
CREATE TEMPORARY TABLE d (n TINYINT); INSERT INTO d VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9);
CREATE TEMPORARY TABLE seq (n INT PRIMARY KEY)
  SELECT a.n + b.n*10 + c.n*100 + e.n*1000 + f.n*10000 + 1 AS n FROM d a, d b, d c, d e, d f;

SET @pw = (SELECT password FROM users LIMIT 1);
INSERT INTO users (name, email, password, role, is_active, created_at, updated_at)
  SELECT CONCAT('Perf Student ', n), CONCAT('perf', n, '@example.test'), @pw, 'student', 1, NOW(), NOW()
  FROM seq WHERE n <= 20000;

-- attempt 1 for everyone, attempt 2 for a third of them, on test 1
INSERT INTO attempts (test_id, user_id, attempt_no, status, started_at, expires_at, submitted_at, created_at, updated_at)
  SELECT 1, u.id, 1, 'evaluated', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 30 MINUTE,
         NOW() - INTERVAL 3 DAY + INTERVAL (300 + u.id % 1500) SECOND, NOW(), NOW()
  FROM users u WHERE u.email LIKE 'perf%';
INSERT INTO attempts (test_id, user_id, attempt_no, status, started_at, expires_at, submitted_at, created_at, updated_at)
  SELECT 1, u.id, 2, 'evaluated', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY + INTERVAL 30 MINUTE,
         NOW() - INTERVAL 1 DAY + INTERVAL (300 + (u.id * 7) % 1500) SECOND, NOW(), NOW()
  FROM users u WHERE u.email LIKE 'perf%' AND u.id % 3 = 0;

INSERT INTO attempt_answers (attempt_id, question_id, is_correct, marks_awarded, answered_at, created_at, updated_at)
  SELECT a.id, tq.question_id, RAND() < 0.6, 0, a.started_at, NOW(), NOW()
  FROM attempts a JOIN test_question tq ON tq.test_id = a.test_id JOIN users u ON u.id = a.user_id
  WHERE u.email LIKE 'perf%';

INSERT INTO results (attempt_id, test_id, user_id, score, correct_count, wrong_count, skipped_count, accuracy,
                     time_taken_seconds, is_passed, evaluated_at, created_at, updated_at)
  SELECT a.id, a.test_id, a.user_id, ROUND(RAND() * 40 - 2, 2), 12, 8, 0, 60.00,
         TIMESTAMPDIFF(SECOND, a.started_at, a.submitted_at), RAND() < 0.7, a.submitted_at, NOW(), NOW()
  FROM attempts a JOIN users u ON u.id = a.user_id WHERE u.email LIKE 'perf%';

ANALYZE TABLE users, attempts, attempt_answers, results;
```

Then prefix any query with `EXPLAIN` (or use `ANALYZE` on MariaDB / `EXPLAIN ANALYZE` on MySQL 8 for real row counts).
