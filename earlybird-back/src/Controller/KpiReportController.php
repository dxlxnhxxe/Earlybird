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
    #[Route('/team-averages', name: 'team_averages', methods: ['GET'])]
    public function getTeamAverages(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $period = $request->query->get('period', 'day');
        if (!in_array($period, ['day','week','month','year'], true)) {
            return new JsonResponse([
                'error' => 'Invalid period value. The period parameter must be one of: day, week, month, or year.'
            ], 400);
        }

        // Build date range based on current date and selected period
        $now = new \DateTimeImmutable('now');
        switch ($period) {
            case 'week':
                // ISO week: Monday 00:00:00 to next Monday
                $start = $now->modify('monday this week')->setTime(0, 0, 0);
                $end = $start->modify('+1 week');
                break;
            case 'month':
                $start = $now->modify('first day of this month')->setTime(0, 0, 0);
                $end = $start->modify('+1 month');
                break;
            case 'year':
                $start = (new \DateTimeImmutable($now->format('Y-01-01 00:00:00')));
                $end = $start->modify('+1 year');
                break;
            default: // day
                $start = $now->setTime(0, 0, 0);
                $end = $start->modify('+1 day');
        }

        // Fetch clocks in range (single query) and map to team members and teams
        $clocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        list($memberClocksAll, $memberTeam) = $this->mapClocksToMembers($clocks);

        // Prepare per-team member clocks subsets
        $teams = $em->getRepository(Team::class)->findAll();
        $teamsOutput = [];
        foreach ($teams as $team) {
            $subset = [];
            foreach ($memberClocksAll as $memberId => $records) {
                if (isset($memberTeam[$memberId]) && $memberTeam[$memberId] === $team->getId()) {
                    $subset[$memberId] = $records;
                }
            }

            $teamData = $this->computeSingleTeamData($team, $subset, $period);
            $teamsOutput[] = $teamData;
        }

        return new JsonResponse([
            'teams' => $teamsOutput,
            'since' => $start->format('Y-m-d'),
            'until' => $end->format('Y-m-d'),
            'period' => $period,
        ]);
    }

    #[Route('/reports/team-kpis', name: 'reports_team_kpis', methods: ['GET'])]
    public function getTeamKpis(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $teamId = $request->query->get('team_id');
        $period = $request->query->get('period', 'day');

        if (!$teamId) {
            return new JsonResponse(['error' => 'Missing required parameter: team_id'], 400);
        }
        if (!in_array($period, ['day','week','month','year'], true)) {
            return new JsonResponse(['error' => 'Invalid period. Must be one of: day, week, month, year.'], 400);
        }

        $team = $em->getRepository(Team::class)->find($teamId);
        if (!$team) {
            return new JsonResponse(['error' => 'Team not found'], 404);
        }

        // Resolve date range
        $now = new \DateTimeImmutable('now');
        switch ($period) {
            case 'week':
                $start = $now->modify('monday this week')->setTime(0,0,0);
                $end = $start->modify('+1 week');
                break;
            case 'month':
                $start = $now->modify('first day of this month')->setTime(0,0,0);
                $end = $start->modify('+1 month');
                break;
            case 'year':
                $start = (new \DateTimeImmutable($now->format('Y-01-01 00:00:00')));
                $end = $start->modify('+1 year');
                break;
            default:
                $start = $now->setTime(0,0,0);
                $end = $start->modify('+1 day');
        }

        // Fetch all clocks for this team in range
        $clocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->where('tm.team = :teamId')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->orderBy('c.timestamp', 'ASC')
            ->setParameter('teamId', $team->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getResult();

        $totalClocks = count($clocks);
        $lastActivity = $this->getLastActivityTimestamp($clocks);

        // Build per-member clocks and user mapping
        $memberClocks = [];
        $memberClockCounts = [];
        $memberUser = [];
        foreach ($team->getMemberships() as $membership) {
            $memberClocks[$membership->getId()] = [];
            $memberClockCounts[$membership->getId()] = 0;
            $user = $membership->getUser();
            if ($user) {
                $memberUser[$membership->getId()] = [
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                ];
            }
        }
        $arrivalsByMember = [];
        $departuresByMember = [];
        $startBreakByMember = [];
        $endBreakByMember = [];
        $arrivalHours = [];
        $departureHours = [];
        foreach ($clocks as $clock) {
            $tm = $clock->getTeamMember();
            if (!$tm) continue;
            $mid = $tm->getId();
            $memberClocks[$mid][] = [
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp(),
            ];
            $memberClockCounts[$mid] = ($memberClockCounts[$mid] ?? 0) + 1;

            // For additional KPIs
            $type = $clock->getType();
            $ts = $clock->getTimestamp();
            if ($type === 'arrival') {
                $arrivalsByMember[$mid][] = $ts;
                $arrivalHours[] = (int)$ts->format('G');
            }
            if ($type === 'departure') {
                $departuresByMember[$mid][] = $ts;
                $departureHours[] = (int)$ts->format('G');
            }
            if ($type === 'start_break') {
                $startBreakByMember[$mid][] = $ts;
            }
            if ($type === 'end_break') {
                $endBreakByMember[$mid][] = $ts;
            }
        }

        // Compute average work seconds per member over the period granularity
        $memberAvgSeconds = $this->computeMemberWorkSeconds($memberClocks, $period);
        $membersCount = count($memberClocks);
        $avgClocksPerMember = $membersCount > 0 ? round($totalClocks / $membersCount, 2) : 0.0;

        $vals = array_values($memberAvgSeconds);
        sort($vals);
        $teamAvgSec = $this->averageOfArray($vals);
        $teamTotalSec = array_sum($vals);
        $teamMedianSec = 0;
        if (count($vals) > 0) {
            $mid = (int) floor((count($vals) - 1) / 2);
            if (count($vals) % 2 === 1) {
                $teamMedianSec = $vals[$mid];
            } else {
                $teamMedianSec = ($vals[$mid] + $vals[$mid + 1]) / 2;
            }
        }
        $teamMinSec = count($vals) ? min($vals) : 0;
        $teamMaxSec = count($vals) ? max($vals) : 0;

        // Find most active by clocks and by work time
        $mostActiveByClocks = null;
        if (!empty($memberClockCounts)) {
            $maxClocks = max($memberClockCounts);
            $mid = array_search($maxClocks, $memberClockCounts, true);
            $mostActiveByClocks = [
                'user' => $memberUser[$mid] ?? null,
                'total_clocks' => $maxClocks,
            ];
        }
        $mostActiveByWork = null;
        if (!empty($memberAvgSeconds)) {
            $maxWork = max($memberAvgSeconds);
            $mid = array_search($maxWork, $memberAvgSeconds, true);
            $mostActiveByWork = [
                'user' => $memberUser[$mid] ?? null,
                'avg_work_seconds' => $maxWork,
                'avg_work_time' => $this->formatDuration($maxWork),
            ];
        }

        // Lateness metrics and punctuality
        $clockTypes = ['arrival', 'end_work', 'end_break'];
        $lateClocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->join('tm.user', 'u')
            ->andWhere('tm.team = :teamId')
            ->andWhere('c.type IN (:types)')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->setParameter('teamId', $team->getId())
            ->setParameter('types', $clockTypes)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getResult();

        $latenessSecondsAll = [];
        $latenessByMember = [];
        $onTimeCount = 0;
        $arrivalCount = 0;
        foreach ($lateClocks as $clock) {
            if ($clock->getType() !== 'arrival') continue;
            $arrivalCount++;
            $tm = $clock->getTeamMember();
            $expectedStart = $tm ? $tm->getStartTime() : null;
            if (!$expectedStart) continue;
            $expectedDateTime = new \DateTimeImmutable(
                $clock->getTimestamp()->format('Y-m-d') . ' ' . $expectedStart->format('H:i:s')
            );
            $late = $clock->getTimestamp()->getTimestamp() - $expectedDateTime->getTimestamp();
            if ($late <= 0) {
                $onTimeCount++;
                $later = 0;
            } else {
                $later = $late;
            }
            $latenessSecondsAll[] = $later;
            $mid = $tm->getId();
            if (!isset($latenessByMember[$mid])) $latenessByMember[$mid] = [];
            $latenessByMember[$mid][] = $later;
        }
        $avgLateness = $this->averageOfArray($latenessSecondsAll);
        $punctualityRate = $arrivalCount > 0 ? $onTimeCount / $arrivalCount : 0.0;

        // Attendance and activity
        $activeMembers = 0;
        $sessionsTotal = 0;
        $sessionSecondsTotal = 0;
        $totalBreaks = 0;
        $breakSecondsTotal = 0;
        $earliestArrival = null; // DateTimeImmutable
        $latestDeparture = null;
        foreach ($memberClocks as $mid => $records) {
            $arrs = $arrivalsByMember[$mid] ?? [];
            $deps = $departuresByMember[$mid] ?? [];
            $pairs = min(count($arrs), count($deps));
            if ($pairs > 0) $activeMembers++;
            $sessionsTotal += $pairs;
            for ($i=0; $i<$pairs; $i++) {
                $sessionSecondsTotal += max(0, $deps[$i]->getTimestamp() - $arrs[$i]->getTimestamp());
            }
            if (!empty($arrs)) {
                $minArr = $arrs[0];
                foreach ($arrs as $a) { if ($minArr > $a) $minArr = $a; }
                if ($earliestArrival === null || $minArr < $earliestArrival) $earliestArrival = $minArr;
            }
            if (!empty($deps)) {
                $maxDep = $deps[0];
                foreach ($deps as $d) { if ($maxDep < $d) $maxDep = $d; }
                if ($latestDeparture === null || $maxDep > $latestDeparture) $latestDeparture = $maxDep;
            }

            // Breaks
            $sbs = $startBreakByMember[$mid] ?? [];
            $ebs = $endBreakByMember[$mid] ?? [];
            $bpairs = min(count($sbs), count($ebs));
            $totalBreaks += $bpairs;
            for ($i=0; $i<$bpairs; $i++) {
                $breakSecondsTotal += max(0, $ebs[$i]->getTimestamp() - $sbs[$i]->getTimestamp());
            }
        }
        $inactiveMembers = max(0, count($memberClocks) - $activeMembers);
        $attendanceRate = (count($memberClocks) > 0) ? $activeMembers / count($memberClocks) : 0.0;
        $avgSessionLength = ($sessionsTotal > 0) ? ($sessionSecondsTotal / $sessionsTotal) : 0.0;
        $avgBreaksPerMember = (count($memberClocks) > 0) ? ($totalBreaks / count($memberClocks)) : 0.0;
        $avgBreakDuration = ($totalBreaks > 0) ? ($breakSecondsTotal / $totalBreaks) : 0.0;

        // Start/End deviation from expected
        $arrivalDeviationSum = 0; $arrivalDeviationCount = 0;
        $departureDeviationSum = 0; $departureDeviationCount = 0;
        foreach ($memberClocks as $mid => $records) {
            $tm = null;
            // Find any membership by id
            foreach ($team->getMemberships() as $m) { if ($m->getId()===$mid) { $tm=$m; break; } }
            if (!$tm) continue;
            $expStart = $tm->getStartTime();
            $expEnd = $tm->getEndTime();
            foreach ($arrivalsByMember[$mid] ?? [] as $a) {
                if ($expStart) {
                    $expDT = new \DateTimeImmutable($a->format('Y-m-d').' '.$expStart->format('H:i:s'));
                    $arrivalDeviationSum += ($a->getTimestamp() - $expDT->getTimestamp());
                    $arrivalDeviationCount++;
                }
            }
            foreach ($departuresByMember[$mid] ?? [] as $d) {
                if ($expEnd) {
                    $expDT = new \DateTimeImmutable($d->format('Y-m-d').' '.$expEnd->format('H:i:s'));
                    // positive if departed after expected end; negative if early
                    $departureDeviationSum += ($d->getTimestamp() - $expDT->getTimestamp());
                    $departureDeviationCount++;
                }
            }
        }
        $avgArrivalDeviation = ($arrivalDeviationCount>0) ? ($arrivalDeviationSum/$arrivalDeviationCount) : 0.0;
        $avgDepartureDeviation = ($departureDeviationCount>0) ? ($departureDeviationSum/$departureDeviationCount) : 0.0;

        // Utilization vs expected schedule hours
        $daysCount = (int) ceil(($end->getTimestamp() - $start->getTimestamp()) / 86400);
        if ($daysCount < 1) $daysCount = 1;
        $expectedTotalSeconds = 0;
        foreach ($team->getMemberships() as $membership) {
            $st = $membership->getStartTime();
            $et = $membership->getEndTime();
            if ($st && $et) {
                $sched = max(0, ($et->getTimestamp() - $st->getTimestamp()));
                $expectedTotalSeconds += $sched * $daysCount;
            }
        }
        $utilizationRate = ($expectedTotalSeconds>0) ? ($teamTotalSec / $expectedTotalSeconds) : 0.0;

        // Spread metrics: stddev and p90 on member averages
        $stddev = 0.0; $p90 = 0.0;
        if (count($vals) > 0) {
            $mean = $teamAvgSec;
            $sumSq = 0.0; foreach ($vals as $v) { $sumSq += ($v-$mean)*($v-$mean); }
            $stddev = sqrt($sumSq / count($vals));
            $idx = (int) floor(0.9 * (count($vals)-1));
            $p90 = $vals[$idx];
        }

        // Peak activity hour from all clock events
        $hourBins = array_fill(0,24,0);
        foreach ($arrivalHours as $h) { $hourBins[$h]++; }
        foreach ($departureHours as $h) { $hourBins[$h]++; }
        $peakHour = 0; $peakVal = -1;
        foreach ($hourBins as $h=>$cnt) { if ($cnt>$peakVal) { $peakVal=$cnt; $peakHour=$h; } }

        // Punctuality per member: top punctual (min avg lateness) and top late (max)
        $topPunctual = null; $topLate = null;
        if (!empty($latenessByMember)) {
            $minAvg = null; $minMid = null; $maxAvg = null; $maxMid = null;
            foreach ($latenessByMember as $mid => $valsL) {
                $a = $this->averageOfArray($valsL);
                if ($minAvg===null || $a < $minAvg) { $minAvg = $a; $minMid = $mid; }
                if ($maxAvg===null || $a > $maxAvg) { $maxAvg = $a; $maxMid = $mid; }
            }
            if ($minMid!==null) {
                $topPunctual = [ 'user' => $memberUser[$minMid] ?? null, 'avg_lateness_seconds' => (int)round($minAvg), 'avg_lateness' => $this->formatDuration($minAvg) ];
            }
            if ($maxMid!==null) {
                $topLate = [ 'user' => $memberUser[$maxMid] ?? null, 'avg_lateness_seconds' => (int)round($maxAvg), 'avg_lateness' => $this->formatDuration($maxAvg) ];
            }
        }

        return new JsonResponse([
            'team_id' => $team->getId(),
            'period' => $period,
            'since' => $start->format('Y-m-d'),
            'until' => $end->format('Y-m-d'),
            'summary' => [
                'members_count' => $membersCount,
                'active_members_count' => $activeMembers,
                'inactive_members_count' => $inactiveMembers,
                'attendance_rate' => $attendanceRate,
                'total_clocks' => $totalClocks,
                'average_clocks_per_member' => $avgClocksPerMember,
                'last_activity' => $lastActivity,
                'team_average_work_time_seconds' => (int) round($teamAvgSec),
                'team_average_work_time' => $this->formatDuration($teamAvgSec),
                'team_total_work_time_seconds' => (int) round($teamTotalSec),
                'team_total_work_time' => $this->formatDuration($teamTotalSec),
                'team_median_work_time_seconds' => (int) round($teamMedianSec),
                'team_median_work_time' => $this->formatDuration($teamMedianSec),
                'team_min_work_time_seconds' => (int) round($teamMinSec),
                'team_min_work_time' => $this->formatDuration($teamMinSec),
                'team_max_work_time_seconds' => (int) round($teamMaxSec),
                'team_max_work_time' => $this->formatDuration($teamMaxSec),
                'average_lateness_seconds' => (int) round($avgLateness),
                'average_lateness' => $this->formatDuration($avgLateness),
                'punctuality_rate' => $punctualityRate,
                'punctuality_percent' => round($punctualityRate * 100, 2),
                'sessions_total' => $sessionsTotal,
                'average_session_length_seconds' => (int) round($avgSessionLength),
                'average_session_length' => $this->formatDuration($avgSessionLength),
                'total_breaks' => $totalBreaks,
                'average_breaks_per_member' => $avgBreaksPerMember,
                'average_break_duration_seconds' => (int) round($avgBreakDuration),
                'average_break_duration' => $this->formatDuration($avgBreakDuration),
                'earliest_arrival' => $earliestArrival ? $earliestArrival->format('H:i') : null,
                'latest_departure' => $latestDeparture ? $latestDeparture->format('H:i') : null,
                'average_arrival_deviation_seconds' => (int) round($avgArrivalDeviation),
                'average_arrival_deviation' => $this->formatDuration($avgArrivalDeviation),
                'average_departure_deviation_seconds' => (int) round($avgDepartureDeviation),
                'average_departure_deviation' => $this->formatDuration($avgDepartureDeviation),
                'expected_total_work_time_seconds' => (int) $expectedTotalSeconds,
                'expected_total_work_time' => $this->formatDuration($expectedTotalSeconds),
                'utilization_rate' => $utilizationRate,
                'utilization_percent' => round($utilizationRate * 100, 2),
                'stddev_work_time_seconds' => (int) round($stddev),
                'stddev_work_time' => $this->formatDuration($stddev),
                'p90_work_time_seconds' => (int) round($p90),
                'p90_work_time' => $this->formatDuration($p90),
                'peak_activity_hour' => $peakHour,
            ],
            'most_active_members' => [
                'by_clocks' => $mostActiveByClocks,
                'by_work_time' => $mostActiveByWork,
            ],
            'punctuality' => [
                'top_punctual' => $topPunctual,
                'top_late' => $topLate,
            ],
        ]);
    }

    #[Route('/reports/team-members-kpis', name: 'reports_team_members_kpis', methods: ['GET'])]
    public function getTeamMembersKpis(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $teamId = $request->query->get('team_id');
        $period = $request->query->get('period', 'day');

        if (!$teamId) {
            return new JsonResponse(['error' => 'Missing required parameter: team_id'], 400);
        }
        if (!in_array($period, ['day','week','month','year'], true)) {
            return new JsonResponse(['error' => 'Invalid period. Must be one of: day, week, month, year.'], 400);
        }

        $team = $em->getRepository(Team::class)->find($teamId);
        if (!$team) {
            return new JsonResponse(['error' => 'Team not found'], 404);
        }

        // Date range
        $now = new \DateTimeImmutable('now');
        switch ($period) {
            case 'week':
                $start = $now->modify('monday this week')->setTime(0,0,0); $end = $start->modify('+1 week'); break;
            case 'month':
                $start = $now->modify('first day of this month')->setTime(0,0,0); $end = $start->modify('+1 month'); break;
            case 'year':
                $start = (new \DateTimeImmutable($now->format('Y-01-01 00:00:00'))); $end = $start->modify('+1 year'); break;
            default:
                $start = $now->setTime(0,0,0); $end = $start->modify('+1 day');
        }

        // Fetch clocks for team
        $clocks = $em->createQueryBuilder()
            ->select('c')
            ->from(Clock::class, 'c')
            ->join('c.teamMember', 'tm')
            ->join('tm.user', 'u')
            ->where('tm.team = :teamId')
            ->andWhere('c.timestamp >= :start')
            ->andWhere('c.timestamp < :end')
            ->orderBy('c.timestamp', 'ASC')
            ->setParameter('teamId', $team->getId())
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getResult();

        // Group by team member
        $memberClocks = [];
        $userByMember = [];
        foreach ($team->getMemberships() as $membership) {
            $memberClocks[$membership->getId()] = [];
            $user = $membership->getUser();
            if ($user) {
                $userByMember[$membership->getId()] = [
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                ];
            }
        }
        foreach ($clocks as $clock) {
            $tm = $clock->getTeamMember();
            if (!$tm) continue;
            $mid = $tm->getId();
            $memberClocks[$mid][] = [
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp(),
            ];
        }

        // Compute per-member KPIs
        $rows = [];
        foreach ($memberClocks as $mid => $records) {
            $user = $userByMember[$mid] ?? null;

            // Sessions & total work seconds
            $arrivals = []; $departures = []; $startBreak = []; $endBreak = [];
            $totalClocks = count($records);
            foreach ($records as $r) {
                $t = $r['type']; $ts = $r['timestamp'];
                if ($t === 'arrival') $arrivals[] = $ts;
                if ($t === 'departure') $departures[] = $ts;
                if ($t === 'start_break') $startBreak[] = $ts;
                if ($t === 'end_break') $endBreak[] = $ts;
            }
            $pairs = min(count($arrivals), count($departures));
            $sessionsTotal = $pairs;
            $totalSeconds = 0;
            for ($i=0; $i<$pairs; $i++) {
                $totalSeconds += max(0, $departures[$i]->getTimestamp() - $arrivals[$i]->getTimestamp());
            }
            $breakPairs = min(count($startBreak), count($endBreak));
            $breakSeconds = 0; for ($i=0; $i<$breakPairs; $i++) { $breakSeconds += max(0, $endBreak[$i]->getTimestamp() - $startBreak[$i]->getTimestamp()); }

            // Average per selected period granularity using helper
            $avgPerPeriod = 0; $memberAvg = $this->computeMemberWorkSeconds([$mid => $records], $period); if (isset($memberAvg[$mid])) $avgPerPeriod = $memberAvg[$mid];

            // Lateness and punctuality for member
            $tm = null; foreach ($team->getMemberships() as $m) { if ($m->getId()===$mid) { $tm=$m; break; } }
            $avgLateness = 0.0; $onTime=0; $arrCnt=0; $firstArrival=null; $lastDeparture=null;
            foreach ($arrivals as $a) {
                $arrCnt++;
                if ($tm && $tm->getStartTime()) {
                    $exp = new \DateTimeImmutable($a->format('Y-m-d').' '.$tm->getStartTime()->format('H:i:s'));
                    $diff = $a->getTimestamp() - $exp->getTimestamp();
                    if ($diff <= 0) $onTime++;
                    $avgLateness += max(0, $diff);
                }
                if ($firstArrival===null || $a < $firstArrival) $firstArrival=$a;
            }
            if ($arrCnt>0) $avgLateness = $avgLateness / $arrCnt; else $avgLateness = 0.0;
            foreach ($departures as $d) { if ($lastDeparture===null || $d > $lastDeparture) $lastDeparture=$d; }
            $punctualityRate = ($arrCnt>0) ? ($onTime/$arrCnt) : 0.0;

            $rows[] = [
                'user' => $user,
                'total_clocks' => $totalClocks,
                'sessions_total' => $sessionsTotal,
                'total_work_seconds' => (int) $totalSeconds,
                'total_work_time' => $this->formatDuration($totalSeconds),
                'avg_work_seconds_per_'.$period => (int) round($avgPerPeriod),
                'avg_work_time_per_'.$period => $this->formatDuration($avgPerPeriod),
                'total_breaks' => $breakPairs,
                'total_break_seconds' => (int) $breakSeconds,
                'total_break_time' => $this->formatDuration($breakSeconds),
                'avg_lateness_seconds' => (int) round($avgLateness),
                'avg_lateness' => $this->formatDuration($avgLateness),
                'punctuality_rate' => $punctualityRate,
                'first_arrival' => $firstArrival ? $firstArrival->format('H:i') : null,
                'last_departure' => $lastDeparture ? $lastDeparture->format('H:i') : null,
            ];
        }

        return new JsonResponse([
            'team_id' => $team->getId(),
            'period' => $period,
            'since' => $start->format('Y-m-d'),
            'until' => $end->format('Y-m-d'),
            'members' => $rows,
        ]);
    }
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
