<?php

namespace App\Http\Controllers\Lexicon;

use App\Data\ComboBox\QData;
use App\Data\Form\CreateFormData;
use App\Data\Form\SearchFormData;
use App\Data\Form\UpdateFormData;
use App\Data\Lexicon\CreateFeatureData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Form;
use App\Services\AppService;
use App\Services\Form\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class FormController extends Controller
{
    #[Get(path: '/form')]
    public function browse(SearchFormData $search)
    {
        $data = BrowseService::browseFormBySearch($search);

        return view('Form.browse', [
            'title' => 'Forms',
            'data' => $data,
        ]);
    }

    #[Post(path: '/form/search')]
    public function search(SearchFormData $search)
    {
        $data = BrowseService::browseFormBySearch($search);

        return view('Form.tree', [
            'data' => $data,
            'title' => 'Forms',
        ]);
    }

    #[Get(path: '/form/morpheme/listForSelect')]
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

    #[Get(path: '/form/new')]
    public function formNew()
    {
        return view('Form.new');
    }

    #[Post(path: '/form')]
    public function create(CreateFormData $data)
    {
        try {
            $exists = Criteria::table('lexicon')
                ->whereRaw("form = '{$data->form}' collate 'utf8mb4_bin'")
                ->where('idLexiconGroup', $data->idLexiconGroup)
                ->where('idLanguage', $data->idLanguage)
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('Form already exists.');
            }
            $newForm = json_encode([
                'form' => $data->form,
                'idLexiconGroup' => $data->idLexiconGroup,
                'idLanguage' => $data->idLanguage,
            ]);
            $idLexicon = Criteria::function('lexicon_create(?)', [$newForm]);
            $form = Form::byId($idLexicon);

            return $this->renderNotify('success', 'Form created.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/form/wordform/{idWordForm}')]
    public function deleteWordform(int $idWordForm)
    {
        try {
            Criteria::deleteById('wordform', 'idWordForm', $idWordForm);
            $this->trigger('reload-gridWordforms');

            return $this->renderNotify('success', 'Wordform removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/form/{idLexicon}')]
    public function edit(int $idLexicon)
    {
        $form = Form::byId($idLexicon);
        $form->group = Criteria::byId('lexicon_group', 'idLexiconGroup', $form->idLexiconGroup);
        $features = Criteria::table('udfeature as f')
            ->join('lexicon_feature as lf', 'f.idUDFeature', 'lf.idUDFeature')
            ->select('f.idUDFeature', 'f.name', 'lf.idLexicon')
            ->where('lf.idLexicon', $idLexicon)
            ->all();

        return view('Form.edit', [
            'form' => $form,
            'features' => $features,
        ]);
    }

    #[Put(path: '/form/{idLexicon}')]
    public function update(int $idLexicon, UpdateFormData $data)
    {
        try {
            if ($data->idLexiconGroup == 2) {
                throw new \Exception('Lemmas must use specific action.');
            }
            Criteria::table('lexicon')
                ->where('idLexicon', $idLexicon)
                ->update([
                    'form' => $data->form,
                    'idLexiconGroup' => $data->idLexiconGroup,
                ]);
            $this->trigger('reload-gridForm');

            return $this->renderNotify('success', 'Form updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/form/{idLexicon}')]
    public function delete(int $idLexicon)
    {
        try {
            Criteria::function('lexicon_delete(?,?)', [$idLexicon, AppService::getCurrentIdUser()]);
            $this->trigger('reload-gridForm');

            return $this->renderNotify('success', 'Form removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', 'Deletion failed. Check if there is some usage of this form.');
        }
    }

    /*--------
      Features
     -------- */

    #[Get(path: '/form/feature/listForSelect')]
    public function listFeatureForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::table('udfeature')
            ->select('idUDFeature', 'name')
            ->whereRaw("lower(name) LIKE lower('{$name}%')")
            ->orderby('name')->all()];
    }

    #[Post(path: '/form/feature/{idLexiconExpression}')]
    public function createFeature(int $idLexiconExpression, CreateFeatureData $data)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexiconExpression', $idLexiconExpression)
                ->where('idUDFeature', $data->idUDFeature)
                ->delete();
            Criteria::create('lexicon_feature', [
                'idLexicon' => $idLexiconExpression,
                'idUDFeature' => $data->idUDFeature,
            ]);
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature added.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/form/features/{idLexiconExpression}')]
    public function features(int $idLexiconExpression)
    {
        $form = Form::byExpression($idLexiconExpression);
        $form->group = Criteria::byId('lexicon_group', 'idLexiconGroup', $form->idLexiconGroup);
        $features = Criteria::table('udfeature as f')
            ->join('lexicon_feature as lf', 'f.idUDFeature', 'lf.idUDFeature')
            ->select('f.idUDFeature', 'f.name', 'lf.idLexiconExpression')
            ->where('lf.idLexiconExpression', $idLexiconExpression)
            ->all();

        return view('Form.features', [
            'form' => $form,
            'features' => $features,
        ]);
    }

    #[Delete(path: '/form/feature/{idLexiconExpression}/{idUDFeature}')]
    public function deleteFeature(int $idLexiconExpression, int $idUDFeature)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexiconExpression', $idLexiconExpression)
                ->where('idUDFeature', $idUDFeature)
                ->delete();
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
