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
        $avgClocksPerUser = $totalUsers > 0 ? round($totalClocks / $totalUsers, 2) : 0;

        $userClockCounts = [];
        foreach ($clocks as $clock) {
            $teamMember = $clock->getTeamMember();
            if ($teamMember && $teamMember->getUser()) {
                $userId = $teamMember->getUser()->getId();
                $userClockCounts[$userId] = ($userClockCounts[$userId] ?? 0) + 1;
            }
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
    #[Route('/reports/filter', name: 'reports_global_filtered', methods: ['GET'])]
    public function getGlobalReportFiltered(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $month = $request->query->get('month');
        $year = $request->query->get('year');

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
        $users = $em->getRepository(User::class)->findAll();

        // Si aucune activité pour le mois choisi
        if (empty($clocks)) {
            return new JsonResponse([
                'filters' => [
                    'month' => $month ?? 'all',
                    'year' => $year ?? 'all'
                ],
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

        // Statistiques
        $totalUsers = count($users);
        $totalClocks = count($clocks);
        $avgClocksPerUser = $totalUsers > 0 ? round($totalClocks / $totalUsers, 2) : 0;


        // Regrouper les clocks par utilisateur via TeamMember
        $userClocks = [];
        foreach ($clocks as $clock) {
            $teamMember = $clock->getTeamMember();
            if ($teamMember && $teamMember->getUser()) {
                $userId = $teamMember->getUser()->getId();
                $userClocks[$userId][] = [
                    'type' => $clock->getType(),
                    'timestamp' => $clock->getTimestamp()
                ];
            }
        }

        // Calcul du temps total travaillé par utilisateur
        $userDurations = [];
        $globalTotalSeconds = 0;

        foreach ($userClocks as $userId => $records) {
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

            $globalTotalSeconds += $totalSeconds;
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);

            $userDurations[$userId] = [
                'total_work_time' => sprintf('%02dh %02dm', $hours, $minutes),
                'total_seconds' => $totalSeconds,
            ];
        }

        // Dernière activité
        $lastActivity = null;
        if (!empty($clocks)) {
            usort($clocks, fn($a, $b) => $b->getTimestamp() <=> $a->getTimestamp());
            $lastActivity = $clocks[0]->getTimestamp()->format('Y-m-d H:i:s');
        }

        // Moyenne du temps travaillé globalement
        $avgWorkTime = $totalUsers > 0 && $globalTotalSeconds > 0
            ? sprintf('%02dh %02dm', floor(($globalTotalSeconds / $totalUsers) / 3600), floor((($globalTotalSeconds / $totalUsers) % 3600) / 60))
            : '00h 00m';

        // Construire la réponse finale
        $reportUsers = [];
        foreach ($users as $user) {
            $uid = $user->getId();
            $reportUsers[] = [
                'id' => $uid,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'total_work_time' => $userDurations[$uid]['total_work_time'] ?? '00h 00m',
            ];
        }

        return new JsonResponse([
            'filters' => [
                'month' => $month ?? 'all',
                'year' => $year ?? 'all'
            ],
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
}