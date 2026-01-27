<?php

namespace App\Services\Daisy;

use App\Database\Criteria;
use App\Services\AppService;

class NetworkService
{
    private static $weigths;

    private static $frames;

    private static $visited;

    private static $fes;

    private static $lus;

    private static $processed;

    public static function init(): void
    {
        ini_set('memory_limit', '10240M');
        self::$weigths = config('daisy.relationWeights');
        self::$frames = [];
        // create all frame nodes
        Criteria::table('daisy_link')->delete();
        Criteria::table('daisy_node')->delete();
        $frames = Criteria::table('frame')
            ->select('idFrame')
            ->all();
        foreach ($frames as $frame) {
            if (!isset(self::$frames[$frame->idFrame])) {
                $idFrameNode = Criteria::create('daisy_node', [
                    'name' => 'frame_' . $frame->idFrame,
                    'type' => 'FR',
                    'idFrame' => $frame->idFrame,
                ]);
                self::$frames[$frame->idFrame] = $idFrameNode;
            }
        }
        //
        self::$fes = [];
        self::$lus = [];
    }

    private static function createFrameLinks(int $idFrameSource): void
    {
        if (!isset(self::$visited[$idFrameSource])) {
            $idNodeSource = Criteria::byId('daisy_node', 'idFrame', $idFrameSource)->idDaisyNode;
            self::$visited[$idFrameSource] = $idNodeSource;
            $relations = Criteria::table('view_frame_relation')
                ->where('f2IdFrame', $idFrameSource)
                ->where('idLanguage', AppService::getCurrentIdLanguage())
                ->all();
            foreach ($relations as $relation) {
                if (self::$weigths[$relation->relationType] > 0) {
                    if (isset(self::$frames[$relation->f1IdFrame])) {
                        $idNodeTarget = self::$frames[$relation->f1IdFrame];
                    } else {
                        $idNodeTarget = Criteria::byId('daisy_node', 'idFrame', $relation->f1IdFrame)->idDaisyNode;
                    }
                    Criteria::create('daisy_link', [
                        'idDaisyNodeSource' => $idNodeSource,
                        'idDaisyNodeTarget' => $idNodeTarget,
                        'type' => 'F-F',
                        'value' => self::$weigths[$relation->relationType],
                    ]);
                    Criteria::create('daisy_link', [
                        'idDaisyNodeSource' => $idNodeTarget,
                        'idDaisyNodeTarget' => $idNodeSource,
                        'type' => 'F-F',
                        'value' => self::$weigths[$relation->relationType],
                    ]);
                    self::createFrameLinks($relation->f1IdFrame);
                }
            }
        }
    }

    public static function createFrameNetwork(): void
    {
        // clear current frame links
        $frameNodes = Criteria::table('daisy_node')
            ->where('type', 'FR')
            ->get()->pluck('idDaisyNode')->toArray();
        //        debug($frameNodes);
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeSource', $frameNodes)
            ->delete();
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeTarget', $frameNodes)
            ->delete();
        // now recreate links
        self::$visited = [];
        foreach (self::$frames as $idFrame => $frameNode) {
            self::createFrameLinks($idFrame);
        }
    }

    public static function createFEFrameNetwork(): void
    {
        // clear current fe-frame network
        $frameNodes = Criteria::table('daisy_node')
            ->where('type', 'FE')
            ->get()->pluck('idDaisyNode')->toArray();
        //        debug($frameNodes);
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeSource', $frameNodes)
            ->delete();
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeTarget', $frameNodes)
            ->delete();
        Criteria::table('daisy_node')
            ->where('type', 'FE')
            ->delete();
        $constraintsFEF = Criteria::table('view_constrainedby_frame as c')
            ->join('frameelement as fe', 'c.idConstrained', '=', 'fe.idEntity')
            ->join('frame as f', 'c.idConstrainedBy', '=', 'f.idEntity')
            ->where('c.idLanguage', AppService::getCurrentIdLanguage())
            ->select('fe.idFrameElement', 'f.idFrame')
            ->all();
        // create all frame nodes
        foreach ($constraintsFEF as $constraint) {
            if (!isset(self::$fes[$constraint->idFrameElement])) {
                $idFENode = Criteria::create('daisy_node', [
                    'name' => 'fe_' . $constraint->idFrameElement,
                    'type' => 'FE',
                    'idFrameElement' => $constraint->idFrameElement,
                ]);
                self::$fes[$constraint->idFrameElement] = $idFENode;
            }
            $idFENode = self::$fes[$constraint->idFrameElement];
            $idFrameNode = self::$frames[$constraint->idFrame];
            Criteria::create('daisy_link', [
                'idDaisyNodeSource' => $idFENode,
                'idDaisyNodeTarget' => $idFrameNode,
                'type' => 'FEF',
                'value' => self::$weigths['rel_fe_f'],
            ]);
        }
    }

    public static function createLUFrameNetwork(): void
    {
        // clear current lu-frame network
        $luNodes = Criteria::table('daisy_node')
            ->where('type', 'LU')
            ->get()->pluck('idDaisyNode')->toArray();
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeSource', $luNodes)
            ->where('type', 'EVK')
            ->delete();
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeTarget', $luNodes)
            ->where('type', 'EVK')
            ->delete();
        $evokes = Criteria::table('view_lu')
            ->select('idLU', 'idFrame')
            ->all();
        // create all evokes nodes
        foreach ($evokes as $evoke) {
            if (!isset(self::$lus[$evoke->idLU])) {
                $idLUNode = Criteria::create('daisy_node', [
                    'name' => 'lu_' . $evoke->idLU,
                    'type' => 'LU',
                    'idLU' => $evoke->idLU,
                ]);
                self::$lus[$evoke->idLU] = $idLUNode;
            }
            $idLUNode = self::$lus[$evoke->idLU];
            $idFrameNode = self::$frames[$evoke->idFrame];
            Criteria::create('daisy_link', [
                'idDaisyNodeSource' => $idLUNode,
                'idDaisyNodeTarget' => $idFrameNode,
                'type' => 'EVK',
                'value' => self::$weigths['rel_evokes'],
            ]);
        }
    }

