<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\Clock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class KpiReportController extends AbstractController
{
    #[Route('/reports/employee/{id}/daily-work-time', name: 'reports_employee_daily', methods: ['GET'])]
    public function getEmployeeDailyWorkTime(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $dateStr = $request->query->get('date');
        if (!$dateStr) {
            return new JsonResponse(['error' => 'Le paramètre "date" (YYYY-MM-DD) est requis.'], 400);
        }

        try {
            $targetDate = new \DateTimeImmutable($dateStr);
            $nextDay = $targetDate->modify('+1 day');
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD.'], 400);
        }

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $clocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->join('tm.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->orderBy('c.timestamp', 'ASC')
            ->setParameter('userId', $id)
            ->setParameter('start', $targetDate)
            ->setParameter('end', $nextDay)
            ->getQuery()
            ->getResult();

        if (empty($clocks)) {
            return new JsonResponse([
                'user_id' => $id,
                'date' => $dateStr,
                'daily_work_time' => '00h 00m',
                'message' => 'Aucun pointage trouvé pour cette journée.'
            ]);
        }

        $dailyRecords = array_map(fn($clock) => [
            'type' => $clock->getType(),
            'timestamp' => $clock->getTimestamp()
        ], $clocks);

        $totalSeconds = $this->calculateTotalSeconds($dailyRecords);
        $formattedTime = $this->formatDuration($totalSeconds);

        return new JsonResponse([
            'user_id' => $id,
            'date' => $dateStr,
            'daily_work_time' => $formattedTime
        ]);
    }
    #[Route('/reports', name: 'reports_global', methods: ['GET'])]
    public function getGlobalReport(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $clocks = $em->getRepository(Clock::class)->findAll();

        $totalUsers = count($users);
        $totalClocks = count($clocks);
        $avgClocksPerUser = $this->averageFromSumAndCount($totalClocks, $totalUsers);

        // Inline countClocksPerUser logic
        $userClockCounts = [];
        foreach ($clocks as $clock) {
            $teamMember = $clock->getTeamMember();
            if ($teamMember && $teamMember->getUser()) {
                $userId = $teamMember->getUser()->getId();
                if (!isset($userClockCounts[$userId])) {
                    $userClockCounts[$userId] = 0;
                }
                $userClockCounts[$userId] += 1;
            }
        }

        // Inline findMostActiveUser logic
        if (empty($userClockCounts)) {
            $mostActiveUser = null;
            $maxClocks = 0;
        } else {
            $maxClocks = max($userClockCounts);
            $userId = array_search($maxClocks, $userClockCounts);
            $mostActiveUser = $em->getRepository(User::class)->find($userId);
        }

        $lastActivity = $this->getLastActivityTimestamp($clocks);

        return new JsonResponse([
            'kpis' => [
                'total_users' => $totalUsers,
                'total_clocks' => $totalClocks,
                'average_clocks_per_user' => $avgClocksPerUser,
                'most_active_user' => $mostActiveUser ? [
                    'id' => $mostActiveUser->getId(),
                    'firstname' => $mostActiveUser->getFirstname(),
                    'lastname' => $mostActiveUser->getLastname(),
                    'email' => $mostActiveUser->getEmail(),
                    'total_clocks' => $maxClocks,
                ] : null,
                'last_activity' => $lastActivity,
            ]
        ]);
    }

    #[Route('/reports/filter', name: 'reports_global_filtered', methods: ['GET'])]
    public function getGlobalReportFiltered(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $filterMonth = $request->query->get('month');
        $filterYear = $request->query->get('year');

        list($allClocks, $allUsers) = $this->getFilteredData($em, $filterMonth, $filterYear);

        if (empty($allClocks)) {
            $monthValue = 'all';
            if ($filterMonth !== null) {
                $monthValue = $filterMonth;
            }

            $yearValue = 'all';
            if ($filterYear !== null) {
                $yearValue = $filterYear;
            }

            $emptyResponse = [
                'filters' => [
                    'month' => $monthValue,
                    'year' => $yearValue
                ],
                'message' => 'Aucune activité pendant ce mois',
                'summary' => [
                    'total_users' => count($allUsers),
                    'total_clocks' => 0,
                    'average_clocks_per_user' => 0,
                    'average_work_time' => '00h 00m',
                    'last_activity' => null
                ],
                'users' => []
            ];

            return new JsonResponse($emptyResponse);
        }

        $numberOfUsers = count($allUsers);
        $numberOfClocks = count($allClocks);
        $averageClocksPerUser = $this->averageFromSumAndCount($numberOfClocks, $numberOfUsers);

        $clocksGroupedByUser = $this->groupClocksByUser($allClocks);
        list($userDurations, $totalWorkSeconds) = $this->calculateUserDurations($clocksGroupedByUser);

        // Compute average work time per user
        $averageWorkTimePerUser = 0;
        if ($numberOfUsers > 0) {
            $averageWorkTimePerUser = $totalWorkSeconds / $numberOfUsers;
        }
        $formattedAverageWorkTime = $this->formatDuration($averageWorkTimePerUser);

        $lastActivityTimestamp = $this->getLastActivityTimestamp($allClocks);

        // Build report for users
        $reportForUsers = [];
        foreach ($allUsers as $user) {
            $userId = $user->getId();
            if (isset($userDurations[$userId]['total_work_time'])) {
                $totalWorkTime = $userDurations[$userId]['total_work_time'];
            } else {
                $totalWorkTime = '00h 00m';
            }

            $reportForUsers[] = [
                'id' => $userId,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'total_work_time' => $totalWorkTime
            ];
        }

        // Handle filters
        $monthValue = 'all';
        if ($filterMonth !== null) {
            $monthValue = $filterMonth;
        }

        $yearValue = 'all';
        if ($filterYear !== null) {
            $yearValue = $filterYear;
        }

        $filtersApplied = [
            'month' => $monthValue,
            'year' => $yearValue
        ];

        $summaryData = [
            'total_users' => $numberOfUsers,
            'total_clocks' => $numberOfClocks,
            'average_clocks_per_user' => $averageClocksPerUser,
            'average_work_time' => $formattedAverageWorkTime,
            'last_activity' => $lastActivityTimestamp
        ];

        $response = [
            'filters' => $filtersApplied,
            'summary' => $summaryData,
            'users' => $reportForUsers
        ];

        return new JsonResponse($response);
    }

    #[Route('/reports/average-work-time', name: 'reports_avg', methods: ['GET'])]
    public function getAverageWorkTime(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $type = $request->query->get('type');
        $userId = $request->query->get('user_id');
        $teamId = $request->query->get('team_id');
        $day = $request->query->get('day');
        $week = $request->query->get('week');
        $month = $request->query->get('month');
        $year = $request->query->get('year');
        $period = $request->query->get('period');

        if ($period !== 'day' && $period !== 'week' && $period !== 'month' && $period !== 'year') {
            return new JsonResponse([
                'error' => 'Invalid period value. The period parameter must be one of: day, week, month, or year.',
                'hint' => 'You can also filter by day, week, month, or year in the query string.'
            ], 400);
        }

        if (!$type || ($type !== 'user' && $type !== 'team')) {
            return new JsonResponse([
                'error' => 'Missing or invalid parameter: type',
                'hint' => 'Type must be "user" or "team".'
            ], 400);
        }

        if (($userId && $teamId) || (!$userId && !$teamId)) {
            if ($userId && $teamId) {
                return new JsonResponse([
                    'error' => 'Cannot provide both user_id and team_id. Please specify only one.'
                ], 400);
            } else {
                return new JsonResponse([
                    'error' => 'Please provide either user_id or team_id.'
                ], 400);
            }
        }

        try {
            list($startDate, $endDate) = $this->resolveDateRange($day, $week, $month, $year);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $filtersApplied = [
            'day' => $day !== null ? $day : 'all',
            'week' => $week !== null ? $week : 'all',
            'month' => $month !== null ? $month : 'all',
            'year' => $year !== null ? $year : 'all',
            'period' => $period
        ];

        // USER REPORT
        if ($type === 'user') {
            if (!$userId) {
                return new JsonResponse(['error' => 'Missing required parameter: user_id'], 400);
            }

            $user = $em->getRepository(User::class)->find($userId);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Fetch clocks
            $qb = $em->createQueryBuilder()
                ->select('c')
                ->from(Clock::class, 'c')
                ->join('c.teamMember', 'tm')
                ->join('tm.user', 'u')
                ->where('u.id = :userId')
                ->andWhere('c.timestamp >= :start')
                ->andWhere('c.timestamp < :end')
                ->setParameter('userId', $userId)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('c.timestamp', 'ASC');

            $clocks = $qb->getQuery()->getResult();

            if (empty($clocks)) {
                return new JsonResponse([
                    'type' => 'user',
                    'user_id' => $userId,
                    'user_name' => $user->getFirstname() . ' ' . $user->getLastname(),
                    'since' => $startDate->format('Y-m-d'),
                    'until' => $endDate->format('Y-m-d'),
                    'filters' => $filtersApplied,
                    'summary' => [
                        'total_periods' => 0,
                        'total_work_time' => $this->formatDuration(0),
                        'average_work_time_per_' . $period => $this->formatDuration(0)
                    ]
                ]);
            }

            // Group clocks by period
            $groupedByPeriod = [];
            foreach ($clocks as $clock) {
                $timestamp = $clock->getTimestamp();
                $periodKey = $this->getPeriodKey($timestamp, $period);
                if (!isset($groupedByPeriod[$periodKey])) {
                    $groupedByPeriod[$periodKey] = [];
                }
                $groupedByPeriod[$periodKey][] = ['type' => $clock->getType(), 'timestamp' => $timestamp];
            }

            // Compute total seconds per period
            $periodTotals = [];
            foreach ($groupedByPeriod as $records) {
                $totalSeconds = 0;
                $arrivals = [];
                $departures = [];
                foreach ($records as $record) {
                    if ($record['type'] === 'arrival') {
                        $arrivals[] = $record['timestamp'];
                    }
                    if ($record['type'] === 'departure') {
                        $departures[] = $record['timestamp'];
                    }
                }
                $pairs = min(count($arrivals), count($departures));
                for ($i = 0; $i < $pairs; $i++) {
                    $interval = $departures[$i]->getTimestamp() - $arrivals[$i]->getTimestamp();
                    if ($interval > 0) {
                        $totalSeconds += $interval;
                    }
                }
                $periodTotals[] = $totalSeconds;
            }

            $totalPeriods = count($periodTotals);
            $totalSeconds = 0;
            foreach ($periodTotals as $sec) {
                $totalSeconds += $sec;
            }

            $averageSeconds = 0;
            if ($totalPeriods > 0) {
                $sum = 0;
                foreach ($periodTotals as $sec) {
                    $sum += $sec;
                }
                $averageSeconds = $sum / $totalPeriods;
            }

            return new JsonResponse([
                'type' => 'user',
                'user_id' => $userId,
                'user_name' => $user->getFirstname() . ' ' . $user->getLastname(),
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
                'filters' => $filtersApplied,
                'summary' => [
                    'total_periods' => $totalPeriods,
                    'total_work_time' => $this->formatDuration($totalSeconds),
                    'average_work_time_per_' . $period => $this->formatDuration($averageSeconds)
                ]
            ]);
        }

        // TEAM REPORT
        if ($type === 'team') {
            $team = $em->getRepository(Team::class)->find($teamId);
            if (!$team) {
                return new JsonResponse(['error' => 'Team not found'], 404);
            }

            // Fetch clocks for team members
            $qb = $em->createQueryBuilder()
                ->select('c')
                ->from(Clock::class, 'c')
                ->join('c.teamMember', 'tm')
                ->where('tm.team = :teamId')
                ->andWhere('c.timestamp >= :start')
                ->andWhere('c.timestamp < :end')
                ->orderBy('c.timestamp', 'ASC')
                ->setParameter('teamId', $teamId)
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);

            $clocks = $qb->getQuery()->getResult();

            // Map clocks to members
            $membersClocks = [];
            foreach ($clocks as $clock) {
                $tm = $clock->getTeamMember();
                $uid = $tm->getUser()->getId();
                if (!isset($membersClocks[$uid])) {
                    $membersClocks[$uid] = [];
                }
                $membersClocks[$uid][] = ['type' => $clock->getType(), 'timestamp' => $clock->getTimestamp()];
            }

            $membersSummary = [];
            foreach ($membersClocks as $uid => $records) {
                $totalSeconds = 0;
                $arrivals = [];
                $departures = [];
                foreach ($records as $record) {
                    if ($record['type'] === 'arrival') {
                        $arrivals[] = $record['timestamp'];
                    }
                    if ($record['type'] === 'departure') {
                        $departures[] = $record['timestamp'];
                    }
                }
                $pairs = min(count($arrivals), count($departures));
                for ($i = 0; $i < $pairs; $i++) {
                    $interval = $departures[$i]->getTimestamp() - $arrivals[$i]->getTimestamp();
                    if ($interval > 0) {
                        $totalSeconds += $interval;
                    }
                }
                $membersSummary[$uid] = [
                    'user_name' => $tm->getUser()->getFirstname() . ' ' . $tm->getUser()->getLastname(),
                    'total_work_time' => $totalSeconds
                ];
            }

            $teamTotalSeconds = 0;
            foreach ($membersSummary as $data) {
                $teamTotalSeconds += $data['total_work_time'];
            }

            $teamAverage = 0;
            if (count($membersSummary) > 0) {
                $teamAverage = $teamTotalSeconds / count($membersSummary);
            }

            $membersOutput = [];
            foreach ($membersSummary as $data) {
                $membersOutput[] = [
                    'user_name' => $data['user_name'],
                    'total_work_time' => $this->formatDuration($data['total_work_time'])
                ];
            }

            return new JsonResponse([
                'type' => 'team',
                'team_id' => $teamId,
                'since' => $startDate->format('Y-m-d'),
                'until' => $endDate->format('Y-m-d'),
                'period' => $period,
                'team' => [
                    'members_count' => count($membersSummary),
                    'team_average_work_time' => $this->formatDuration($teamAverage),
                    'members' => $membersOutput
                ]
            ]);
        }

        return new JsonResponse(['error' => 'Invalid type parameter. Must be user or team.'], 400);
    }

    #[Route('/reports/average-late-time', name: 'reports_avg_late_time', methods: ['GET'])]
    public function getAverageLateTime(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $type   = $request->query->get('type');
        $userId = $request->query->get('user_id');
        $teamId = $request->query->get('team_id');
        $day    = $request->query->get('day');
        $week   = $request->query->get('week');
        $month  = $request->query->get('month');
        $year   = $request->query->get('year');
        $period = $request->query->get('period');

        if ($period !== 'day' && $period !== 'week' && $period !== 'month' && $period !== 'year') {
            return new JsonResponse([
                'error' => 'Invalid period value. Must be one of: day, week, month, year.',
            ], 400);
        }

        if (!$type || ($type !== 'user' && $type !== 'team')) {
            return new JsonResponse(['error' => 'Type must be "user" or "team".'], 400);
        }

        if (($userId && $teamId) || (!$userId && !$teamId)) {
            if ($userId && $teamId) {
                return new JsonResponse(['error'=>'Cannot provide both user_id and team_id.'], 400);
            } else {
                return new JsonResponse(['error'=>'Must provide either user_id or team_id.'], 400);
            }
        }

        try {
            list($startDate, $endDate) = $this->resolveDateRange($day, $week, $month, $year);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if ($type === 'user') {
            if (!$userId) {
                return new JsonResponse(['error'=>'Missing user_id'],400);
            }
            $user = $em->getRepository(User::class)->find($userId);
            if (!$user) {
                return new JsonResponse(['error'=>'User not found'],404);
            }
        } else {
            if (!$teamId) {
                return new JsonResponse(['error'=>'Missing team_id'],400);
            }
            $team = $em->getRepository(Team::class)->find($teamId);
            if (!$team) {
                return new JsonResponse(['error'=>'Team not found'],404);
            }
        }

        $clockTypes = ['arrival', 'end_work', 'end_break'];
        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->join('tm.user', 'u')
            ->andWhere('c.type IN (:clockTypes)')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->setParameter('clockTypes', $clockTypes)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($type === 'user') {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $user->getId());
        } else {
            $qb->andWhere('tm.team = :teamId')->setParameter('teamId', $team->getId());
        }

        $clocks = $qb->getQuery()->getResult();

        if (empty($clocks)) {
            $filters = [
                'day' => $day !== null ? $day : 'all',
                'week' => $week !== null ? $week : 'all',
                'month' => $month !== null ? $month : 'all',
                'year' => $year !== null ? $year : 'all',
                'period' => $period,
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ];

            if ($type === 'user') {
                return new JsonResponse([
                    'type' => 'user',
                    'user_id' => $user->getId(),
                    'user_name' => $user->getFirstname().' '.$user->getLastname(),
                    'filters' => $filters,
                    'message' => 'No clock-ins found for this period.'
                ]);
            } else {
                return new JsonResponse([
                    'type' => 'team',
                    'team_id' => $team->getId(),
                    'team_name' => $team->getName(),
                    'filters' => $filters,
                    'message' => 'No clock-ins found for this period.'
                ]);
            }
        }

        // Compute lateness
        $latenessGrouped = [];

        foreach ($clocks as $clock) {
            $clockTime = $clock->getTimestamp();
            $teamMember = $clock->getTeamMember();
            $expectedStart = $teamMember->getStartTime();
            if (!$expectedStart) {
                continue;
            }

            $expectedDateTime = new \DateTimeImmutable(
                $clockTime->format('Y-m-d').' '.$expectedStart->format('H:i:s')
            );

            $latenessSeconds = $clockTime->getTimestamp() - $expectedDateTime->getTimestamp();
            if ($latenessSeconds < 0) {
                $latenessSeconds = 0;
            }

            $periodKey = $this->getPeriodKey($clockTime, $period);

            if ($type === 'user') {
                if (!isset($latenessGrouped[$periodKey])) {
                    $latenessGrouped[$periodKey] = [];
                }
                $latenessGrouped[$periodKey][] = $latenessSeconds;
            } else {
                $userId = $teamMember->getUser()->getId();
                if (!isset($latenessGrouped[$userId])) {
                    $latenessGrouped[$userId] = [
                        'user_name' => $teamMember->getUser()->getFirstname().' '.$teamMember->getUser()->getLastname(),
                        'periods' => []
                    ];
                }
                if (!isset($latenessGrouped[$userId]['periods'][$periodKey])) {
                    $latenessGrouped[$userId]['periods'][$periodKey] = [];
                }
                $latenessGrouped[$userId]['periods'][$periodKey][] = $latenessSeconds;
            }
        }

        $filtersApplied = [
            'day' => $day !== null ? $day : 'all',
            'week' => $week !== null ? $week : 'all',
            'month' => $month !== null ? $month : 'all',
            'year' => $year !== null ? $year : 'all',
            'period' => $period,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ];

        if ($type === 'user') {
            $periodAverages = [];
            foreach ($latenessGrouped as $period => $values) {
                $periodAverages[$period] = $this->averageOfArray($values);
            }

            $overallTotal = 0;
            $periodCount = count($periodAverages);
            foreach ($periodAverages as $avg) {
                $overallTotal += $avg;
            }
            $overallAverage = 0;
            if ($periodCount > 0) {
                $overallAverage = $overallTotal / $periodCount;
            }

            return new JsonResponse([
                'type' => 'user',
                'user_id' => $user->getId(),
                'user_name' => $user->getFirstname().' '.$user->getLastname(),
                'filters' => $filtersApplied,
                'summary' => [
                    'total_periods' => $periodCount,
                    'average_lateness' => $this->formatDuration($overallAverage)
                ]
            ]);
        } else {
            $membersSummary = [];
            foreach ($latenessGrouped as $uid => $memberData) {
                $memberPeriodAverages = [];
                foreach ($memberData['periods'] as $periodKey => $values) {
                    $memberPeriodAverages[$periodKey] = $this->averageOfArray($values);
                }

                $memberTotal = 0;
                $memberCount = count($memberPeriodAverages);
                foreach ($memberPeriodAverages as $avg) {
                    $memberTotal += $avg;
                }

                $overallMemberAverage = 0;
                if ($memberCount > 0) {
                    $overallMemberAverage = $memberTotal / $memberCount;
                }

                $membersSummary[$uid] = [
                    'user_name' => $memberData['user_name'],
                    'average_overall' => $overallMemberAverage
                ];
            }

            $teamTotal = 0;
            $teamCount = count($membersSummary);
            foreach ($membersSummary as $data) {
                $teamTotal += $data['average_overall'];
            }
            $teamAverage = 0;
            if ($teamCount > 0) {
                $teamAverage = $teamTotal / $teamCount;
            }

            $membersOutput = [];
            foreach ($membersSummary as $data) {
                $membersOutput[] = [
                    'user_name' => $data['user_name'],
                    'average_lateness' => $this->formatDuration($data['average_overall'])
                ];
            }

            return new JsonResponse([
                'type' => 'team',
                'team_id' => $team->getId(),
                'team_name' => $team->getName(),
                'filters' => $filtersApplied,
                'summary' => [
                    'members_count' => $teamCount,
                    'team_average_lateness' => $this->formatDuration($teamAverage)
                ],
                'members' => $membersOutput
            ]);
        }
    }


    /* ------------------------- PRIVATE HELPERS ------------------------- */
    private function getLastActivityTimestamp(array $clocks): ?string
    {
        if (empty($clocks)) {
            return null;
        }

        // Sort clocks descending by timestamp
        usort($clocks, function ($clockA, $clockB) {
            $timestampA = $clockA->getTimestamp()->getTimestamp();
            $timestampB = $clockB->getTimestamp()->getTimestamp();

            if ($timestampA === $timestampB) {
                return 0;
            }

            return ($timestampA < $timestampB) ? 1 : -1;
        });

        $mostRecentClock = $clocks[0];
        $mostRecentTimestamp = $mostRecentClock->getTimestamp();

        return $mostRecentTimestamp->format('Y-m-d H:i:s');
    }

    private function averageFromSumAndCount(int $total, int $count): float
    {
        if ($count > 0) {
            $average = $total / $count;
            return round($average, 2);
        } else {
            return 0.0;
        }
    }

    private function averageOfArray(array $values): float
    {
        // Remove null entries from the array
        $filteredValues = [];
        foreach ($values as $value) {
            if ($value !== null) {
                $filteredValues[] = $value;
            }
        }

        // Count how many valid values remain
        $valueCount = count($filteredValues);

        // If there are no valid values, return 0
        if ($valueCount === 0) {
            return 0.0;
        }

        // Calculate the sum of all valid values
        $totalSum = array_sum($filteredValues);

        // Compute the average
        $averageValue = $totalSum / $valueCount;

        return $averageValue;
    }

    private function formatDuration(float $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = floor($seconds % 60);
        return sprintf('%02dh %02dm %02ds', $h, $m, $s);
    }

    private function resolveDateRange(?string $day, ?string $week, ?string $month, ?string $year): array
    {
        $currentDate = new \DateTimeImmutable('now');

        try {
            if ($day !== null && $month !== null && $year !== null) {
                // Exact day specified
                $rangeStart = new \DateTimeImmutable("$year-$month-$day 00:00:00");
                $rangeEnd = $rangeStart->modify('+1 day');

            } elseif ($week !== null && $month !== null && $year !== null) {
                // Specific week within a month
                $firstDayOfMonth = new \DateTimeImmutable("$year-$month-01 00:00:00");
                $lastDayOfMonth = $firstDayOfMonth->modify('last day of this month');

                $daysInMonth = (int)$lastDayOfMonth->format('d');
                $firstWeekdayOfMonth = (int)$firstDayOfMonth->format('N'); // 1 (Mon) to 7 (Sun)
                $totalWeeksInMonth = (int) ceil(($daysInMonth + ($firstWeekdayOfMonth - 1)) / 7);

                if ($week < 1 || $week > $totalWeeksInMonth) {
                    throw new \InvalidArgumentException(
                        "Week must be between 1 and $totalWeeksInMonth for $month/$year."
                    );
                }

                $startOffsetDays = (($week - 1) * 7) - ($firstWeekdayOfMonth - 1);
                $rangeStart = $firstDayOfMonth->modify("+$startOffsetDays days");
                $rangeEnd = $rangeStart->modify('+7 days');

                $lastDayWithTime = $lastDayOfMonth->setTime(23, 59, 59);
                if ($rangeEnd > $lastDayWithTime) {
                    $rangeEnd = $lastDayWithTime;
                }

            } elseif ($week !== null && $year !== null) {
                // Week number within the year (ISO week)
                $isoWeekStart = (new \DateTimeImmutable())->setISODate((int)$year, (int)$week);
                $rangeStart = $isoWeekStart->modify('Monday 00:00:00');
                $rangeEnd = $rangeStart->modify('+1 week');

            } elseif ($month !== null && $year !== null) {
                // Whole month
                $rangeStart = new \DateTimeImmutable("$year-$month-01 00:00:00");
                $rangeEnd = $rangeStart->modify('+1 month');

            } elseif ($year !== null) {
                // Whole year
                $rangeStart = new \DateTimeImmutable("$year-01-01 00:00:00");
                $rangeEnd = $rangeStart->modify('+1 year');

            } else {
                // Default: last 365 days
                $rangeStart = $currentDate->modify('-365 days');
                $rangeEnd = $currentDate;
            }

            return [$rangeStart, $rangeEnd];

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date filter: ' . $e->getMessage());
        }
    }

    private function getFilteredData(EntityManagerInterface $em, ?string $month, ?string $year): array
    {
        $queryBuilder = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c');

        if ($month !== null && $year !== null) {
            $startDate = new \DateTimeImmutable("$year-$month-01 00:00:00");
            $endDate = $startDate->modify('+1 month');

            $queryBuilder->where('c.timestamp >= :startDate')
                ->andWhere('c.timestamp < :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $filteredClocks = $queryBuilder->getQuery()->getResult();
        $allUsers = $em->getRepository(User::class)->findAll();

        return [$filteredClocks, $allUsers];
    }

    private function groupClocksByUser(array $clocks): array
    {
        $clocksGroupedByUser = [];

        foreach ($clocks as $clockEntry) {
            $teamMember = $clockEntry->getTeamMember();
            if ($teamMember === null) {
                continue;
            }

            $user = $teamMember->getUser();
            if ($user === null) {
                continue;
            }

            $userId = $user->getId();

            $clocksGroupedByUser[$userId][] = [
                'type' => $clockEntry->getType(),
                'timestamp' => $clockEntry->getTimestamp(),
            ];
        }

        return $clocksGroupedByUser;
    }

    private function calculateUserDurations(array $clocksGroupedByUser): array
    {
        $userDurations = [];
        $totalSecondsAllUsers = 0;

        foreach ($clocksGroupedByUser as $userId => $userClocks) {

            // Separate arrival and departure clocks
            $arrivalClocks = [];
            $departureClocks = [];
            foreach ($userClocks as $record) {
                if ($record['type'] === 'arrival') {
                    $arrivalClocks[] = $record;
                } elseif ($record['type'] === 'departure') {
                    $departureClocks[] = $record;
                }
            }

            // Determine how many complete pairs of arrival/departure we have
            $numberOfPairs = count($arrivalClocks);
            if (count($departureClocks) < $numberOfPairs) {
                $numberOfPairs = count($departureClocks);
            }

            $totalUserSeconds = 0;

            for ($i = 0; $i < $numberOfPairs; $i++) {
                $arrivalTime = $arrivalClocks[$i]['timestamp']->getTimestamp();
                $departureTime = $departureClocks[$i]['timestamp']->getTimestamp();

                $interval = $departureTime - $arrivalTime;
                if ($interval > 0) {
                    $totalUserSeconds += $interval;
                }
            }

            $totalSecondsAllUsers += $totalUserSeconds;

            $userDurations[$userId] = [
                'total_work_time' => $this->formatDuration($totalUserSeconds),
                'total_seconds' => $totalUserSeconds
            ];
        }

        return [$userDurations, $totalSecondsAllUsers];
    }

    private function calculateTotalSeconds(array $records): int
    {
        usort($records, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        $inTypes = ['arrival', 'end_work', 'end_break'];
        $outTypes = ['departure', 'start_work', 'start_break'];

        $ins = [];
        $outs = [];

        foreach ($records as $r) {
            $type = $r['type'] ?? '';
            $ts = $r['timestamp'] ?? null;
            if (!($ts instanceof \DateTimeInterface)) continue;

            if (in_array($type, $inTypes, true)) $ins[] = $ts;
            elseif (in_array($type, $outTypes, true)) $outs[] = $ts;
        }

        usort($ins, fn($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());
        usort($outs, fn($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());

        $pairs = min(count($ins), count($outs));
        $total = 0;

        for ($i = 0; $i < $pairs; $i++) {
            $delta = $outs[$i]->getTimestamp() - $ins[$i]->getTimestamp();
            if ($delta > 0) $total += $delta;
        }

        return $total;
    }

    private function mapClocksToMembers(array $clocks): array
    {
        $memberClocks = [];
        $memberTeam = [];

        foreach ($clocks as $clock) {
            $tm = $clock->getTeamMember();
            if (!$tm || !$tm->getTeam()) continue;

            $tmId = $tm->getId();
            $memberTeam[$tmId] = $tm->getTeam()->getId();
            $memberClocks[$tmId][] = [
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp(),
            ];
        }

        return [$memberClocks, $memberTeam];
    }

    private function computeSingleTeamData(Team $team, array $memberClocks, string $period): array
    {
        // Compute average work seconds per member
        $memberSeconds = $this->computeMemberWorkSeconds($memberClocks, $period);

        // Aggregate team data
        $teamData = [
            'id' => $team->getId(),
            'name' => $team->getName(),
            '_sum' => 0,
            '_count' => 0,
            'average_work_time_seconds' => 0,
            'average_work_time' => '00h 00m',
            'members_count' => 0
        ];

        foreach ($memberSeconds as $avgSec) {
            $teamData['_sum'] += $avgSec;
            $teamData['_count']++;
        }

        // Finalize averages
        $count = $teamData['_count'];
        $avgSec = (int) round($this->averageOfArray($memberSeconds));
        $teamData['members_count'] = $count;
        $teamData['average_work_time_seconds'] = $avgSec;
        $teamData['average_work_time'] = $this->formatDuration($avgSec);

        unset($teamData['_sum'], $teamData['_count']);

        return $teamData;
    }

    private function computeMemberWorkSeconds(array $memberClocks, string $period): array
    {
        $result = [];

        foreach ($memberClocks as $memberId => $records) {
            // Group records by period key
            $grouped = [];
            foreach ($records as $r) {
                $ts = $r['timestamp'];
                $key = $this->getPeriodKey($ts, $period);
                $grouped[$key][] = $r;
            }

            // Sum seconds per period
            $totalSeconds = 0;
            foreach ($grouped as $periodRecords) {
                $totalSeconds += $this->calculateTotalSeconds($periodRecords);
            }

            // Average per period
            $periodCount = count($grouped);
            $result[$memberId] = $periodCount > 0 ? $totalSeconds / $periodCount : 0;
        }

        return $result;
    }


    private function getPeriodKey(\DateTimeInterface $ts, string $period): string
    {
        return match ($period) {
            'week'  => $ts->format('o-W'),
            'month' => $ts->format('Y-m'),
            'year'  => $ts->format('Y'),
            default => $ts->format('Y-m-d'),
        };
    }
}
