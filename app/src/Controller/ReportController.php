<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Clock;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReportController extends AbstractController
{
    #[Route('/reports', name: 'reports_global', methods: ['GET'])]
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

    #[Route('/reports/filter', name: 'reports_global_filtered', methods: ['GET'])]
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

    #[Route('/reports/team-averages', name: 'reports_team_averages', methods: ['GET'])]
    public function getTeamsAverageWorkTime(EntityManagerInterface $em): JsonResponse
    {
        $now = new \DateTimeImmutable('now');
        $since = $now->modify('-365 days');

        $clocks = $this->fetchClocksSince($em, $since);
        [$memberClocks, $memberTeam] = $this->mapClocksToMembers($clocks);
        $memberSeconds = $this->computeMemberWorkSeconds($memberClocks);

        $teamData = $this->buildTeamData($em, $memberSeconds, $memberTeam);
        $this->finalizeTeamAverages($teamData);

        usort($teamData, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return new JsonResponse([
            'since' => $since->format('Y-m-d'),
            'until' => $now->format('Y-m-d'),
            'teams' => array_values($teamData),
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

    /* ---------- TEAM AVERAGE HELPERS ---------- */

    private function fetchClocksSince(EntityManagerInterface $em, \DateTimeImmutable $since): array
    {
        return $em->createQueryBuilder()
            ->select('c')->from(Clock::class, 'c')
            ->where('c.timestamp >= :since')
            ->setParameter('since', $since)
            ->orderBy('c.timestamp', 'ASC')
            ->getQuery()->getResult();
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

    private function computeMemberWorkSeconds(array $memberClocks): array
    {
        $result = [];
        foreach ($memberClocks as $id => $records) {
            usort($records, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
            $total = 0;
            $open = null;

            foreach ($records as $r) {
                if ($r['type'] === 'arrival' && !$open) {
                    $open = $r['timestamp'];
                } elseif ($r['type'] === 'departure' && $open) {
                    $delta = $r['timestamp']->getTimestamp() - $open->getTimestamp();
                    if ($delta > 0) $total += $delta;
                    $open = null;
                }
            }
            $result[$id] = $total;
        }
        return $result;
    }

    private function buildTeamData(EntityManagerInterface $em, array $memberSeconds, array $memberTeam): array
    {
        $teams = $em->getRepository(Team::class)->findAll();
        $data = [];

        foreach ($teams as $team) {
            $data[$team->getId()] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'average_work_time_seconds' => 0,
                'average_work_time' => '00h 00m',
                'members_count' => 0,
            ];
        }

        foreach ($memberSeconds as $tmId => $seconds) {
            $tid = $memberTeam[$tmId] ?? null;
            if ($tid === null || !isset($data[$tid])) continue;

            $avgPerDay = $seconds / 365;
            $data[$tid]['_sum'] = ($data[$tid]['_sum'] ?? 0) + $avgPerDay;
            $data[$tid]['_count'] = ($data[$tid]['_count'] ?? 0) + 1;
        }

        return $data;
    }

    private function finalizeTeamAverages(array &$teams): void
    {
        foreach ($teams as &$t) {
            $count = $t['_count'] ?? 0;
            $avgSec = $count > 0 ? (int) round($t['_sum'] / $count) : 0;

            $t['members_count'] = $count;
            $t['average_work_time_seconds'] = $avgSec;
            $t['average_work_time'] = $this->formatSeconds($avgSec);

            unset($t['_sum'], $t['_count']);
        }
    }
}
