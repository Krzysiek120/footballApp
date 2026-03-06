<?php

namespace App;

use PDO;

class DatabaseStorage
{
    private PDO $pdo;

    public function __construct(string $dbPath = 'sqlite::memory:')
    {
        $dsn = str_starts_with($dbPath, 'sqlite:') ? $dbPath : 'sqlite:' . $dbPath;

        if (!str_starts_with($dbPath, 'sqlite::memory:') && !str_starts_with($dbPath, 'sqlite:')) {
            $directory = dirname($dbPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                timestamp INTEGER NOT NULL,
                match_id TEXT,
                team_id TEXT,
                data TEXT NOT NULL
            )
        ");
    }

    public function save(array $event): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO events (type, timestamp, match_id, team_id, data) 
            VALUES (:type, :timestamp, :match_id, :team_id, :data)
        ");

        $data = $event['data'] ?? [];

        $stmt->execute([
            ':type' => $event['type'],
            ':timestamp' => $event['timestamp'],
            ':match_id' => $data['match_id'] ?? null,
            ':team_id' => $data['team_id'] ?? null,
            ':data' => json_encode($event['data'])
        ]);
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM events ORDER BY timestamp ASC, id ASC");
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'type' => $row['type'],
                'timestamp' => (int)$row['timestamp'],
                'data' => json_decode($row['data'], true)
            ];
        }

        return $results;
    }
}
