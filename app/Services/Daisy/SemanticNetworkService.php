<?php

namespace App\Services\Daisy;

use App\Data\Daisy\PoolObjectData;
use App\Database\Criteria;
use Illuminate\Support\Facades\Cache;

/**
 * SemanticNetworkService - Semantic Network Construction
 *
 * Responsible for:
 * - Building semantic networks for each frame candidate
 * - Querying frame-to-frame relations (inheritance, perspective_on, subframe)
 * - Querying Frame Element constraints
 * - Querying Qualia relations
 * - Creating pool objects for spreading activation
 */
class SemanticNetworkService
{
    private int $idLanguage;

    private int $searchType;

    private int $level;

    private array $relationWeights;

    private array $feWeights;

    private array $energyBonus;

    private array $networkExpansion;

    public function __construct(int $idLanguage, int $searchType, int $level)
    {
        $this->idLanguage = $idLanguage;
        $this->searchType = $searchType;
        $this->level = $level;
        $this->relationWeights = config('daisy.relationWeights');
        $this->feWeights = config('daisy.feWeights');
        $this->energyBonus = config('daisy.energyBonus');
        $this->networkExpansion = config('daisy.networkExpansion');
    }

    /**
     * Build semantic network for all frame candidates
     *
     * @param  array  $windows  Frame candidates indexed by window, word, frameEntry
     * @return array Windows with pool objects added to each frame candidate
     */
    public function buildSemanticNetworks(array $windows): array
    {
        foreach ($windows as $idWindow => $words) {
            foreach ($words as $word => $frames) {
                foreach ($frames as $frameEntry => $frame) {
                    // Build semantic network for this frame
                    $pool = $this->buildFrameNetwork($frame, $windows, $idWindow);

                    // Populate pool sets with word energy contributions
                    $pool = $this->populatePoolSets($pool, $windows, $idWindow);

                    $windows[$idWindow][$word][$frameEntry]->pool = $pool;
                }
            }
        }

        return $windows;
    }

    /**
     * Populate pool object sets with word energy contributions
     */
    private function populatePoolSets(array $pool, array $windows, int $currentWindowId): array
    {
        // For each pool object (related frame)
        foreach ($pool as $poolFrameName => $poolObject) {
            // Check all words in the current window
            foreach ($windows[$currentWindowId] as $word => $frames) {
                foreach ($frames as $candidateFrameEntry => $candidateFrame) {
                    // If word evokes a frame that matches this pool frame
                    if ($candidateFrameEntry === $poolFrameName) {
                        // Add this word as a contributor to the pool set
                        $poolObject->addContributor(
                            word: $word,
                            frame: $candidateFrameEntry,
                            energy: $candidateFrame->energy,
                            iword: $candidateFrame->iword,
                            level: $poolObject->level,
                            idWindow: $currentWindowId,
                            isQualia: false
                        );

                        debug("        Added contributor: word='{$word}', frame='{$candidateFrameEntry}', energy={$candidateFrame->energy}");
                    }
                }
            }
        }

        return $pool;
    }

    /**
     * Build semantic network for a single frame
     */
    private function buildFrameNetwork(object $frame, array $windows, int $idWindow): array
    {
        $pool = [];

        debug("Building network for frame: {$frame->frameEntry} (idFrame={$frame->idFrame}), searchType={$this->searchType}, level={$this->level}");

        // Step 1: Add direct frame
        $pool[$frame->frameEntry] = new PoolObjectData(
            frameName: $frame->frameEntry,
            factor: 1.0,
            baseFrame: $frame->frameEntry,
            level: 1
        );
        debug('  Step 1: Added direct frame to pool');

        // Step 2: Frame family relations (searchType >= 2)
        if ($this->searchType >= 2) {
            debug('  Step 2: Querying frame relations...');
            $frameRelations = $this->queryFrameRelations($frame->idFrame);
            debug('  Found {count} frame relations', ['count' => count($frameRelations)]);
            if (! empty($frameRelations)) {
                debug('  Frame relations:', array_column($frameRelations, 'frameEntry'));
            }
            $expanded = $this->expandFrameRelations($frameRelations, $frame->frameEntry, 1.0, $this->level);
            debug('  Expanded to {count} pool entries', ['count' => count($expanded)]);
            $pool = array_merge($pool, $expanded);
        } else {
            debug('  Step 2: SKIPPED (searchType < 2)');
        }

        // Step 3: Frame Element constraints (searchType >= 3)
        if ($this->searchType >= 3) {
            debug('  Step 3: Querying FE constraints...');
            $feConstraints = $this->queryFEConstraints($frame->idFrame);
            debug('  Found {count} FE constraints', ['count' => count($feConstraints)]);
            $pool = array_merge($pool, $this->processFEConstraints($feConstraints, $frame->frameEntry));
        } else {
            debug('  Step 3: SKIPPED (searchType < 3)');
        }

        // Step 4: Qualia relations (searchType >= 4)
        if ($this->searchType >= 4) {
            debug('  Step 4: Querying qualia relations...');
            $qualiaRelations = $this->queryQualiaRelations($frame->idLU, $windows);
            debug('  Found {count} qualia relations', ['count' => count($qualiaRelations)]);
            $pool = array_merge($pool, $this->processQualiaRelations($qualiaRelations, $frame->frameEntry, $idWindow));
        } else {
            debug('  Step 4: SKIPPED (searchType < 4)');
        }

        debug('  Final pool size: {count} entries', ['count' => count($pool)]);

        return $pool;
    }

