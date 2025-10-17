@php use App\Database\Criteria;use App\Services\AppService; @endphp
@php
    $idLanguage = AppService::getCurrentIdLanguage();
    $relationGroups = Criteria::table("view_relationgroup")
        ->select("idRelationGroup","name")
        ->where("idLanguage", $idLanguage)
        ->orderBy("name")
        ->keyBy("idRelationGroup")
        ->all();
    $relationTypes = Criteria::table("view_relationtype")
        ->select("idRelationType","nameCanonical","color","nameDirect","nameInverse","idRelationGroup")
        ->where("idLanguage", $idLanguage)
        ->orderBy("nameCanonical")
        ->get()
        ->groupBy("idRelationGroup")
        ->toArray();
@endphp
<div
    class="h-full"
    hx-trigger="reload-gridRelations from:body"
    hx-target="this"
    hx-swap="innerHTML"
    hx-post="/relations/grid"
>
    <div class="relative h-full overflow-auto">
        <table id="userTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <tbody
                >
                @foreach($relationGroups as $idRelationGroup => $relationGroup)
                    <tr
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="subheader"
                    >
                        <td
                            hx-get="/relations/relationgroup/{{$idRelationGroup}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                            colspan="3"
                        >
                            <span class="text-blue-900 font-bold">{{$relationGroup->name}}</span>
                        </td>
                    </tr>
                    @php($relations = $relationTypes[$idRelationGroup] ?? [])
                    @php( debug($relations))
                    @foreach($relations as $relationType)
                        <tr
                            hx-target="#editArea"
                            hx-swap="innerHTML"
                            hx-get="/relations/relationtype/{{$relationType->idRelationType}}/edit"
                        >
                            <td
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span class="pl-3" style="color:{{$relationType->color}}">{{$relationType->nameCanonical}}</span>
                            </td>
                            <td
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span class="pl-3" style="color:{{$relationType->color}}">{{$relationType->nameDirect}}</span>
                            </td>
                            <td
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span class="pl-3" style="color:{{$relationType->color}}">{{$relationType->nameInverse}}</span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            @endfragment
        </table>
    </div>
</div>
