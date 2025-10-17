<?php

namespace App\Http\Controllers\Lexicon;

use App\Data\ComboBox\QData;
use App\Data\Lexicon\CreateExpressionData;
use App\Data\Lexicon\CreateFeatureData;
use App\Data\Lexicon\CreateLexiconData;
use App\Data\Lexicon\SearchData;
use App\Data\Lexicon\UpdateLexiconData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Lexicon;
use App\Services\AppService;
use App\Services\Lexicon\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class Resource3Controller extends Controller
{
    /*
    #[Get(path: '/lexicon3')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseLemmaBySearch($search);

        return view('Lexicon3.browse', [
            'title' => 'Lemmas',
            'data' => $data,
        ]);
    }

    #[Post(path: '/lexicon3/search')]
    public function search(SearchData $search)
    {
        $title = "";
        if ($search->idLemma != 0) {
            $data = BrowseService::browseFormByLemmaSearch($search);
        } else if ($search->form != '') {
            $data = BrowseService::browseFormBySearch($search);
            $title = 'Forms';
        } else {
            $data = BrowseService::browseLemmaBySearch($search);
            $title = 'Lemmas';
        }
        return view('Lexicon3.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }
    * /

//    #[Post(path: '/lexicon3/tree')]
//    public function tree(TreeData $search)
//    {
//        $data = [];
//        if ($search->idLemma != 0) {
//            $data = BrowseService::browseFormByLemmaSearch($search);
//        }
//
//        return view('Lexicon3.browse', [
//            'idNode' => $search->idLemma,
//            'data' => $data,
//        ])->fragment('tree');
//
//    }

    /*------
      Lemma
      ------ */
/*
    #[Get(path: '/lexicon3/lemma/listForSearch')]
    public function listForSearch(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::byFilterLanguage('view_lexicon_lemma', ['name', 'startswith', trim($name)])
            ->select('idLexicon', 'fullNameUD as name')
            ->limit(50)
            ->orderby('name')->all()];
    }

    #[Get(path: '/lexicon3/lemma/new')]
    public function formNewLemma()
    {
        return view('Lexicon3.formNewLemma');
    }

    #[Post(path: '/lexicon3/lemma/new')]
    public function newLemma(CreateLexiconData $data)
    {
        try {
            $exists = Criteria::table('view_lexicon_lemma')
                ->whereRaw("name = '{$data->form}' collate 'utf8mb4_bin'")
                ->where('idUDPOS', $data->idUDPOS)
                ->where('idLanguage', $data->idLanguage)
                ->first();
            if (!is_null($exists)) {
                throw new \Exception('Lemma already exists.');
            }
            $newLemma = json_encode([
                'form' => $data->form,
                'idLexiconGroup' => $data->idLexiconGroup,
                'idLanguage' => $data->idLanguage,
                'idPOS' => $data->idPOS,
                'idUDPOS' => $data->idUDPOS,
            ]);
            $idLemma = Criteria::function('lexicon_create(?)', [$newLemma]);
            $lemma = Lexicon::lemmaById($idLemma);
            $view = view('Lexicon3.lemma', [
                'lemma' => $lemma,
                'expressions' => [],
            ]);

            return $view;//->fragment('content');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lexicon3/lemma/{idLemma}/expressions')]
    public function expressions(int $idLemma)
    {
        $lemma = Lexicon::LemmabyId($idLemma);
        $expressions = Criteria::table('view_lexicon_expression as e')
            ->where('e.idLemma', $idLemma)
            ->orderBy('e.position')
            ->all();

        return view('Lexicon3.expressions', [
            'lemma' => $lemma,
            'expressions' => $expressions,
        ]);
    }

    #[Get(path: '/lexicon3/lemma/{idLemma}')]
    public function lemma(int $idLemma)
    {
        $lemma = Lexicon::LemmabyId($idLemma);
        $expressions = Criteria::table('view_lexicon_expression as e')
            ->where('e.idLemma', $idLemma)
            ->orderBy('e.position')
            ->all();

        return view('Lexicon3.lemma', [
            'lemma' => $lemma,
            'expressions' => $expressions,
        ]);
    }

    #[Put(path: '/lexicon3/lemma')]
    public function updateLemma(UpdateLexiconData $data)
    {
        debug($data);
        try {
            Criteria::table('lexicon')
                ->where('idLexicon', $data->idLexicon)
                ->where('idLexiconGroup', $data->idLexiconGroup)
                ->update([
                    'form' => $data->form,
                    'idUDPOS' => $data->idUDPOS,
                    'idPOS' => $data->idPOS,
                ]);

            return $this->renderNotify('success', 'Lemma updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/lexicon3/lemma/{idLexicon}')]
    public function deleteLemma(string $idLexicon)
    {
        try {
            Criteria::function('lexicon_delete(?,?)', [$idLexicon, AppService::getCurrentIdUser()]);
            $this->trigger('reload-gridLexicon3');

            return $this->renderNotify('success', 'Lemma removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', 'Deletion failed. Check if there is some LU  using this lemma.');
        }
    }
*/
    /*------
      Expression
      ------ */
/*
    #[Get(path: '/lexicon3/expression/listForSelect')]
    public function listExpressionForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::byFilterLanguage('lexicon', ['form', 'startswith', trim($name)])
            ->select('idLexicon', 'form as name')
            ->limit(50)
            ->orderby('name')->all()];
    }

    #[Post(path: '/lexicon3/expression/new')]
    public function newExpression(CreateExpressionData $data)
    {
        try {
            if ($data->idLexicon) {
                Criteria::create('lexicon_expression', [
                    'idLexicon' => $data->idLemma,
                    'idExpression' => $data->idLexicon,
                    'position' => $data->position,
                    'head' => $data->head,
                    'breakBefore' => $data->breakBefore,
                ]);
                $this->trigger('reload-gridExpressions');

                return $this->renderNotify('success', 'Expression added.');
            } else {
                throw new \Exception('Expression not found.');
            }
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/lexicon3/expression/{idLexiconExpression}')]
    public function deleteLexiconExpression(string $idLexiconExpression)
    {
        try {
            Criteria::deleteById('lexicon_expression', 'idLexiconExpression', $idLexiconExpression);
            $this->trigger('reload-gridExpressions');

            return $this->renderNotify('success', 'Expression removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
*/
    /*--------
      Form
      -------- */
    /*
    #[Get(path: '/lexicon3/morpheme/listForSelect')]
    public function listMorphemeForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';
        $idLanguage = ($data->idLanguage > 0) ? $data->idLanguage : AppService::getCurrentIdLanguage();

        return Criteria::table('lexicon as l')
            ->join('lexicon_group as g', 'g.idLexiconGroup', '=', 'l.idLexiconGroup')
            ->where('l.form', 'startswith', trim($name))
            ->whereNotIn('l.idLexiconGroup', [1, 2, 7])
            ->where('l.idLanguage', $idLanguage)
            ->select('l.idLexicon')
            ->selectRaw("concat(l.form,' [', g.name,']') as name")
            ->limit(50)
            ->orderby('name')->all();
    }

    #[Get(path: '/lexicon3/form/new')]
    public function formNewForm()
    {
        return view('Lexicon3.formNewForm');
    }

    #[Post(path: '/lexicon3/form/new')]
    public function newForm(CreateLexiconData $data)
    {
        try {
            $exists = Criteria::table('lexicon')
                ->whereRaw("form = '{$data->form}' collate 'utf8mb4_bin'")
                ->where('idLexiconGroup', $data->idLexiconGroup)
                ->where('idLanguage', $data->idLanguage)
                ->first();
            if (!is_null($exists)) {
                throw new \Exception('Form already exists.');
            }
            $newForm = json_encode([
                'form' => $data->form,
                'idLexiconGroup' => $data->idLexiconGroup,
                'idLanguage' => $data->idLanguage,
            ]);
            $idLexicon = Criteria::function('lexicon_create(?)', [$newForm]);
            $lexicon = Lexicon::byId($idLexicon);

            return $this->renderNotify('success', 'Form created.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/lexicon3/wordform/{idWordForm}')]
    public function deleteWordform(string $idWordForm)
    {
        try {
            Criteria::deleteById('wordform', 'idWordForm', $idWordForm);
            $this->trigger('reload-gridWordforms');

            return $this->renderNotify('success', 'Wordform removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lexicon3/form/{idLexicon}')]
    public function lexicon(int $idLexicon, ?string $fragment = null)
    {
        $lexicon = Lexicon::byId($idLexicon);
        $lexicon->group = Criteria::byId('lexicon_group', 'idLexiconGroup', $lexicon->idLexiconGroup);
        $features = Criteria::table('udfeature as f')
            ->join('lexicon_feature as lf', 'f.idUDFeature', 'lf.idUDFeature')
            ->select('f.idUDFeature', 'f.name', 'lf.idLexicon')
            ->where('lf.idLexicon', $idLexicon)
            ->all();

        return view('Lexicon3.lexicon', [
            'lexicon' => $lexicon,
            'features' => $features,
        ]);
    }

    #[Put(path: '/lexicon3/lexicon')]
    public function updateLexicon(UpdateLexiconData $data)
    {
        try {
            if ($data->idLexiconGroup == 2) {
                throw new \Exception('Lemmas must use specific action.');
            }
            Criteria::table('lexicon')
                ->where('idLexicon', $data->idLexicon)
                ->update([
                    'form' => $data->form,
                    'idLexiconGroup' => $data->idLexiconGroup,
                ]);
            $this->trigger('reload-gridLexicon3');

            return $this->renderNotify('success', 'Form updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
*/
    /*--------
      Features
     -------- */
/*
    #[Get(path: '/lexicon3/feature/listForSelect')]
    public function listFeatureForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::table('udfeature')
            ->select('idUDFeature', 'name')
            ->whereRaw("lower(name) LIKE lower('{$name}%')")
            ->orderby('name')->all()];
    }

    #[Post(path: '/lexicon3/feature/new')]
    public function newFeature(CreateFeatureData $data)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexicon', $data->idLexiconBase)
                ->where('idUDFeature', $data->idUDFeature)
                ->delete();
            Criteria::create('lexicon_feature', [
                'idLexicon' => $data->idLexiconBase,
                'idUDFeature' => $data->idUDFeature,
            ]);
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature added.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lexicon3/feature/{idLexicon}')]
    public function features(int $idLexicon)
    {
        $lexicon = Lexicon::byId($idLexicon);
        $lexicon->group = Criteria::byId('lexicon_group', 'idLexiconGroup', $lexicon->idLexiconGroup);
        $features = Criteria::table('udfeature as f')
            ->join('lexicon_feature as lf', 'f.idUDFeature', 'lf.idUDFeature')
            ->select('f.idUDFeature', 'f.name', 'lf.idLexicon')
            ->where('lf.idLexicon', $idLexicon)
            ->all();

        return view('Lexicon3.features', [
            'lexicon' => $lexicon,
            'features' => $features,
        ]);
    }

    #[Delete(path: '/lexicon3/feature/{idLexicon}/{idUDFeature}')]
    public function deleteFeature(int $idLexicon, int $idUDFeature)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexicon', $idLexicon)
                ->where('idUDFeature', $idUDFeature)
                ->delete();
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
*/
}
