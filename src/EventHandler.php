<?php

namespace App;

class EventHandler
{
    private DatabaseStorage $storage;
    private StatisticsManager $statisticsManager;

    public function __construct(string $storagePath, ?StatisticsManager $statisticsManager = null)
    {
        $this->storage = new DatabaseStorage($storagePath);
        $this->statisticsManager = $statisticsManager ?? new StatisticsManager(__DIR__ . '/../storage/statistics.txt');
    }

    public function handleEvent(array $data): array
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('Event type is required');
        }

        $event = [
            'type' => $data['type'],
            'timestamp' => time(),
            'data' => $data
        ];

        $this->storage->save($event);

        // Update statistics for events
        if ($data['type'] === 'foul') {
            if (!isset($data['match_id']) || !isset($data['team_id'])) {
                throw new \InvalidArgumentException('match_id and team_id are required for foul events');
            }

            $this->statisticsManager->updateTeamStatistics(
                $data['match_id'],
                $data['team_id'],
                'fouls'
            );
        }
        elseif ($data['type'] === 'goal') {
            if (!isset($data['scorer']) || !isset($data['team_id']) || !isset($data['match_id']) || !isset($data['minute'])) {
                throw new \InvalidArgumentException('scorer, team_id, match_id, and minute are required for goal events');
            }

            $this->statisticsManager->updateTeamStatistics(
                $data['match_id'],
                $data['team_id'],
                'goals'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Event saved successfully',
            'event' => $event
        ];
    }
}