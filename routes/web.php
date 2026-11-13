<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Web\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Web\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Web\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Web\Admin\TestSeriesController as AdminTestSeriesController;
use App\Http\Controllers\Web\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\DashboardRedirectController;
use App\Http\Controllers\Web\Student\AnalyticsController as StudentAnalyticsController;
use App\Http\Controllers\Web\Student\AttemptController as StudentAttemptController;
use App\Http\Controllers\Web\Student\CheckoutController as StudentCheckoutController;
use App\Http\Controllers\Web\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Web\Student\LeaderboardController as StudentLeaderboardController;
use App\Http\Controllers\Web\Student\PurchaseController as StudentPurchaseController;
use App\Http\Controllers\Web\Student\ResultController as StudentResultController;
use App\Http\Controllers\Web\Student\SeriesController as StudentSeriesController;
use App\Http\Controllers\Web\Student\TestController as StudentTestController;
use App\Http\Controllers\Web\Teacher\AnalyticsController as TeacherAnalyticsController;
use App\Http\Controllers\Web\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Web\Teacher\QuestionController as TeacherQuestionController;
use App\Http\Controllers\Web\Teacher\QuestionImportController as TeacherQuestionImportController;
use App\Http\Controllers\Web\Teacher\TestController as TeacherTestController;
use App\Http\Controllers\Web\Teacher\TestQuestionController as TeacherTestQuestionController;
use Illuminate\Support\Facades\Route;

// ---- Public site ----
Route::get('/', HomeController::class)->name('home');
Route::view('features', 'public.features')->name('features');
Route::get('contact', [ContactController::class, 'create'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:3,10');
Route::view('terms', 'legal.terms')->name('legal.terms');
Route::view('privacy', 'legal.privacy')->name('legal.privacy');
Route::view('refunds', 'legal.refunds')->name('legal.refunds');

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

        Route::resource('series', AdminTestSeriesController::class)->except('show')->parameters(['series' => 'series']);
        Route::resource('plans', AdminPlanController::class)->except('show');
        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
        Route::patch('messages/{message}/read', [AdminContactMessageController::class, 'markRead'])->name('messages.read');

        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('reports/tests/{test}', [AdminReportController::class, 'show'])->name('reports.show');
    });

    // ---- Teacher area: /teacher/*, route names teacher.* ----
    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher')->group(function () {
        Route::get('/', TeacherDashboardController::class)->name('dashboard');

        // Bulk import — declared BEFORE the resource, so "questions/import" isn't read as {question}.
        Route::controller(TeacherQuestionImportController::class)->prefix('questions/import')->name('questions.import.')->group(function () {
            Route::get('/', 'create')->name('create');
            Route::get('template', 'template')->name('template');
            Route::post('/', 'store')->name('store');
            Route::get('{import}', 'show')->whereUuid('import')->name('show');
            Route::post('{import}', 'confirm')->whereUuid('import')->name('confirm');
            Route::delete('{import}', 'destroy')->whereUuid('import')->name('destroy');
        });

        Route::resource('questions', TeacherQuestionController::class);

        Route::resource('tests', TeacherTestController::class);
        Route::patch('tests/{test}/publish', [TeacherTestController::class, 'publish'])->name('tests.publish');
        Route::patch('tests/{test}/archive', [TeacherTestController::class, 'archive'])->name('tests.archive');
        Route::get('tests/{test}/analytics', [TeacherAnalyticsController::class, 'show'])->name('tests.analytics');

        // Test builder. scopeBindings(): {question} is looked up through $test->questions(),
        // so a question that isn't in this test is a 404.
        Route::prefix('tests/{test}/questions')->name('tests.questions.')->scopeBindings()->group(function () {
            Route::get('/', [TeacherTestQuestionController::class, 'index'])->name('index');
            Route::post('/', [TeacherTestQuestionController::class, 'store'])->name('store');
            Route::put('order', [TeacherTestQuestionController::class, 'reorder'])->name('reorder');
            Route::post('generate', [TeacherTestQuestionController::class, 'generate'])->name('generate');
            Route::post('{question}/swap', [TeacherTestQuestionController::class, 'swap'])->name('swap');
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
        Route::get('attempts/{attempt}/result', [StudentResultController::class, 'show'])->name('attempts.result');

        Route::get('tests/{test:slug}/leaderboard', [StudentLeaderboardController::class, 'show'])->name('tests.leaderboard');
        Route::get('analytics', StudentAnalyticsController::class)->name('analytics');

        // Paid series, All-Access passes and Razorpay checkout
        Route::get('series', [StudentSeriesController::class, 'index'])->name('series.index');
        Route::get('series/{series:slug}', [StudentSeriesController::class, 'show'])->name('series.show');
        Route::post('checkout', [StudentCheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
        Route::post('checkout/verify', [StudentCheckoutController::class, 'verify'])->name('checkout.verify');
        Route::get('checkout/{payment}', [StudentCheckoutController::class, 'show'])->name('checkout.show')->can('view', 'payment');
        Route::get('purchases', [StudentPurchaseController::class, 'index'])->name('purchases');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
