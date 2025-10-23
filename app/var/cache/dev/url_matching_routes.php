<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/doc' => [[['_route' => 'app.swagger_ui', '_controller' => 'nelmio_api_doc.controller.swagger_ui'], null, ['GET' => 0], null, false, false, null]],
        '/clocks' => [
            [['_route' => 'clock_create', '_controller' => 'App\\Controller\\ClockController::createClock'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'user_team_clocks', '_controller' => 'App\\Controller\\ClockController::getUserTeamClocks'], null, ['GET' => 0], null, false, false, null],
        ],
        '/reports/team-averages' => [[['_route' => 'reports_team_averages', '_controller' => 'App\\Controller\\TeamReportController::getTeamsAverageWorkTime'], null, ['GET' => 0], null, false, false, null]],
        '/teams' => [
            [['_route' => 'team_create', '_controller' => 'App\\Controller\\TeamsManagementController::createTeam'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'team_list', '_controller' => 'App\\Controller\\TeamsManagementController::listTeams'], null, ['GET' => 0], null, false, false, null],
        ],
        '/reports' => [[['_route' => 'reports_global', '_controller' => 'App\\Controller\\KpiReportController::getGlobalReport'], null, ['GET' => 0], null, false, false, null]],
        '/reports/filter' => [[['_route' => 'reports_global_filtered', '_controller' => 'App\\Controller\\KpiReportController::getGlobalReportFiltered'], null, ['GET' => 0], null, false, false, null]],
        '/' => [[['_route' => 'homepage', '_controller' => 'App\\Controller\\UsersManagementController::index'], null, null, null, false, false, null]],
        '/users' => [
            [['_route' => 'user_create', '_controller' => 'App\\Controller\\UsersManagementController::createUser'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'user_display', '_controller' => 'App\\Controller\\UsersManagementController::displayUser'], null, ['GET' => 0], null, false, false, null],
        ],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/clocks/([^/]++)(?'
                    .'|(*:61)'
                .')'
                .'|/teams/([^/]++)(?'
                    .'|(*:87)'
                .')'
                .'|/reports/(?'
                    .'|employee/([^/]++)/daily\\-work\\-time(*:142)'
                    .'|salarie/([^/]++)/average\\-work\\-time(*:186)'
                .')'
                .'|/users/([^/]++)(?'
                    .'|(*:213)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        61 => [
            [['_route' => 'clock_delete', '_controller' => 'App\\Controller\\ClockController::deleteClock'], ['id'], ['DELETE' => 0], null, false, true, null],
            [['_route' => 'clock_update', '_controller' => 'App\\Controller\\ClockController::updateClock'], ['id'], ['PUT' => 0], null, false, true, null],
        ],
        87 => [
            [['_route' => 'team_detail', '_controller' => 'App\\Controller\\TeamsManagementController::getTeam'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'team_update', '_controller' => 'App\\Controller\\TeamsManagementController::updateTeam'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'team_delete', '_controller' => 'App\\Controller\\TeamsManagementController::deleteTeam'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        142 => [[['_route' => 'reports_employee_daily', '_controller' => 'App\\Controller\\KpiReportController::getEmployeeDailyWorkTime'], ['id'], ['GET' => 0], null, false, false, null]],
        186 => [[['_route' => 'reports_employee_avg', '_controller' => 'App\\Controller\\KpiReportController::getEmployeeAverageWorkTime'], ['id'], ['GET' => 0], null, false, false, null]],
        213 => [
            [['_route' => 'user_delete', '_controller' => 'App\\Controller\\UsersManagementController::deleteUser'], ['id'], ['DELETE' => 0], null, false, true, null],
            [['_route' => 'user_show', '_controller' => 'App\\Controller\\UsersManagementController::showUser'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'user_update', '_controller' => 'App\\Controller\\UsersManagementController::updateUser'], ['id'], ['PUT' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
