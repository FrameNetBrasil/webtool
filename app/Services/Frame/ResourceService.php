<?php

namespace App\Services\Frame;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Services\AppService;
use App\Services\RelationService;
use Illuminate\Support\Facades\DB;

class ResourceService
{

    public static function clone(string $id)
    {
        try {
            $user = AppService::getCurrentUser();
            $idUser = $user ? $user->idUser : 0;

            // Wrap all operations in a transaction to ensure atomicity
            $newFrameId = DB::transaction(function () use ($id, $idUser) {
                // 1. Get original frame
                $originalFrame = Frame::byId($id);
                if (! $originalFrame) {
                    throw new \Exception('Frame not found');
                }

                // 2. Create cloned frame with _cloned suffix
                $clonedName = $originalFrame->name.'_cloned';
                $frameData = json_encode([
                    'nameEn' => $clonedName,
                    'idNamespace' => $originalFrame->idNamespace ?? 1,
                    'idUser' => $idUser,
                ]);
                $newFrameId = Criteria::function('frame_create(?)', [$frameData]);

                // Get the new frame to access its entity ID
                $newFrame = Frame::byId($newFrameId);

                // 3. Clone all frame elements with ID mapping
                $feEntityMapping = []; // old FE entity ID => new FE entity ID
                $feIdMapping = []; // old FE idFrameElement => old FE entity ID
                $frameElements = Criteria::table('view_frameelement')
                    ->where('idFrame', $id)
                    ->where('idLanguage', AppService::getCurrentIdLanguage())
                    ->all();

                foreach ($frameElements as $fe) {
                    // Store old FE mapping for later use
                    $feIdMapping[$fe->idFrameElement] = $fe->idEntity;

                    // Call fe_create for each frame element
                    $newFeId = Criteria::function('fe_create(?, ?, ?, ?, ?)', [
                        $newFrameId,
                        $fe->name,
                        $fe->coreType,
                        $fe->idColor,
                        $idUser,
                    ]);

                    // Get the new FE's entity ID
                    $newFe = Criteria::table('view_frameelement')
                        ->where('idFrameElement', $newFeId)
                        ->where('idLanguage', AppService::getCurrentIdLanguage())
                        ->first();

                    // Map old entity ID to new entity ID
                    $feEntityMapping[$fe->idEntity] = $newFe->idEntity;
                }

                // 4. Clone FE internal relations (coreset, excludes, requires)
                $feInternalRelations = RelationService::listRelationsFEInternal($id);
                foreach ($feInternalRelations as $relation) {
                    // Use the mapping to get entity IDs
                    $oldEntity1 = $feIdMapping[$relation->feIdFrameElement] ?? null;
                    $oldEntity2 = $feIdMapping[$relation->relatedFEIdFrameElement] ?? null;

                    $newEntity1 = $oldEntity1 ? ($feEntityMapping[$oldEntity1] ?? null) : null;
                    $newEntity2 = $oldEntity2 ? ($feEntityMapping[$oldEntity2] ?? null) : null;

                    if ($newEntity1 && $newEntity2) {
                        RelationService::create(
                            $relation->relationType,
                            $newEntity1,
                            $newEntity2
                        );
                    }
                }

                // 5. Clone frame-to-frame relations
                $frameRelationMapping = []; // old idEntityRelation => new idEntityRelation
                $frameRelations = Criteria::table('view_frame_relation')
                    ->where('f1IdFrame', $id)
                    ->where('idLanguage', AppService::getCurrentIdLanguage())
                    ->all();

                foreach ($frameRelations as $relation) {
                    // Clone direct relations (original frame was entity1)
                    $newRelationId = RelationService::create(
                        $relation->relationType,
                        $newFrame->idEntity,
                        $relation->f2IdEntity
                    );
                    $frameRelationMapping[$relation->idEntityRelation] = $newRelationId;
                }

                // Clone inverse relations (original frame was entity2)
                $inverseRelations = Criteria::table('view_frame_relation')
                    ->where('f2IdFrame', $id)
                    ->where('idLanguage', AppService::getCurrentIdLanguage())
                    ->all();

                foreach ($inverseRelations as $relation) {
                    $newRelationId = RelationService::create(
                        $relation->relationType,
                        $relation->f1IdEntity,
                        $newFrame->idEntity
                    );
                    $frameRelationMapping[$relation->idEntityRelation] = $newRelationId;
                }

                // 5b. Clone FE-FE relations between frames
                foreach ($frameRelationMapping as $oldRelationId => $newRelationId) {
                    $feRelations = Criteria::table('view_fe_relation')
                        ->where('idRelation', $oldRelationId)
                        ->where('idLanguage', AppService::getCurrentIdLanguage())
                        ->all();

                    foreach ($feRelations as $feRelation) {
                        // Determine which FE belongs to the cloned frame and map it
                        $newFe1Entity = $feRelation->fe1IdFrame == $id
                            ? ($feEntityMapping[$feRelation->fe1IdEntity] ?? null)
                            : $feRelation->fe1IdEntity;

                        $newFe2Entity = $feRelation->fe2IdFrame == $id
                            ? ($feEntityMapping[$feRelation->fe2IdEntity] ?? null)
                            : $feRelation->fe2IdEntity;

                        if ($newFe1Entity && $newFe2Entity) {
                            RelationService::create(
                                $feRelation->relationType,
                                $newFe1Entity,
                                $newFe2Entity,
                                null,
                                $newRelationId
                            );
                        }
                    }
                }

                // 6. Clone frame classifications
                $classifications = Criteria::table('frame')
                    ->select('relation.relationType', 'relation.idEntity1 as idEntityFrame', 'relation.idEntity2 as idEntitySemanticType', 'e.idLanguage')
                    ->join('view_relation as relation', 'frame.idEntity', '=', 'relation.idEntity1')
                    ->join('semantictype as st', 'relation.idEntity2', '=', 'st.idEntity')
                    ->join('entry as e', 'e.idEntity', '=', 'st.idEntity')
                    ->where('relation.relationGroup', '=', 'rgp_frame_classification')
                    ->where('frame.idFrame', '=', $id)
                    ->where('e.idLanguage', '=', AppService::getCurrentIdLanguage())
                    ->get();

                foreach ($classifications as $classification) {
                    RelationService::create(
                        $classification->relationType,
                        $newFrame->idEntity,
                        $classification->idEntitySemanticType
                    );
                }

                // 7. Update multilingual entries for frame
                // frame_create() already created entries, so we just update them
                $frameEntries = Criteria::table('entry')
                    ->where('idEntity', $originalFrame->idEntity)
                    ->all();

                foreach ($frameEntries as $entry) {
                    Criteria::table('entry')
                        ->where('idEntity', $newFrame->idEntity)
                        ->where('idLanguage', $entry->idLanguage)
                        ->update([
                            'name' => $entry->name,
                            'description' => $entry->description,
                            'nick' => $entry->nick,
                        ]);
                }

                // Update multilingual entries for frame elements
                // fe_create() already created entries, so we just update them
                foreach ($frameElements as $oldFe) {
                    $newFeEntity = $feEntityMapping[$oldFe->idEntity] ?? null;
                    if ($newFeEntity) {
                        $feEntries = Criteria::table('entry')
                            ->where('idEntity', $oldFe->idEntity)
                            ->all();

                        foreach ($feEntries as $entry) {
                            Criteria::table('entry')
                                ->where('idEntity', $newFeEntity)
                                ->where('idLanguage', $entry->idLanguage)
                                ->update([
                                    'name' => $entry->name,
                                    'description' => $entry->description,
                                    'nick' => $entry->nick,
                                ]);
                        }
                    }
                }

                // Return the new frame ID from the transaction
                return $newFrameId;
            });
            return $newFrameId;

        } catch (\Exception $e) {
            throw new \Exception('Error cloning frame: '.$e->getMessage());
        }
    }


}