    public static function createQualiaNetwork(): void
    {
        // clear current qualia network
        $luNodes = Criteria::table('daisy_node')
            ->where('type', 'LU')
            ->get()->pluck('idDaisyNode')->toArray();
        //        debug($frameNodes);
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeSource', $luNodes)
            ->where('type', 'QLR')
            ->delete();
        Criteria::table('daisy_link')
            ->whereIN('idDaisyNodeTarget', $luNodes)
            ->where('type', 'QLR')
            ->delete();
        $qualiaLUs = Criteria::table('view_relation as r')
            ->select('lu1.idLU as idLU1', 'lu2.idLU as idLU2')
            ->join('view_lu as lu1', 'r.idEntity1', '=', 'lu1.idEntity')
            ->join('view_lu as lu2', 'r.idEntity2', '=', 'lu2.idEntity')
            ->leftJoin('qualia as q', 'r.idEntity3', '=', 'q.idEntity')
            ->leftJoin('view_relation as rq', 'q.idEntity', '=', 'rq.idEntity1')
            ->where('r.relationGroup', '=', 'rgp_qualia')
            ->where('rq.relationType', '=', 'rel_qualia_frame')
            ->all();
        foreach ($qualiaLUs as $qualiaLU) {
            if (!isset(self::$lus[$qualiaLU->idLU1])) {
                $idLUNode = Criteria::create('daisy_node', [
                    'name' => 'lu_' . $qualiaLU->idLU1,
                    'type' => 'LU',
                    'idLU' => $qualiaLU->idLU1,
                ]);
                self::$lus[$qualiaLU->idLU1] = $idLUNode;
            }
            if (!isset(self::$lus[$qualiaLU->idLU2])) {
                $idLUNode = Criteria::create('daisy_node', [
                    'name' => 'lu_' . $qualiaLU->idLU2,
                    'type' => 'LU',
                    'idLU' => $qualiaLU->idLU2,
                ]);
                self::$lus[$qualiaLU->idLU2] = $idLUNode;
            }
            $idLU1Node = self::$lus[$qualiaLU->idLU1];
            $idLU2Node = self::$lus[$qualiaLU->idLU2];
            Criteria::create('daisy_link', [
                'idDaisyNodeSource' => $idLU1Node,
                'idDaisyNodeTarget' => $idLU2Node,
                'type' => 'QLR',
                'value' => self::$weigths['rel_qualia'],
            ]);
            Criteria::create('daisy_link', [
                'idDaisyNodeSource' => $idLU2Node,
                'idDaisyNodeTarget' => $idLU1Node,
                'type' => 'QLR',
                'value' => self::$weigths['rel_qualia'],
            ]);
        }
    }

    public function vectorForFrame(int $idFrame): array
    {
        $vector = [
            $idFrame => 1.0,
        ];
        self::$visited = [];
        $this->spreadFromFrame($idFrame, $vector, 1);

        return $vector;
    }

    public function spreadFromFrame(int $idFrame, array &$vector, int $level, float $baseWeight = 1.0): void
    {
        if ($level > 9) {
            return;
        }
        if (!isset(self::$visited[$idFrame])) {
            self::$visited[$idFrame] = $idFrame;
            $frameNode = Criteria::table('daisy_node')
                ->where('type', 'FR')
                ->where('idFrame', $idFrame)
                ->first();
            if ($frameNode) {
                $links = Criteria::table('daisy_link as l')
                    ->join('daisy_node as n', 'l.idDaisyNodeTarget', '=', 'n.idDaisyNode')
                    ->select('n.idFrame', 'l.value as weight')
                    ->where('l.idDaisyNodeSource', $frameNode->idDaisyNode)
                    ->all();
                foreach ($links as $link) {
                    $weight = $link->weight * $baseWeight;
                    $vector[$link->idFrame] = $weight;
                    $this->spreadFromFrame($link->idFrame, $vector, $level + 1, $weight);
                }
            }
        }
    }

    public function vectorForLU(int $idLU): array
    {
        self::$visited = [];
        $startNode = Criteria::table("daisy_node")
            ->where('idLU', $idLU)
            ->first();
        $vector = [];
//        print_r("idLU : {$idLU}\n");
        $this->spreadFromLU($startNode->idDaisyNode, $vector, 1, 1.0);
        return $vector;
    }

    public function spreadFromLU(int $idDaisyNode, array &$vector, int $level, float $baseWeight = 1.0): void
    {
        if ($level > 3) {
            return;
        }
//        print_r(str_repeat(' ', $level *3 ) . $idDaisyNode ."\n");
        $vector[$idDaisyNode] = $baseWeight;
        self::$visited[$idDaisyNode] = $idDaisyNode;
        $links = Criteria::table('daisy_link as l')
            ->join('daisy_node as n', 'l.idDaisyNodeTarget', '=', 'n.idDaisyNode')
            ->select('n.idDaisyNode', 'l.value as weight')
            ->where('l.idDaisyNodeSource', $idDaisyNode)
            ->all();
        foreach ($links as $link) {
            if (!isset(self::$visited[$link->idDaisyNode])) {
                $weight = $link->weight * $baseWeight;
                $this->spreadFromLU($link->idDaisyNode, $vector, $level + 1, $weight);
            }
        }
    }

}
