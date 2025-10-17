<?php

namespace App\Services\Dashboard;


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Orkester\Persistence\PersistenceManager;

class McGovernService extends AppService
{
    public static function dashboard(): array
    {
        $database ??= config('database.default');
        $cmd = <<<HERE
        select st.entry domain, count(distinct f.idFrame) f
from entityrelation e
join frame f on (e.idEntity1 = f.idEntity)
join semantictype st on (e.idEntity2 = st.idEntity)
where st.entry in ('sty_fd_health','sty_fd_violence')
group by st.entry

HERE;
        $query = DB::connection($database)->select($cmd);
        $frames = collect($query)->keyBy('domain')->all();
        ddump($frames);
        $cmd = <<<HERE
        select e.idEntity2 idEntity, count(distinct lu.idLU) l
from entityrelation e
join frame f on (e.idEntity1 = f.idEntity)
join lu on (lu.idFrame = f.idFrame)
where identity2 in (1550220,1554179)
group by e.idEntity2

HERE;
        $query = DB::connection($database)->select($cmd);
        $lus = collect($query)->keyBy('idEntity')->all();
//ddump($lus);
        $cmd = <<<HERE
select count(distinct d.idDocument) d,count(distinct ds.idSentence) s,count(distinct f.idFrame) f,count(distinct lu.idLU) l,count(distinct a.idSentence) a, count(distinct a.idAnnotationSet) an
from corpus c
join document d on (d.idCorpus = c.idCorpus)
join document_sentence ds on (ds.idDocument = d.idDocument)
left join annotationset a on (ds.idSentence = a.idSentence)
left join lu on (a.idEntityRelated = lu.idEntity)
left join frame f on (lu.idFrame = f.idFrame)
where c.idcorpus in (153,155);

HERE;
        $query = DB::connection('internal')->select($cmd);
        //$annoSIH = collect($query)->keyBy('entry')->all();
        $annoSIH = $query;
//        ddump($annoSIH);
        $cmd = <<<HERE
select c.entry, count(distinct d.idDocument) d,count(distinct ds.idSentence) s,count(distinct f.idFrame) f,count(distinct lu.idLU) l,count(distinct a.idSentence) a, count(distinct a.idAnnotationSet) an
from corpus c
join document d on (d.idCorpus = c.idCorpus)
join document_sentence ds on (ds.idDocument = d.idDocument)
left join annotationset a on (ds.idSentence = a.idSentence)
left join lu on (a.idEntityRelated = lu.idEntity)
left join frame f on (lu.idFrame = f.idFrame)
where c.idcorpus in (154)
group by c.entry;

HERE;
        $query = DB::connection('internal')->select($cmd);
        $annoSINAN = collect($query)->keyBy('entry')->all();
//        ddump($annoSINAN);

        $cmd = <<<HERE
select count(*) total
from entityrelation r
join lu on (r.idEntity1 = lu.idEntity)
join frame f on (lu.idFrame = f.idFrame)
join entityrelation rst on (f.idEntity = rst.idEntity1)
join semantictype st on (rst.idEntity2 = st.idEntity)
where st.entry in ('sty_fd_health','sty_fd_violence')
and (r.idRelationType in (33,34,35,36,215)
and (r.idEntity3 is not null))

HERE;
        $query = DB::connection('fnbr')->select($cmd);
        //$annoSIH = collect($query)->keyBy('entry')->all();
        $qualia = $query;

        $cmd = <<<HERE
select count(*) total
from entityrelation r
join lu on (r.idEntity1 = lu.idEntity)
join frame f on (lu.idFrame = f.idFrame)
join entityrelation rst on (f.idEntity = rst.idEntity1)
join semantictype st on (rst.idEntity2 = st.idEntity)
join qualia q on (r.idEntity3 = q.idEntity)
where st.entry in ('sty_fd_health','sty_fd_violence')
and (r.idRelationType in (33,34,35,36,215))
and (r.idEntity3 is not null)
group by q.info
having count(*) > 10;

HERE;
        $query = DB::connection('fnbr')->select($cmd);
        //$annoSIH = collect($query)->keyBy('entry')->all();
        $qualiaData = collect($query)->pluck('total')->all();
        ddump($qualiaData);

        return [
            'sihDoc' => $annoSIH[0]->d,
            'sihSen' => $annoSIH[0]->s,
            'sihFrm' => $annoSIH[0]->f,
            'sihLu' => $annoSIH[0]->l,
            'sihAnno' => $annoSIH[0]->a,
            'sihAS' => $annoSIH[0]->an,
            //
            'sinanDoc' => $annoSINAN['crp_sinan']->d,
            'sinanSen' => $annoSINAN['crp_sinan']->s,
            'sinanFrm' => $annoSINAN['crp_sinan']->f,
            'sinanLu' => $annoSINAN['crp_sinan']->l,
            'sinanAnno' => $annoSINAN['crp_sinan']->a,
            'sinanAS' => $annoSINAN['crp_sinan']->an,
//            'pecDoc' => $anno['crp_pec']->d,
//            'pecSen' => $anno['crp_pec']->s,
//            'pecFrm' => $anno['crp_pec']->f,
//            'pecLu' => $anno['crp_pec']->l,
//            'pecAnno' => $anno['crp_pec']->a,
//            'pecAS' => $anno['crp_pec']->an,
            'hFrames' => $frames['sty_fd_health']->f,
            'hLus' => $lus['1550220']->l,
            'vFrames' => $frames['sty_fd_violence']->f,
            'vLus' => $lus['1554179']->l,
            'qualiaTotal' => $qualia[0]->total,
            'qualiaData' => $qualiaData

        ];
    }

