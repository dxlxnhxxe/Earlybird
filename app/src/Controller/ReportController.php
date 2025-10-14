<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Clock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReportController extends AbstractController
{
    // Route GET /reports : rapport global simple
    #[Route('/reports', name: 'reports_global', methods: ['GET'])]
    public function getGlobalReport(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $clocks = $em->getRepository(Clock::class)->findAll();

        $totalUsers = count($users);
        $totalClocks = count($clocks);
        if ($totalUsers > 0) {
            $avgClocksPerUser = round($totalClocks / $totalUsers, 2);
        } else {
            $avgClocksPerUser = 0;
        }

        $userClockCounts = [];
        foreach ($clocks as $clock) {
            $userId = $clock->getUser()->getId();
            $userClockCounts[$userId] = ($userClockCounts[$userId] ?? 0) + 1;
            /*
            Code a comprendre pour tt le monde
            Admettons $clocks a 3 entrées
            1.	For user 7 → not in array → (0) + 1 = 1
		    2.  For user 7 again → (1) + 1 = 2
		    3.  For user 2 → not in array → (0) + 1 = 1

            donc

            $userClockCounts = [
                  7 => 2,
                  2 => 1
            ];
            */
        }

        $mostActiveUser = null;
        $maxClocks = 0;
        if (!empty($userClockCounts)) {
            $maxClocks = max($userClockCounts);
            $mostActiveUserId = array_search($maxClocks, $userClockCounts);
            $mostActiveUser = $em->getRepository(User::class)->find($mostActiveUserId);
        }

        $lastActivity = null;
        if (!empty($clocks)) {
            usort($clocks, fn($a, $b) => $b->getTimestamp() <=> $a->getTimestamp());
            $lastActivity = $clocks[0]->getTimestamp()->format('Y-m-d H:i:s');
        }

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
        ], 200);
    }

    // Route GET /reports/filter : rapport filtré + calcul du temps travaillé

    // Route GET /reports/filter : rapport filtré + calcul du temps travaillé
    #[Route('/reports/filter', name: 'reports_global_filtered', methods: ['GET'])]
    public function getGlobalReportFiltered(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $month = $request->query->get('month');
        $year = $request->query->get('year');

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();

        // Filter clocks by month/year if provided
        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c');

        if ($month && $year) {
            $startDate = new \DateTimeImmutable("$year-$month-01 00:00:00");
            $endDate = $startDate->modify('+1 month');
            $qb->where('c.timestamp >= :start')
                ->andWhere('c.timestamp < :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        $clocks = $qb->getQuery()->getResult();

        if (empty($clocks)) {
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
            ], 200);
        }

        $totalUsers = count($users);
        $totalClocks = count($clocks);
        $avgClocksPerUser = $totalUsers > 0 ? round($totalClocks / $totalUsers, 2) : 0;

        // Group clocks by user
        $userClocks = $this->groupClocksByUser($clocks);

        $globalTotalSeconds = 0;
        $userDurations = [];

        foreach ($userClocks as $userId => $records) {
            $totalSeconds = $this->calculateTotalSeconds($records);
            $globalTotalSeconds += $totalSeconds;
            $userDurations[$userId] = [
                'total_work_time' => $this->formatDuration($totalSeconds),
                'total_seconds' => $totalSeconds,
            ];
        }

        // Last activity
        usort($clocks, fn($a, $b) => $b->getTimestamp() <=> $a->getTimestamp());
        $lastActivity = $clocks[0]->getTimestamp()->format('Y-m-d H:i:s');

        // Average global work time
        $avgWorkTime = ($totalUsers > 0 && $globalTotalSeconds > 0)
            ? $this->formatDuration($globalTotalSeconds / $totalUsers)
            : '00h 00m';

        // Build user report
        $reportUsers = array_map(function ($user) use ($userDurations) {
            $uid = $user->getId();
            return [
                'id' => $uid,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'total_work_time' => $userDurations[$uid]['total_work_time'] ?? '00h 00m',
            ];
        }, $users);

        return new JsonResponse([
            'filters' => ['month' => $month ?? 'all', 'year' => $year ?? 'all'],
            'summary' => [
                'total_users' => $totalUsers,
                'total_clocks' => $totalClocks,
                'average_clocks_per_user' => $avgClocksPerUser,
                'average_work_time' => $avgWorkTime,
                'last_activity' => $lastActivity,
            ],
            'users' => $reportUsers
        ], 200);
    }

    #[Route('/reports/team/{id}', name: 'reports_team', methods: ['GET'])]
    public function getTeamReport(int $id, EntityManagerInterface $em): JsonResponse
    {
        $teamRepo = $em->getRepository(Team::class);
        $userRepo = $em->getRepository(User::class);
        $clockRepo = $em->getRepository(Clock::class);

        $teams = $teamRepo->findAll();
        $users = $userRepo->findAll();
        $clocks = $clockRepo->findAll();

        // Group all clocks by user
        $userClocks = $this->groupClocksByUser($clocks);

        $teamClockCounts = [];

        foreach ($teams as $team) {
            // Filter users belonging to this team
            $teamUsers = array_filter($users, fn($u) => $u->getTeam() === $team || $u->getTeams()?->contains($team));
            $userCount = count($teamUsers);

            if ($userCount === 0) {
                $teamClockCounts[$team->getName()] = '00h 00m';
                continue;
            }

            // Compute total work time for all team users
            $totalSecondsForTeam = 0;
            foreach ($teamUsers as $user) {
                $records = $userClocks[$user->getId()] ?? [];
                $totalSecondsForTeam += $this->calculateTotalSeconds($records);
            }

            // Average work time
            $avgSeconds = $totalSecondsForTeam / $userCount;
            $teamClockCounts[$team->getName()] = $this->formatDuration($avgSeconds);
        }

        return new JsonResponse($teamClockCounts, 200);
    }

    private function groupClocksByUser(array $clocks): array
    {
        $grouped = [];
        foreach ($clocks as $clock) {
            $userId = $clock->getUser()->getId();
            $grouped[$userId][] = [
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp()
            ];
        }
        return $grouped;
    }

     //Calculates total worked seconds from arrival/departure records.
    private function calculateTotalSeconds(array $records): int
    {
        $arrivals = [];
        $departures = [];

        foreach ($records as $entry) {
            if ($entry['type'] === 'arrival') {
                $arrivals[] = $entry['timestamp'];
            } elseif ($entry['type'] === 'departure') {
                $departures[] = $entry['timestamp'];
            }
        }

        $totalSeconds = 0;
        $pairCount = min(count($arrivals), count($departures));

        for ($i = 0; $i < $pairCount; $i++) {
            $interval = $departures[$i]->getTimestamp() - $arrivals[$i]->getTimestamp();
            if ($interval > 0) {
                $totalSeconds += $interval;
            }
        }

        return $totalSeconds;
    }

    //format heures et minutes
    private function formatDuration(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%02dh %02dm', $hours, $minutes);
    }
}