    /**
     * Query frame-to-frame relations
     */
    private function queryFrameRelations(int $idFrame): array
    {
        $cacheKey = "daisy:frame_relations:{$idFrame}";
        $ttl = config('daisy.cacheTTL.frameRelations');

        return Cache::remember($cacheKey, $ttl, function () use ($idFrame) {
            try {
                $relations = Criteria::table('frame')
                    ->selectRaw('frame.idFrame, rt.entry as relationType, frame.Entry as frameEntry')
                    ->join('entity as entity1', 'frame.idEntity', '=', 'entity1.idEntity')
                    ->join('entityrelation as er', 'entity1.idEntity', '=', 'er.idEntity1')
                    ->join('relationtype as rt', 'er.idRelationType', '=', 'rt.idRelationType')
                    ->join('entity as entity2', 'er.idEntity2', '=', 'entity2.idEntity')
                    ->join('frame as relatedFrame', 'entity2.idEntity', '=', 'relatedFrame.idEntity')
                    ->where('relatedFrame.idFrame', '=', $idFrame)
                    ->whereIn('rt.entry', ['rel_inheritance', 'rel_perspective_on', 'rel_subframe', 'rel_using'])
                    ->orderBy('rt.entry')
                    ->orderBy('relatedFrame.idFrame')
                    ->get()
                    ->toArray();

                return array_map(fn ($rel) => (array) $rel, $relations);
            } catch (\Exception $e) {
                logger()->error('Daisy frame relations query error: '.$e->getMessage());

                return [];
            }
        });
    }

    /**
     * Expand frame relations recursively
     */
    private function expandFrameRelations(array $relations, string $baseFrame, float $parentValue, int $remainingLevels): array
    {
        $pool = [];

        foreach ($relations as $relation) {
            $relationType = $relation['relationType'];
            $frameEntry = $relation['frameEntry'];

            // Get weight for this relation type
            $weight = $this->relationWeights[$relationType] ?? 0.0;

            if ($weight <= 0) {
                continue; // Skip unused relations
            }

            $factor = $weight * $parentValue;

            // Create pool object
            $poolObj = new PoolObjectData(
                frameName: $frameEntry,
                factor: $factor,
                baseFrame: $baseFrame,
                level: $this->level - $remainingLevels + 2
            );

            $pool[$frameEntry] = $poolObj;

            // Recursive expansion if levels remain
            if ($remainingLevels > 1) {
                $newValue = $parentValue - $this->networkExpansion['valueDecrement'];

                if ($newValue >= $this->networkExpansion['minValue']) {
                    $childRelations = $this->queryFrameRelations($relation['idFrame']);
                    $childPool = $this->expandFrameRelations(
                        $childRelations,
                        $baseFrame,
                        $newValue,
                        $remainingLevels - 1
                    );
                    $pool = array_merge($pool, $childPool);
                }
            }
        }

        return $pool;
    }

