<?php

namespace App\Controller\Reports;

use App\Entity\Clock;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TeamReportController
{
    #[Route('/team-averages', name: 'team_averages', methods: ['GET'])]
    public function getTeamsAverageWorkTime(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'day');
        $month = $request->query->get('month');
        $year = $request->query->get('year');

        if (!in_array($period, ['day', 'week', 'month'], true)) {
            return new JsonResponse(['error' => 'The period parameter must be one of: day, week, month.'], 400);
        }

        $now = new \DateTimeImmutable('now');

        if ($month && $year) {
            try {
                $start = new \DateTimeImmutable("$year-$month-01 00:00:00");
                $end = $start->modify('+1 month');
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid month/year'], 400);
            }
        } else {
            // default last 365 days
            $start = $now->modify('-365 days');
            $end = $now;
        }

        // Fetch clocks in period
        $clocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->where('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('c.timestamp', 'ASC')
            ->getQuery()
            ->getResult();

        // Map clocks to member & team
        [$memberClocks, $memberTeam] = $this->mapClocksToMembers($clocks);

        // Compute total seconds per member
        $memberSeconds = $this->computeMemberWorkSeconds($memberClocks, $period);

        // Aggregate by team
        $teamData = $this->buildTeamData($em, $memberSeconds, $memberTeam, $period);

        // Format final averages
        $this->finalizeTeamAverages($teamData);

        usort($teamData, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        return new JsonResponse([
            'since' => $start->format('Y-m-d'),
            'until' => $end->format('Y-m-d'),
            'period' => $period,
            'teams' => array_values($teamData),
        ]);
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

    private function computeMemberWorkSeconds(array $memberClocks, string $period): array
    {
        $result = [];

        foreach ($memberClocks as $memberId => $records) {
            // Group records by period key
            $grouped = [];
            foreach ($records as $r) {
                $ts = $r['timestamp'];
                switch ($period) {
                    case 'week':
                        $key = $ts->format('o-W'); // ISO week
                        break;
                    case 'month':
                        $key = $ts->format('Y-m'); // e.g., 2025-10
                        break;
                    default:
                        $key = $ts->format('Y-m-d'); // per day
                }
                $grouped[$key][] = $r;
            }

            // Sum seconds per period
            $totalSeconds = 0;
            foreach ($grouped as $periodKey => $periodRecords) {
                $totalSeconds += $this->calculateTotalSeconds($periodRecords);
            }

            // Average per period
            $periodCount = count($grouped);
            $result[$memberId] = $periodCount > 0 ? $totalSeconds / $periodCount : 0;
        }

        return $result;
    }

    private function calculateTotalSeconds(array $records): int
    {
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

        return $total;
    }

    private function buildTeamData(EntityManagerInterface $em, array $memberSeconds, array $memberTeam, string $period): array
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

        foreach ($memberSeconds as $tmId => $avgSeconds) {
            $tid = $memberTeam[$tmId] ?? null;
            if ($tid === null || !isset($data[$tid])) continue;

            $data[$tid]['_sum'] = ($data[$tid]['_sum'] ?? 0) + $avgSeconds;
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

    private function formatSeconds(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        return sprintf('%02dh %02dm', $h, $m);
    }
}
