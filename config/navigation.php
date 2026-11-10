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
|
| Links are added here as each section of the app is built.
|
*/

return [
    'admin' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
        ['label' => 'Subjects', 'route' => 'admin.subjects.index', 'active' => 'admin.subjects.*'],
        ['label' => 'Topics', 'route' => 'admin.topics.index', 'active' => 'admin.topics.*'],
    ],

    'teacher' => [
        ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'active' => 'teacher.dashboard'],
        ['label' => 'Question bank', 'route' => 'teacher.questions.index', 'active' => 'teacher.questions.*'],
    ],

    'student' => [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'active' => 'student.dashboard'],
    ],
];
