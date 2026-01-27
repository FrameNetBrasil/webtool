<?php

namespace App\Repositories\Parser;

use Illuminate\Support\Facades\DB;

/**
 * State Snapshot Repository
 *
 * Data access layer for parser state snapshots.
 * Handles CRUD operations for the parser_state_snapshot_v5 table.
 */
class StateSnapshotRepository
{
    private string $table = 'parser_state_snapshot_v5';

    /**
     * Create a new snapshot
     *
     * @param  array  $data  Snapshot data
     * @return int Snapshot ID
     */
    public function create(array $data): int
    {
        // Ensure JSON fields are properly encoded
        $data['tokenData'] = $this->encodeJson($data['tokenData'] ?? null);
        $data['tokenGraph'] = $this->encodeJson($data['tokenGraph']);
        $data['activeAlternatives'] = $this->encodeJson($data['activeAlternatives'] ?? null);
        $data['ghostNodes'] = $this->encodeJson($data['ghostNodes'] ?? null);
        $data['confirmedNodes'] = $this->encodeJson($data['confirmedNodes'] ?? null);
        $data['confirmedEdges'] = $this->encodeJson($data['confirmedEdges'] ?? null);
        $data['reconfigurations'] = $this->encodeJson($data['reconfigurations'] ?? null);
        $data['statistics'] = $this->encodeJson($data['statistics'] ?? null);

        return DB::table($this->table)->insertGetId($data);
    }

    /**
     * Find a snapshot by parser graph and position
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $position  Position in sentence
     * @return array|null Snapshot data
     */
    public function findByPosition(int $idParserGraph, int $position): ?array
    {
        $snapshot = DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->where('position', $position)
            ->first();

        return $snapshot ? $this->decodeJsonFields((array) $snapshot) : null;
    }

    /**
     * Find all snapshots for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array Array of snapshots ordered by position
     */
    public function findByParserGraph(int $idParserGraph): array
    {
        $snapshots = DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->orderBy('position')
            ->get();

        return array_map(
            fn ($snapshot) => $this->decodeJsonFields((array) $snapshot),
            $snapshots->toArray()
        );
    }

    /**
     * Find snapshots in a position range
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $startPosition  Start position (inclusive)
     * @param  int  $endPosition  End position (inclusive)
     * @return array Array of snapshots
     */
    public function findByPositionRange(int $idParserGraph, int $startPosition, int $endPosition): array
    {
        $snapshots = DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->whereBetween('position', [$startPosition, $endPosition])
            ->orderBy('position')
            ->get();

        return array_map(
            fn ($snapshot) => $this->decodeJsonFields((array) $snapshot),
            $snapshots->toArray()
        );
    }

    /**
     * Delete all snapshots for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return int Number of snapshots deleted
     */
    public function deleteByParserGraph(int $idParserGraph): int
    {
        return DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->delete();
    }

    /**
     * Delete a specific snapshot
     *
     * @param  int  $idSnapshot  Snapshot ID
     * @return bool Success status
     */
    public function delete(int $idSnapshot): bool
    {
        return DB::table($this->table)
            ->where('idSnapshot', $idSnapshot)
            ->delete() > 0;
    }

    /**
     * Get snapshot count for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return int Number of snapshots
     */
    public function count(int $idParserGraph): int
    {
        return DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->count();
    }

    /**
     * Check if snapshots exist for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return bool True if snapshots exist
     */
    public function exists(int $idParserGraph): bool
    {
        return DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->exists();
    }

    /**
     * Get the latest snapshot for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array|null Snapshot data
     */
    public function getLatest(int $idParserGraph): ?array
    {
        $snapshot = DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->orderBy('position', 'desc')
            ->first();

        return $snapshot ? $this->decodeJsonFields((array) $snapshot) : null;
    }

    /**
     * Get positions with snapshots for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array Array of positions
     */
    public function getPositions(int $idParserGraph): array
    {
        return DB::table($this->table)
            ->where('idParserGraph', $idParserGraph)
            ->orderBy('position')
            ->pluck('position')
            ->toArray();
    }

    /**
     * Encode a value to JSON if needed
     *
     * @param  mixed  $value  Value to encode
     * @return string|null JSON string or null
     */
    private function encodeJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // If already a string, assume it's already JSON encoded
        if (is_string($value)) {
            return $value;
        }

        // Encode arrays and objects to JSON
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        // For scalar values, encode them as JSON
        return json_encode($value);
    }

    /**
     * Decode JSON fields in snapshot
     *
     * @param  array  $snapshot  Raw snapshot data
     * @return array Snapshot with decoded JSON fields
     */
    private function decodeJsonFields(array $snapshot): array
    {
        $jsonFields = [
            'tokenData',
            'tokenGraph',
            'activeAlternatives',
            'ghostNodes',
            'confirmedNodes',
            'confirmedEdges',
            'reconfigurations',
            'statistics',
        ];

        foreach ($jsonFields as $field) {
            if (isset($snapshot[$field]) && is_string($snapshot[$field])) {
                $snapshot[$field] = json_decode($snapshot[$field], true);
            }
        }

        return $snapshot;
    }
}