    public static function subcorporaFrame2(): int
    {
        $count = CorpusModel::getCriteria()
            ->where('entry', '=', 'crp_pedro_pelo_mundo')
            ->get("count(documents.sentences.idSentence) as n");
        return $count[0]['n'];
    }

    public static function subcorporaAudition(): int
    {
        $count = CorpusModel::getCriteria()
            ->where('entry', '=', 'crp_curso_dataset')
            ->get("count(documents.sentences.idSentence) as n");
        return $count[0]['n'];
    }

    public static function subcorporaMulti30k(): int
    {
        $countCom = CorpusModel::getCriteria()
            ->where('entry', 'IN', [
                'crp_oficina_com_sentenca_1',
                'crp_oficina_com_sentenca_2',
                'crp_oficina_com_sentenca_3',
                'crp_oficina_com_sentenca_4',
            ])
            ->get("count(documents.sentences.idSentence) as n");
        $countSem = CorpusModel::getCriteria()
            ->where('entry', 'IN', [
                'crp_oficina_sem_sentenca_1',
                'crp_oficina_sem_sentenca_2',
                'crp_oficina_sem_sentenca_3',
                'crp_oficina_sem_sentenca_4',
            ])
            ->get("count(documents.sentences.idSentence) as n");
        return $countCom[0]['n'] + $countSem[0]['n'];
    }

    private static function getSentences($query)
    {
        $query->setModel(SentenceModel::class)
            ->distinct()
            ->select("idSentence")
            ->where('documents.corpus.entry', 'IN', [
                'crp_pedro_pelo_mundo',
                'crp_curso_dataset',
                'crp_oficina_com_sentenca_1',
                'crp_oficina_com_sentenca_2',
                'crp_oficina_com_sentenca_3',
                'crp_oficina_com_sentenca_4',
                'crp_oficina_sem_sentenca_1',
                'crp_oficina_sem_sentenca_2',
                'crp_oficina_sem_sentenca_3',
                'crp_oficina_sem_sentenca_4',
            ]);
    }

    public static function annoFulltext(): int
    {
        $sentences = function ($query) {
            self::getSentences($query);
        };
        $count = AnnotationSetModel::getCriteria()
            ->where('sentence.idSentence', 'IN', $sentences)
            ->get("count(idAnnotationSet) as n");
        return $count[0]['n'];
    }

