<?php

namespace App;

class StatisticsManager
{
    private \PDO $pdo;

    public function __construct(string $dbPath = __DIR__ . '/../storage/database.sqlite')
    {
        $dsn = str_starts_with($dbPath, 'sqlite:') ? $dbPath : 'sqlite:' . $dbPath;
        $this->pdo = new \PDO($dsn);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function updateTeamStatistics(string $matchId, string $teamId, string $statType, int $value = 1): void
    {
    // Statistics are derived directly via queries now
    }

    public function getTeamStatistics(string $matchId, string $teamId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT type, COUNT(*) as count 
            FROM events 
            WHERE match_id = :match_id AND team_id = :team_id 
            GROUP BY type
        ");

        $stmt->execute([
            ':match_id' => $matchId,
            ':team_id' => $teamId
        ]);

        $stats = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats[$row['type'] . 's'] = (int)$row['count'];
        }

        return $stats;
    }

    public function getMatchStatistics(string $matchId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT team_id, type, COUNT(*) as count 
            FROM events 
            WHERE match_id = :match_id 
            GROUP BY team_id, type
        ");

        $stmt->execute([
            ':match_id' => $matchId
        ]);

        $stats = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $teamId = $row['team_id'];
            $type = $row['type'] . 's';

            if (!isset($stats[$teamId])) {
                $stats[$teamId] = [];
            }

            $stats[$teamId][$type] = (int)$row['count'];
        }

        return $stats;
    }
}
