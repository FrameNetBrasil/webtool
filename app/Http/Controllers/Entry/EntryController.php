<?php

namespace App\Http\Controllers\Entry;

use App\Data\Entry\UpdateData;
use App\Data\Entry\UpdateSingleData;
use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class EntryController extends Controller
{
    #[Put(path: '/entry')]
    public function entry(UpdateData $data)
    {
        try {
            $languages = AppService::availableLanguages();
            foreach ($languages as $language) {
                $idLanguage = $language->idLanguage;
                $updateData = UpdateSingleData::from([
                    'idEntry' => $data->idEntry[$idLanguage],
                    'name' => $data->name[$idLanguage],
                    'description' => $data->description[$idLanguage],
                    'idLanguage' => $idLanguage,
                ]);
                Entry::update($updateData);
            }
            if ($data->trigger != '') {
                $this->trigger($data->trigger);
            }
            return $this->renderNotify("success", "Translations recorded.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}
