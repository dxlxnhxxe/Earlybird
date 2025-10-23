<?php

namespace App\Controller\Reports;

use App\Entity\User;
use App\Entity\Clock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserReportController extends AbstractController
{
    #[Route('/employee/{id}/daily-work-time', name: 'employee_daily', methods: ['GET'])]
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

    #[Route('/employee/{id}/average-work-time', name: 'employee_avg', methods: ['GET'])]
    public function getEmployeeAverageWorkTime(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $day = $request->query->get('day');
        $week = $request->query->get('week');
        $month = $request->query->get('month');
        $year = $request->query->get('year');
        $period = $request->query->get('period', 'day');

        // Validate period
        if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
            return new JsonResponse([
                'error' => 'Invalid period value. The "period" parameter must be one of: day, week, month, or year.',
                'hint' => 'You can also filter by day, week, month, or year in the query string.'
            ], 400);
        }

        // Validate user existence
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Build query
        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->join('tm.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $id)
            ->orderBy('c.timestamp', 'ASC');

        // Apply date filters
        try {
            if ($day && $month && $year) {
                $start = new \DateTimeImmutable("$year-$month-$day 00:00:00");
                $end = $start->modify('+1 day');
            } elseif ($week && $year) {
                $dto = new \DateTimeImmutable();
                $dto = $dto->setISODate((int)$year, (int)$week);
                $start = $dto->modify('Monday 00:00:00');
                $end = $start->modify('+1 week');
            } elseif ($month && $year) {
                $start = new \DateTimeImmutable("$year-$month-01 00:00:00");
                $end = $start->modify('+1 month');
            } elseif ($year) {
                $start = new \DateTimeImmutable("$year-01-01 00:00:00");
                $end = $start->modify('+1 year');
            }

            if (isset($start) && isset($end)) {
                $qb->andWhere('c.timestamp >= :start')
                    ->andWhere('c.timestamp < :end')
                    ->setParameter('start', $start)
                    ->setParameter('end', $end);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date filter.'], 400);
        }

        $clocks = $qb->getQuery()->getResult();

        if (empty($clocks)) {
            return new JsonResponse([
                'user_id' => $id,
                'user_name' => $user->getFirstname() . ' ' . $user->getLastname(),
                'filters' => [
                    'day' => $day ?? 'all',
                    'week' => $week ?? 'all',
                    'month' => $month ?? 'all',
                    'year' => $year ?? 'all',
                    'period' => $period
                ],
                'message' => 'Nothing found for the selected period'
            ]);
        }

        // Group clocks by selected period
        $groupedRecords = [];
        foreach ($clocks as $clock) {
            $ts = $clock->getTimestamp();
            switch ($period) {
                case 'week':
                    $key = $ts->format('o-W'); // ISO week
                    break;
                case 'month':
                    $key = $ts->format('Y-m'); // Year-Month
                    break;
                case 'year':
                    $key = $ts->format('Y'); // Year
                    break;
                default:
                    $key = $ts->format('Y-m-d'); // Day
            }
            $groupedRecords[$key][] = [
                'type' => $clock->getType(),
                'timestamp' => $ts
            ];
        }

        // Calculate total work seconds per period
        $periodSeconds = [];
        foreach ($groupedRecords as $key => $records) {
            $periodSeconds[$key] = $this->calculateTotalSeconds($records); // your existing function
        }

        $totalPeriods = count($periodSeconds);
        $totalSeconds = array_sum($periodSeconds);
        $avgSeconds = $totalPeriods > 0 ? $totalSeconds / $totalPeriods : 0;

        // Build summary
        $summary = [
            'total_periods' => $totalPeriods,
            'total_work_time' => $this->formatDuration($totalSeconds),
            'average_work_time_per_' . $period => $this->formatDuration($avgSeconds),
        ];

        return new JsonResponse([
            'user_id' => $id,
            'user_name' => $user->getFirstname() . ' ' . $user->getLastname(),
            'filters' => [
                'day' => $day ?? 'all',
                'week' => $week ?? 'all',
                'month' => $month ?? 'all',
                'year' => $year ?? 'all',
                'period' => $period
            ],
            'summary' => $summary
        ]);
    }

    #[Route('/', name: 'global', methods: ['GET'])]
    public function getGlobalReport(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $clocks = $em->getRepository(Clock::class)->findAll();

        $totalUsers = count($users);
        $totalClocks = count($clocks);
        $avgClocksPerUser = $this->calculateAverage($totalClocks, $totalUsers);

        $userClockCounts = $this->countClocksPerUser($clocks);
        [$mostActiveUser, $maxClocks] = $this->findMostActiveUser($em, $userClockCounts);
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


    #[Route('/filter', name: 'global_filtered', methods: ['GET'])]
    public function getGlobalReportFiltered(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $month = $request->query->get('month');
        $year = $request->query->get('year');

        [$clocks, $users] = $this->getFilteredData($em, $month, $year);

        if (empty($clocks)) {
            return $this->emptyFilteredResponse($users, $month, $year);
        }

        $totalUsers = count($users);
        $totalClocks = count($clocks);
        $avgClocksPerUser = $this->calculateAverage($totalClocks, $totalUsers);

        $userClocks = $this->groupClocksByUser($clocks);
        [$userDurations, $globalSeconds] = $this->calculateUserDurations($userClocks);

        $avgWorkTime = $this->formatAverageWorkTime($globalSeconds, $totalUsers);
        $lastActivity = $this->getLastActivityTimestamp($clocks);
        $reportUsers = $this->buildUserReport($users, $userDurations);

        return new JsonResponse([
            'filters' => ['month' => $month ?? 'all', 'year' => $year ?? 'all'],
            'summary' => [
                'total_users' => $totalUsers,
                'total_clocks' => $totalClocks,
                'average_clocks_per_user' => $avgClocksPerUser,
                'average_work_time' => $avgWorkTime,
                'last_activity' => $lastActivity,
            ],
            'users' => $reportUsers,
        ]);
    }

    /* ------------------------- PRIVATE HELPERS ------------------------- */

    private function calculateAverage(int $total, int $count): float
    {
        return $count > 0 ? round($total / $count, 2) : 0;
    }

    private function countClocksPerUser(array $clocks): array
    {
        $counts = [];
        foreach ($clocks as $clock) {
            $tm = $clock->getTeamMember();
            if ($tm && $tm->getUser()) {
                $userId = $tm->getUser()->getId();
                $counts[$userId] = ($counts[$userId] ?? 0) + 1;
            }
        }
        return $counts;
    }

    private function findMostActiveUser(EntityManagerInterface $em, array $counts): array
    {
        if (empty($counts)) {
            return [null, 0];
        }
        $max = max($counts);
        $userId = array_search($max, $counts);
        return [$em->getRepository(User::class)->find($userId), $max];
    }

    private function getLastActivityTimestamp(array $clocks): ?string
    {
        if (empty($clocks)) return null;
        usort($clocks, fn($a, $b) => $b->getTimestamp() <=> $a->getTimestamp());
        return $clocks[0]->getTimestamp()->format('Y-m-d H:i:s');
    }

    private function getFilteredData(EntityManagerInterface $em, ?string $month, ?string $year): array
    {
        $qb = $em->createQueryBuilder()->select('c')->from(Clock::class, 'c');
        if ($month && $year) {
            $start = new \DateTimeImmutable("$year-$month-01 00:00:00");
            $end = $start->modify('+1 month');
            $qb->where('c.timestamp >= :start')->andWhere('c.timestamp < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }
        return [$qb->getQuery()->getResult(), $em->getRepository(User::class)->findAll()];
    }

    private function emptyFilteredResponse(array $users, ?string $month, ?string $year): JsonResponse
    {
        return new JsonResponse([
            'filters' => ['month' => $month ?? 'all', 'year' => $year ?? 'all'],
            'message' => 'Aucune activité pendant ce mois',
            'summary' => [
                'total_users' => count($users),
                'total_clocks' => 0,
                'average_clocks_per_user' => 0,
                'average_work_time' => '00h 00m',
                'last_activity' => null,
            ],
            'users' => []
        ]);
    }

    private function groupClocksByUser(array $clocks): array
    {
        $grouped = [];
        foreach ($clocks as $clock) {
            $tm = $clock->getTeamMember();
            if ($tm && $tm->getUser()) {
                $id = $tm->getUser()->getId();
                $grouped[$id][] = [
                    'type' => $clock->getType(),
                    'timestamp' => $clock->getTimestamp(),
                ];
            }
        }
        return $grouped;
    }

    private function calculateUserDurations(array $userClocks): array
    {
        $durations = [];
        $global = 0;

        foreach ($userClocks as $uid => $records) {
            $arrivals = array_filter($records, fn($r) => $r['type'] === 'arrival');
            $departures = array_filter($records, fn($r) => $r['type'] === 'departure');
            $pairs = min(count($arrivals), count($departures));
            $total = 0;

            for ($i = 0; $i < $pairs; $i++) {
                $interval = $departures[$i]['timestamp']->getTimestamp() - $arrivals[$i]['timestamp']->getTimestamp();
                if ($interval > 0) $total += $interval;
            }

            $global += $total;
            $durations[$uid] = [
                'total_work_time' => $this->formatSeconds($total),
                'total_seconds' => $total,
            ];
        }

        return [$durations, $global];
    }

    private function formatSeconds(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        return sprintf('%02dh %02dm', $h, $m);
    }

    private function formatAverageWorkTime(int $globalSeconds, int $userCount): string
    {
        if ($userCount === 0 || $globalSeconds === 0) return '00h 00m';
        $avg = $globalSeconds / $userCount;
        return $this->formatSeconds((int)$avg);
    }

    private function buildUserReport(array $users, array $userDurations): array
    {
        $report = [];
        foreach ($users as $u) {
            $uid = $u->getId();
            $report[] = [
                'id' => $uid,
                'firstname' => $u->getFirstname(),
                'lastname' => $u->getLastname(),
                'email' => $u->getEmail(),
                'total_work_time' => $userDurations[$uid]['total_work_time'] ?? '00h 00m',
            ];
        }
        return $report;
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

    private function formatDuration(float $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = floor($seconds % 60);
        return sprintf('%02dh %02dm %02ds', $h, $m, $s);
    }
}