    public static function annoStatic(): int
    {
        $sentences = function ($query) {
            self::getSentences($query);
        };
        $count = ObjectSentenceMMModel::getCriteria()
            ->where('sentenceMM.idSentence', 'IN', $sentences)
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->get("count(idObjectSentenceMM) as n");
        return $count[0]['n'];
    }

    public static function annoDynamic(): int
    {
        $count = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo', 'crp_curso_dataset'])
            ->get("count(idObjectMM) as n");
        return $count[0]['n'];
    }

    public static function categoryFrame(): int
    {
        $sentences = function ($query) {
            self::getSentences($query);
        };
        $frames = [];
        $frameDynamic = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo', 'crp_curso_dataset'])
            ->get("frameElement.idFrame");
        foreach ($frameDynamic as $row) {
            $frames[$row['idFrame']] = 1;
        }
        $frameStatic = ObjectSentenceMMModel::getCriteria()
            ->where('sentenceMM.idSentence', 'IN', $sentences)
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->get("frameElement.idFrame");
        foreach ($frameStatic as $row) {
            $frames[$row['idFrame']] = 1;
        }
        $frameText = LabelModel::getCriteria()
            ->where('layer.annotationSet.sentence.idSentence', 'IN', $sentences)
            ->get("frameElement.idFrame");
        foreach ($frameText as $row) {
            $frames[$row['idFrame']] = 1;
        }
        return count($frames);
    }

    public static function categoryFE(): int
    {
        $sentences = function ($query) {
            self::getSentences($query);
        };
        $fes = [];
        $feDynamic = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo', 'crp_curso_dataset'])
            ->get("idFrameElement");
        foreach ($feDynamic as $row) {
            $fes[$row['idFrameElement']] = 1;
        }
        $feStatic = ObjectSentenceMMModel::getCriteria()
            ->where('sentenceMM.idSentence', 'IN', $sentences)
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->get("idFrameElement");
        foreach ($feStatic as $row) {
            $fes[$row['idFrameElement']] = 1;
        }
        $feText = LabelModel::getCriteria()
            ->where('layer.annotationSet.sentence.idSentence', 'IN', $sentences)
            ->get("frameElement.idFrameElement");
        foreach ($feText as $row) {
            $fes[$row['idFrameElement']] = 1;
        }
        return count($fes);
    }

