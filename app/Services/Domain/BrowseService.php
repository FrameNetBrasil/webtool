<?php

namespace App\Services\Domain;

use App\Data\Domain\SearchData;
use App\Database\Criteria;

class BrowseService
{
    public static int $limit = 300;

    public static function browseAllDomains(): array
    {
        $result = [];
        $domains = Criteria::byFilterLanguage('view_domain', [])
            ->select('idDomain', 'name', 'description')
            ->orderBy('name')
            ->limit(self::$limit)
            ->all();

        foreach ($domains as $domain) {
            $result[$domain->idDomain] = [
                'id' => $domain->idDomain,
                'type' => 'domain',
                'text' => $domain->name,
                'leaf' => false, // Domains can be expanded to show semantic types
                'state' => 'closed',
            ];
        }

        return $result;
    }

    public static function browseDomainBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->domain != '') {
            $domains = Criteria::byFilterLanguage('view_domain', [])
                ->where('name', 'startswith', $search->domain)
                ->select('idDomain', 'name', 'description')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($domains as $domain) {
                $result[$domain->idDomain] = [
                    'id' => $domain->idDomain,
                    'type' => 'domain',
                    'text' => $domain->name,
                    'leaf' => $leaf,
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseSemanticTypeBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->semanticType != '') {
            $semanticTypes = Criteria::byFilterLanguage('view_semantictype', [])
                ->where('name', 'startswith', $search->semanticType)
                ->select('idSemanticType', 'name', 'idDomain')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($semanticTypes as $semanticType) {
                // Get domain name for display
                $domain = Criteria::byFilterLanguage('view_domain', ['idDomain', '=', $semanticType->idDomain])->first();
                $domainName = $domain->name ?? '';

                $result[$semanticType->idSemanticType] = [
                    'id' => $semanticType->idSemanticType,
                    'type' => 'semanticType',
                    'text' => $semanticType->name.($domainName ? ' ['.$domainName.']' : ''),
                    'leaf' => true,
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    /**
     * Get direct children (subtypes) of a semantic type
     */
    public static function getSemanticTypeChildren(int $idSemanticType): array
    {
        // Get all subtypes of this semantic type from the relation table
        $children = Criteria::table('view_semantictype_relation')
            ->where('relationType', 'rel_subtypeof')
            ->where('st2IdSemanticType', $idSemanticType)
            ->where('idLanguage', 1) // Use current language
            ->select('st1IdSemanticType as idSemanticType', 'st1Name as name')
            ->orderBy('st1Name')
            ->get()
            ->unique('idSemanticType')
            ->all();

        return $children;
    }

    /**
     * Check if a semantic type has any subtypes
     */
    public static function hasSemanticTypeChildren(int $idSemanticType): bool
    {
        $count = Criteria::table('view_semantictype_relation')
            ->where('relationType', 'rel_subtypeof')
            ->where('st2IdSemanticType', $idSemanticType)
            ->where('idLanguage', 1)
            ->count();

        return $count > 0;
    }

    /**
     * Recursively build semantic type tree with children
     */
    public static function buildSemanticTypeTree(array $semanticTypes): array
    {
        $result = [];
        foreach ($semanticTypes as $semanticType) {
            $hasChildren = self::hasSemanticTypeChildren($semanticType->idSemanticType);
            $children = [];

            if ($hasChildren) {
                $childTypes = self::getSemanticTypeChildren($semanticType->idSemanticType);
                $children = self::buildSemanticTypeTree($childTypes);
            }

            $result[] = [
                'id' => $semanticType->idSemanticType,
                'type' => 'semanticType',
                'text' => $semanticType->name,
                'leaf' => ! $hasChildren,
                'state' => $hasChildren ? 'closed' : 'open',
                'children' => $children,
            ];
        }

        return $result;
    }

    /**
     * Get top-level semantic types for a domain (those without parents in the same domain)
     */
    public static function getTopLevelSemanticTypes(int $idDomain): array
    {
        // Get all semantic types in this domain
        $allTypes = Criteria::byFilterLanguage('view_semantictype', [])
            ->where('idDomain', $idDomain)
            ->select('idSemanticType')
            ->limit(self::$limit)
            ->pluck('idSemanticType')
            ->toArray();

        // Get all semantic types that have a parent (subtype relation)
        $typesWithParents = Criteria::table('view_semantictype_relation')
            ->where('relationType', 'rel_subtypeof')
            ->where('idLanguage', 1)
            ->whereIn('st1IdSemanticType', $allTypes) // child is in this domain
            ->whereIn('st2IdSemanticType', $allTypes) // parent is also in this domain
            ->pluck('st1IdSemanticType')
            ->toArray();

        // Top-level types are those without parents (or parents outside domain)
        $topLevelIds = array_diff($allTypes, $typesWithParents);

        // Get the actual semantic type data
        $topLevelTypes = Criteria::byFilterLanguage('view_semantictype', [])
            ->whereIn('idSemanticType', $topLevelIds)
            ->select('idSemanticType', 'name')
            ->orderBy('name')
            ->all();

        return $topLevelTypes;
    }

    public static function browseSemanticTypesByDomain(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            // Get only top-level semantic types (no parent in same domain)
            $topLevelTypes = self::getTopLevelSemanticTypes($search->id);

            // Build the tree with all children recursively
            $result = self::buildSemanticTypeTree($topLevelTypes);
        }

        return $result;
    }

    /**
     * Browse subtypes of a specific semantic type (for tree expansion)
     */
    public static function browseSemanticTypeChildren(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $children = self::getSemanticTypeChildren($search->id);
            $result = self::buildSemanticTypeTree($children);
        }

        return $result;
    }

    public static function browseDomainSemanticTypeBySearch(SearchData $search): array
    {
        $result = [];

        // Handle tree expansion: if type is 'semanticType' and id is provided, return subtypes
        if ($search->type === 'semanticType' && $search->id != 0) {
            $result = self::browseSemanticTypeChildren($search);
        }
        // Handle tree expansion: if type is 'domain' and id is provided, return semantic types for that domain
        elseif ($search->type === 'domain' && $search->id != 0) {
            $result = self::browseSemanticTypesByDomain($search);
        }
        // If searching for specific domain ID (legacy behavior), return its semantic types
        elseif ($search->id != 0 && $search->type === '') {
            $result = self::browseSemanticTypesByDomain($search);
        } else {
            // If searching by semantic type name, return matching semantic types
            if ($search->semanticType != '') {
                $result = self::browseSemanticTypeBySearch($search);
            } else {
                // If searching by domain name, return filtered domains
                if ($search->domain != '') {
                    $result = self::browseDomainBySearch($search);
                } else {
                    // Show all domains by default
                    $result = self::browseAllDomains();
                }
            }
        }

        return $result;
    }
}
