<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\DashboardRedirectController;
use App\Http\Controllers\Web\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Web\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Shared entry point: sends each user to their own role's dashboard.
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    // ---- Admin area: /admin/*, route names admin.* ----
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
    });

    // ---- Teacher area: /teacher/*, route names teacher.* ----
    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher')->group(function () {
        Route::get('/', TeacherDashboardController::class)->name('dashboard');
    });

    // ---- Student area: /student/*, route names student.* ----
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
