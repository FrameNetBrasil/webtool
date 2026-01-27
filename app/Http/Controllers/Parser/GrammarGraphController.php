<?php

namespace App\Http\Controllers\Parser;

use App\Data\Parser\Grammar\CreateData;
use App\Data\Parser\Grammar\SearchData;
use App\Data\Parser\Grammar\UpdateData;
use App\Http\Controllers\Controller;
use App\Repositories\Parser\GrammarGraph;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'master')]
class GrammarGraphController extends Controller
{
    #[Get(path: '/parser/grammar')]
    public function index(SearchData $search)
    {
        $grammars = GrammarGraph::listToGrid($search);

        return view('Parser.Grammar.index', [
            'grammars' => $grammars,
        ]);
    }

    #[Post(path: '/parser/grammar/search')]
    public function search(SearchData $search)
    {
        $grammars = GrammarGraph::listToGrid($search);

        return view('Parser.Grammar.index', [
            'grammars' => $grammars,
        ])->fragment('search');

    }

    /**
     * Show create grammar form
     */
    #[Get(path: '/parser/grammar/new')]
    public function new()
    {
        return view('Parser.Grammar.formNew');
    }

    /**
     * Show edit grammar page
     */
    #[Get(path: '/parser/grammar/{id}')]
    public function edit(int $id)
    {
        $grammar = GrammarGraph::byId($id);
        $grammar->constructionCount = GrammarGraph::countConstructions($id);

        return view('Parser.Grammar.edit', [
            'grammar' => $grammar,
        ]);
    }

    /**
     * Show edit grammar page
     */
    #[Get(path: '/parser/grammar/{id}/formEdit')]
    public function formEdit(int $id)
    {
        $grammar = GrammarGraph::byId($id);
        $grammar->constructionCount = GrammarGraph::countConstructions($id);

        return view('Parser.Grammar.formEdit', [
            'grammar' => $grammar,
        ]);
    }

    /**
     * Create new grammar graph
     */
    #[Post(path: '/parser/grammar')]
    public function create(CreateData $data)
    {
        try {
            $idGrammarGraph = GrammarGraph::create([
                'name' => $data->name,
                'language' => $data->language,
                'description' => $data->description,
            ]);

            $this->trigger('reload-gridGrammar');

            return $this->renderNotify('success', "Grammar graph '{$data->name}' created successfully.");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    /**
     * Update grammar graph
     */
    #[Post(path: '/parser/grammar/{id}')]
    public function update(int $id, UpdateData $data)
    {
        try {
            GrammarGraph::update($id, [
                'name' => $data->name,
                'language' => $data->language,
                'description' => $data->description,
            ]);

            $this->trigger('reload-gridGrammar');

            return $this->renderNotify('success', 'Grammar graph updated successfully.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    /**
     * Delete grammar graph
     */
    #[Delete(path: '/parser/grammar/{id}')]
    public function delete(int $id)
    {
        try {
            $grammar = GrammarGraph::byId($id);
            $grammarName = $grammar->name;

            GrammarGraph::delete($id);

            $this->trigger('reload-gridGrammar');
            $this->clientRedirect('/parser/grammar');

            return $this->renderNotify('success', "Grammar graph '{$grammarName}' deleted successfully.");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
