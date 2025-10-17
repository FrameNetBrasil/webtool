<?php

namespace App\Services\Form;

use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static int $limit = 300;

    public static function browseFormBySearch(object $search): array
    {
        $result = [];
        if ($search->form != '') {
            $forms = Criteria::byFilter('view_form', [
                ['form', 'startswith', $search->form],
                ['idLanguage', '=', AppService::getCurrentIdLanguage()],
            ])->select('idLexicon', 'form','udPOS')
                ->distinct()
                ->limit(self::$limit)
                ->orderBy('form')->all();
            foreach ($forms as $form) {
                $result[$form->idLexicon] = [
                    'id' => $form->idLexicon,
                    'type' => 'form',
                    'text' => view('Form.partials.tree-item', (array) $form)->render(),
                    'leaf' => true,
                ];
            }
        }

        return $result;
    }
}
