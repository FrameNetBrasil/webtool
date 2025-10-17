<?php

namespace App\Services\Project;

use App\Database\Criteria;
use App\Data\Project\SearchData;

class BrowseService
{
    static int $limit = 300;

    public static function browseAllProjects(): array
    {
        $result = [];
        $projects = Criteria::table("project")
            ->select("idProject", "name", "description")
            ->orderBy("name")
            ->limit(self::$limit)
            ->all();

        foreach ($projects as $project) {
            $result[$project->idProject] = [
                'id' => $project->idProject,
                'type' => 'project',
                'text' => $project->name,
                'leaf' => false, // Projects can be expanded to show datasets
                'state' => 'closed'
            ];
        }
        return $result;
    }

    public static function browseProjectBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->project != '') {
            $projects = Criteria::table("project")
                ->where("name", "startswith", $search->project)
                ->select("idProject", "name", "description")
                ->orderBy("name")
                ->limit(self::$limit)
                ->all();

            foreach ($projects as $project) {
                $result[$project->idProject] = [
                    'id' => $project->idProject,
                    'type' => 'project',
                    'text' => $project->name,
                    'leaf' => $leaf,
                    'state' => 'closed'
                ];
            }
        }
        return $result;
    }

    public static function browseDatasetBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->dataset != '') {
            $datasets = Criteria::table("project_dataset")
                ->join("dataset", "project_dataset.idDataset", "=", "dataset.idDataset")
                ->join("project", "project_dataset.idProject", "=", "project.idProject")
                ->where("dataset.name", "startswith", $search->dataset)
                ->select('dataset.idDataset', 'dataset.name', 'project.name as projectName')
                ->orderBy("dataset.name")
                ->limit(self::$limit)
                ->all();

            foreach ($datasets as $dataset) {
                $result[$dataset->idDataset] = [
                    'id' => $dataset->idDataset,
                    'type' => 'dataset',
                    'text' => $dataset->name . ' [' . $dataset->projectName . ']',
                    'leaf' => true,
                    'state' => 'open'
                ];
            }
        }
        return $result;
    }

    public static function browseDatasetsByProject(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $datasets = Criteria::table("project_dataset")
                ->join("dataset", "project_dataset.idDataset", "=", "dataset.idDataset")
                ->where("project_dataset.idProject", $search->id)
                ->select('dataset.idDataset', 'dataset.name')
                ->orderBy("dataset.name")
                ->limit(self::$limit)
                ->all();

            foreach ($datasets as $dataset) {
                $result[$dataset->idDataset] = [
                    'id' => $dataset->idDataset,
                    'type' => 'dataset',
                    'text' => $dataset->name,
                    'leaf' => true,
                    'state' => 'open'
                ];
            }
        }
        return $result;
    }

    public static function browseProjectDatasetBySearch(SearchData $search): array
    {
        $result = [];

        // Handle tree expansion: if type is 'project' and id is provided, return datasets for that project
        if ($search->type === 'project' && $search->id != 0) {
            $result = self::browseDatasetsByProject($search);
        }
        // If searching for specific project ID (legacy behavior), return its datasets
        elseif ($search->id != 0 && $search->type === '') {
            $result = self::browseDatasetsByProject($search);
        } else {
            // If searching by dataset name, return matching datasets
            if ($search->dataset != '') {
                $result = self::browseDatasetBySearch($search);
            } else {
                // If searching by project name, return filtered projects
                if ($search->project != '') {
                    $result = self::browseProjectBySearch($search);
                } else {
                    // Show all projects by default (NEW BEHAVIOR)
                    $result = self::browseAllProjects();
                }
            }
        }

        return $result;
    }
}