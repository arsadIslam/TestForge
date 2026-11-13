<?php

/*
|--------------------------------------------------------------------------
| Per-role navigation
|--------------------------------------------------------------------------
|
| Rendered by layouts/navigation.blade.php. Each link has:
|   label  - text shown in the menu
|   route  - named route the link points to
|   active - route-name pattern that highlights the link (routeIs() syntax)
|   icon   - a name from components/icon.blade.php
|
| Links are added here as each section of the app is built.
|
*/

return [
    'admin' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
        ['label' => 'Subjects', 'route' => 'admin.subjects.index', 'active' => 'admin.subjects.*', 'icon' => 'book'],
        ['label' => 'Topics', 'route' => 'admin.topics.index', 'active' => 'admin.topics.*', 'icon' => 'tag'],
        ['label' => 'Series', 'route' => 'admin.series.index', 'active' => 'admin.series.*', 'icon' => 'stack'],
        ['label' => 'Passes', 'route' => 'admin.plans.index', 'active' => 'admin.plans.*', 'icon' => 'ticket'],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'active' => 'admin.payments.*', 'icon' => 'card'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'icon' => 'chart'],
        ['label' => 'Messages', 'route' => 'admin.messages.index', 'active' => 'admin.messages.*', 'icon' => 'inbox'],
    ],

    'teacher' => [
        ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'active' => 'teacher.dashboard', 'icon' => 'home'],
        ['label' => 'Question bank', 'route' => 'teacher.questions.index', 'active' => 'teacher.questions.*', 'icon' => 'question'],
        ['label' => 'Tests', 'route' => 'teacher.tests.index', 'active' => 'teacher.tests.*', 'icon' => 'clipboard'],
    ],

    'student' => [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'active' => 'student.dashboard', 'icon' => 'home'],
        ['label' => 'Tests', 'route' => 'student.tests.index', 'active' => 'student.tests.*', 'icon' => 'clipboard'],
        ['label' => 'Test series', 'route' => 'student.series.index', 'active' => 'student.series.*', 'icon' => 'stack'],
        ['label' => 'My analytics', 'route' => 'student.analytics', 'active' => 'student.analytics', 'icon' => 'chart'],
        ['label' => 'Purchases', 'route' => 'student.purchases', 'active' => 'student.purchases', 'icon' => 'bag'],
    ],
];
