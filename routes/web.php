<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Web\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\DashboardRedirectController;
use App\Http\Controllers\Web\Student\AttemptController as StudentAttemptController;
use App\Http\Controllers\Web\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Web\Student\TestController as StudentTestController;
use App\Http\Controllers\Web\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Web\Teacher\QuestionController as TeacherQuestionController;
use App\Http\Controllers\Web\Teacher\TestController as TeacherTestController;
use App\Http\Controllers\Web\Teacher\TestQuestionController as TeacherTestQuestionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Shared entry point: sends each user to their own role's dashboard.
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    // ---- Admin area: /admin/*, route names admin.* ----
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        // ->can() runs UserPolicy@update before the controller: admin only, never on themselves.
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->name('users.role')->can('update', 'user');
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])
            ->name('users.status')->can('update', 'user');

        Route::resource('subjects', AdminSubjectController::class)->except('show');
        Route::resource('topics', AdminTopicController::class)->except('show');
    });

    // ---- Teacher area: /teacher/*, route names teacher.* ----
    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher')->group(function () {
        Route::get('/', TeacherDashboardController::class)->name('dashboard');

        Route::resource('questions', TeacherQuestionController::class);

        Route::resource('tests', TeacherTestController::class);
        Route::patch('tests/{test}/publish', [TeacherTestController::class, 'publish'])->name('tests.publish');
        Route::patch('tests/{test}/archive', [TeacherTestController::class, 'archive'])->name('tests.archive');

        // Test builder. scopeBindings(): {question} is looked up through $test->questions(),
        // so a question that isn't in this test is a 404.
        Route::prefix('tests/{test}/questions')->name('tests.questions.')->scopeBindings()->group(function () {
            Route::get('/', [TeacherTestQuestionController::class, 'index'])->name('index');
            Route::post('/', [TeacherTestQuestionController::class, 'store'])->name('store');
            Route::put('order', [TeacherTestQuestionController::class, 'reorder'])->name('reorder');
            Route::delete('{question}', [TeacherTestQuestionController::class, 'destroy'])->name('destroy');
        });
    });

    // ---- Student area: /student/*, route names student.* ----
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');

        Route::get('tests', [StudentTestController::class, 'index'])->name('tests.index');
        Route::get('tests/{slug}', [StudentTestController::class, 'show'])->name('tests.show');
        Route::post('tests/{test:slug}/attempts', [StudentAttemptController::class, 'store'])->name('attempts.store');

        Route::get('attempts/{attempt}', [StudentAttemptController::class, 'show'])->name('attempts.show');
        // Auto-save is called repeatedly by the browser, so it gets its own rate limit (per user per minute).
        Route::put('attempts/{attempt}/answers/{question}', [StudentAttemptController::class, 'saveAnswer'])
            ->name('attempts.answers.save')->middleware('throttle:120,1');
        Route::post('attempts/{attempt}/submit', [StudentAttemptController::class, 'submit'])->name('attempts.submit');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