    public static function categoryCV(): int
    {
        $sentences = function ($query) {
            self::getSentences($query);
        };
        $lus = [];
        $luDynamic = ObjectMMModel::getCriteria()
            ->where('idLU', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo', 'crp_curso_dataset'])
            ->get("idLU");
        foreach ($luDynamic as $row) {
            $lus[$row['idLU']] = 1;
        }
        $luStatic = ObjectSentenceMMModel::getCriteria()
            ->where('sentenceMM.idSentence', 'IN', $sentences)
            ->where('idLU', 'IS', 'NOT NULL')
            ->get("idLU");
        foreach ($luStatic as $row) {
            $lus[$row['idLU']] = 1;
        }
        $luText = AnnotationSetModel::getCriteria()
            ->where('sentence.idSentence', 'IN', $sentences)
            ->get("lu.idLU");
        foreach ($luText as $row) {
            $lus[$row['idLU']] = 1;
        }
        return count($lus);
    }

    //
    // Frame2
    //

    private static function getSentencesFrame2($query)
    {
        $query->setModel(SentenceModel::class)
            ->distinct()
            ->select("idSentence")
            ->where('documents.corpus.entry', 'IN', [
                'crp_pedro_pelo_mundo'
            ]);

    }

    public static function frame2(): array
    {
        $result = [];
        $sentences = function ($query) {
            self::getSentencesFrame2($query);
        };
        $count = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct idSentence) as n");
        $result['sentences'] = $count[0]['n'];
        $count = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo'])
            ->get("count(distinct idObjectMM) as n");
        $result['bbox'] = $count[0]['n'];
        $count1 = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct lu.idFrame) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo'])
            ->get("count(distinct frameElement.idFrame) as n");
        $result['framesText'] = $count1[0]['n'];
        $result['framesBBox'] = $count2[0]['n'];
        $count1 = LabelModel::getCriteria()
            ->where('layer.annotationSet.sentence.idSentence', 'IN', $sentences)
            ->get("count(distinct frameElement.idFrameElement) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo'])
            ->get("count(distinct idFrameElement) as n");
        $result['fesText'] = $count1[0]['n'];
        $result['fesBBox'] = $count2[0]['n'];
        $count1 = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct lu.idLU) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idLU', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo'])
            ->get("count(distinct idLU) as n");
        $result['lusText'] = $count1[0]['n'];
        $result['lusBBox'] = $count2[0]['n'];
        $counts = AnnotationSetModel::getCriteria()
            ->where('sentence.idSentence', 'IN', $sentences)
            ->get(["count(idAnnotationSet) as a", "count(distinct idSentence) as s"]);
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS']= number_format($counts[0]['a'] / $counts[0]['s'], 3, $decimal, '');
        $count = ObjectFrameMMModel::getCriteria()
            ->where('objectMM.documentMM.document.corpus.entry', 'IN', ['crp_pedro_pelo_mundo'])
            ->groupBy("idObjectMM")
            ->get("count(*) as n");
        $sum = 0;
        foreach ($count as $row) {
            $sum += $row['n'];
        }
        $avg = ($sum / count($count)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        return $result;
    }

    //
    // Audition
    //

    private static function getSentencesAudition($query)
    {
        $query->setModel(SentenceModel::class)
            ->distinct()
            ->select("idSentence")
            ->where('documents.corpus.entry', 'IN', [
                'crp_curso_dataset',
                'crp_hoje_eu_nao_quero',
                'crp_ad alternativa curta_hoje_eu_não_quero'
            ]);

    }

    public static function audition(): array
    {
        $result = [];
        $sentences = function ($query) {
            self::getSentencesAudition($query);
        };
        $count = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct idSentence) as n");
        $result['sentences'] = $count[0]['n'];
        $count = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_curso_dataset','crp_hoje_eu_nao_quero','crp_ad alternativa curta_hoje_eu_não_quero'])
            ->get("count(distinct idObjectMM) as n");
        $result['bbox'] = $count[0]['n'];
        $count1 = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct lu.idFrame) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_curso_dataset','crp_hoje_eu_nao_quero','crp_ad alternativa curta_hoje_eu_não_quero'])
            ->get("count(distinct frameElement.idFrame) as n");
        $result['framesText'] = $count1[0]['n'];
        $result['framesBBox'] = $count2[0]['n'];
        $count1 = LabelModel::getCriteria()
            ->where('layer.annotationSet.sentence.idSentence', 'IN', $sentences)
            ->get("count(distinct frameElement.idFrameElement) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_curso_dataset','crp_hoje_eu_nao_quero','crp_ad alternativa curta_hoje_eu_não_quero'])
            ->get("count(distinct idFrameElement) as n");
        $result['fesText'] = $count1[0]['n'];
        $result['fesBBox'] = $count2[0]['n'];
        $count1 = AnnotationSetModel::getCriteria()
            ->where('idSentence', 'IN', $sentences)
            ->get("count(distinct lu.idLU) as n");
        $count2 = ObjectMMModel::getCriteria()
            ->where('idLU', 'IS', 'NOT NULL')
            ->where('documentMM.document.corpus.entry', 'IN', ['crp_curso_dataset','crp_hoje_eu_nao_quero','crp_ad alternativa curta_hoje_eu_não_quero'])
            ->get("count(distinct idLU) as n");
        $result['lusText'] = $count1[0]['n'];
        $result['lusBBox'] = $count2[0]['n'];
        $counts = AnnotationSetModel::getCriteria()
            ->where('sentence.idSentence', 'IN', $sentences)
            ->get(["count(idAnnotationSet) as a", "count(distinct idSentence) as s"]);
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS']= number_format($counts[0]['a'] / $counts[0]['s'], 3, $decimal, '');
        $count = ObjectFrameMMModel::getCriteria()
            ->where('objectMM.documentMM.document.corpus.entry', 'IN', ['crp_curso_dataset','crp_hoje_eu_nao_quero','crp_ad alternativa curta_hoje_eu_não_quero'])
            ->groupBy("idObjectMM")
            ->get("count(*) as n");
        $sum = 0;
        foreach ($count as $row) {
            $sum += $row['n'];
        }
        $avg = ($sum / count($count)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        ddump($result);
        return $result;
    }

    //
    // Multi30k
    //

    public static function multi30k(): array
    {
        $result = [];
        $count = ObjectSentenceMMModel::getCriteria()
            ->where('sentenceMM.sentence.documents.corpus.entry', 'IN', [
                'crp_oficina_com_sentenca_1',
                'crp_oficina_com_sentenca_2',
                'crp_oficina_com_sentenca_3',
                'crp_oficina_com_sentenca_4',
                'crp_oficina_sem_sentenca_1',
                'crp_oficina_sem_sentenca_2',
                'crp_oficina_sem_sentenca_3',
                'crp_oficina_sem_sentenca_4',
            ])
            ->where('idFrameElement', 'IS', 'NOT NULL')
            ->get([
                "count(distinct idSentenceMM) as n1",
                "count(distinct idObjectSentenceMM) as n2",
                "count(distinct frameElement.idFrame) as n3",
                "count(distinct idFrameElement) as n4"
            ]);
        $result['images'] = $count[0]['n1'];
        $result['bbox'] = $count[0]['n2'];
        $result['framesImage'] = $count[0]['n3'];
        $result['fesImage'] = $count[0]['n4'];
        $result['lusImage'] = 0;

        ////
        $dbDaisy = PersistenceManager::$capsule->connection('daisy');
        // PTT
        $cmd = "select count(*) as n from flickr30ksentence where idDocumentFNBr = 1054 ";
        $count = $dbDaisy->select($cmd, []);
        $result['pttSentences'] = $count[0]->n;
        $cmd = "select count(distinct l.frame) as n
from lomeresult l
join flickr30ksentence f on (l.idFlickr30KSentence = f.idFlickr30KSentence)
where f.idDocumentFNBr = 1054";
        $count = $dbDaisy->select($cmd, []);
        $result['pttFrames'] = $count[0]->n;
        // PTO
        $cmd = "select count(*) as n from flickr30ksentence where idDocumentFNBr = 1055 ";
        $count = $dbDaisy->select($cmd, []);
        $result['ptoSentences'] = $count[0]->n;
        $cmd = "select count(distinct l.frame) as n
from lomeresult l
join flickr30ksentence f on (l.idFlickr30KSentence = f.idFlickr30KSentence)
where f.idDocumentFNBr = 1055";
        $count = $dbDaisy->select($cmd, []);
        $result['ptoFrames'] = $count[0]->n;
        // ENO
        $cmd = "select count(*) as n from flickr30ksentence where idDocumentFNBr = 663 ";
        $count = $dbDaisy->select($cmd, []);
        $result['enoSentences'] = $count[0]->n;
        $cmd = "select count(distinct l.frame) as n
from lomeresult l
join flickr30ksentence f on (l.idFlickr30KSentence = f.idFlickr30KSentence)
where f.idDocumentFNBr = 663";
        $count = $dbDaisy->select($cmd, []);
        $result['enoFrames'] = $count[0]->n;
        // Chart
        $dbFnbr = PersistenceManager::$capsule->connection('fnbr');
        $cmd = "SELECT month(tlDateTime) m,year(tlDateTime) y, count(*) n
         FROM fnbr_db.timeline t
where tablename='objectsentencemm'
group by month(tlDateTime),year(tlDateTime)";
        $rows = $dbFnbr->select($cmd, []);
        $chart = [];
        $sum = 0;
        foreach($rows as $row) {
            $sum += $row['n'];
            $chart[] = [
                'm' => $row['m'] . '/' . $row['y'],
                'value' => $sum
            ];
        }
        $chart[count($chart) - 1]['value'] = $result['bbox'];
        $result['chart'] = $chart;
        return $result;
    }

}