    /**
     * Query Frame Element constraints
     */
    private function queryFEConstraints(int $idFrame): array
    {
        try {
            $constraints = Criteria::table('view_frameelement as fe')
                ->selectRaw('fr.idFrame, fe.coreType as typeEntry')
                ->join('view_relation as r', 'fe.idEntity', '=', 'r.idEntity2')
                ->join('frame as fr', 'r.idEntity3', '=', 'fr.idEntity')
                ->where('r.relationType', '=', 'rel_constraint_frame')
                ->where('fe.idFrame', '=', $idFrame)
                ->get()
                ->toArray();

            return array_map(fn ($c) => (array) $c, $constraints);
        } catch (\Exception $e) {
            logger()->error('Daisy FE constraints query error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Process FE constraints
     */
    private function processFEConstraints(array $constraints, string $baseFrame): array
    {
        $pool = [];

        foreach ($constraints as $constraint) {
            $feType = $constraint['typeEntry'];

            // Get weight for this FE type
            $weight = $this->feWeights[$feType] ?? 0.0;

            if ($weight <= 0) {
                continue; // Skip non-core FEs
            }

            // Query the constrained frame
            try {
                $frame = Criteria::table('frame')
                    ->where('idFrame', '=', $constraint['idFrame'])
                    ->first();

                if ($frame) {
                    $poolObj = new PoolObjectData(
                        frameName: $frame->Entry,
                        factor: $weight,
                        baseFrame: $baseFrame,
                        level: 3
                    );

                    $pool[$frame->Entry] = $poolObj;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $pool;
    }

    /**
     * Query Qualia relations
     */
    private function queryQualiaRelations(int $idLU, array $windows): array
    {
        $cacheKey = "daisy:qualia:{$idLU}:{$this->idLanguage}";
        $ttl = config('daisy.cacheTTL.qualiaRelations');

        return Cache::remember($cacheKey, $ttl, function () use ($idLU) {
            try {
                // Bidirectional qualia search
                $qualiaLUs = Criteria::table('view_relation as r')
                    ->selectRaw('lu2.idLU')
                    ->join('view_lu as lu1', 'r.idEntity1', '=', 'lu1.idEntity')
                    ->join('view_lu as lu2', 'r.idEntity2', '=', 'lu2.idEntity')
                    ->leftJoin('qualia as q', 'r.idEntity3', '=', 'q.idEntity')
                    ->leftJoin('view_relation as rq', 'q.idEntity', '=', 'rq.idEntity1')
                    ->where('lu1.idLU', '=', $idLU)
                    ->where('r.relationGroup', '=', 'rgp_qualia')
                    ->where('rq.relationType', '=', 'rel_qualia_frame')
                    ->where('lu1.idLanguage', '=', $this->idLanguage)
                    ->where('lu2.idLanguage', '=', $this->idLanguage)
                    ->union(
                        Criteria::table('view_relation as r')
                            ->selectRaw('lu1.idLU')
                            ->join('view_lu as lu1', 'r.idEntity1', '=', 'lu1.idEntity')
                            ->join('view_lu as lu2', 'r.idEntity2', '=', 'lu2.idEntity')
                            ->leftJoin('qualia as q', 'r.idEntity3', '=', 'q.idEntity')
                            ->leftJoin('view_relation as rq', 'q.idEntity', '=', 'rq.idEntity1')
                            ->where('lu2.idLU', '=', $idLU)
                            ->where('r.relationGroup', '=', 'rgp_qualia')
                            ->where('rq.relationType', '=', 'rel_qualia_frame')
                            ->where('lu1.idLanguage', '=', $this->idLanguage)
                            ->where('lu2.idLanguage', '=', $this->idLanguage)
                    )
                    ->get()
                    ->toArray();

                return array_map(fn ($lu) => (array) $lu, $qualiaLUs);
            } catch (\Exception $e) {
                logger()->error('Daisy qualia query error: '.$e->getMessage());

                return [];
            }
        });
    }

    /**
     * Process Qualia relations with depth search
     */
    private function processQualiaRelations(array $relations, string $baseFrame, int $idWindow): array
    {
        $pool = [];
        $maxDepth = $this->networkExpansion['maxQualiaDepth'];
        $searched = [];
        $toSearch = array_column($relations, 'idLU');

        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            if (empty($toSearch)) {
                break;
            }

            $nextSearch = [];

            foreach ($toSearch as $idLU) {
                if (isset($searched[$idLU])) {
                    continue;
                }

                $searched[$idLU] = true;
                $qualiaLUs = $this->queryQualiaRelations($idLU, []);

                foreach ($qualiaLUs as $relatedLU) {
                    $relatedIdLU = $relatedLU['idLU'];

                    if (! isset($searched[$relatedIdLU])) {
                        $nextSearch[] = $relatedIdLU;

                        // Get frame for this LU
                        try {
                            $lu = Criteria::table('view_lu')
                                ->where('idLU', '=', $relatedIdLU)
                                ->first();

                            if ($lu) {
                                $energyKey = $depth === 1 ? 'qualia_depth_1' : 'qualia_depth_2';
                                $qualiaEnergy = $this->energyBonus[$energyKey];

                                $poolObj = new PoolObjectData(
                                    frameName: $lu->Entry,
                                    factor: $qualiaEnergy,
                                    baseFrame: $baseFrame,
                                    level: 4
                                );

                                $pool[$lu->Entry] = $poolObj;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $toSearch = $nextSearch;
        }

        return $pool;
    }
}
