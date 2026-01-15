# Daisy Disambiguation Pipeline - Technical Documentation

This document provides comprehensive technical documentation for the Daisy semantic disambiguation pipeline implemented in Laravel 12. It covers the complete architecture, API endpoints, service layers, and algorithm details for frame semantic disambiguation using FrameNet and the GRID (Graph-based Reasoning for Inference and Disambiguation) algorithm.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Overview](#overview)
3. [Pipeline Architecture](#pipeline-architecture)
4. [Step 1: Universal Dependencies Parsing](#step-1-universal-dependencies-parsing)
5. [Step 2: GRID Window Creation](#step-2-grid-window-creation)
6. [Step 3: Lexical Unit Matching](#step-3-lexical-unit-matching)
7. [Step 4: Semantic Network Construction](#step-4-semantic-network-construction)
8. [Step 5: Spreading Activation](#step-5-spreading-activation)
9. [Step 6: Winner Selection](#step-6-winner-selection)
10. [Data Structures](#data-structures)
11. [Database Schema Requirements](#database-schema-requirements)
12. [Configuration Parameters](#configuration-parameters)
13. [Laravel API Implementation](#laravel-api-implementation)
14. [Algorithms](#algorithms)
15. [Implementation Status](#implementation-status)

---

## Quick Start

### For API Users

**Endpoint:** `POST /daisy/parse`

**Request:**
```bash
curl -X POST http://localhost:8001/daisy/parse \
  -H "Content-Type: application/json" \
  -d '{
    "sentence": "O menino construiu uma casa",
    "idLanguage": 1,
    "searchType": 2,
    "level": 1
  }'
```

**Response:** HTML view with disambiguation results, including:
- Semantic frames identified for each word
- Energy scores showing confidence
- Graph visualization data
- Universal Dependencies parse

### For Developers

**Using the Service:**

```php
use App\Data\Daisy\DaisyInputData;
use App\Services\Daisy\DaisyService;

$input = new DaisyInputData(
    sentence: 'O menino construiu uma casa',
    idLanguage: 1,      // 1=Portuguese, 2=English
    searchType: 2,      // 1-4, higher = more comprehensive
    level: 1,           // 1-5, higher = deeper networks
    gregnetMode: false
);

$result = app(DaisyService::class)->disambiguate($input);

// Access results
$winners = $result->result;      // Disambiguated frames per word
$graph = $result->graph;         // Visualization data
$udParse = $result->sentenceUD;  // UD parse
$weights = $result->weights;     // Energy scores
```

**Key Files:**
- `app/Http/Controllers/Daisy/DaisyController.php` - HTTP endpoints
- `app/Services/Daisy/DaisyService.php` - Main orchestrator
- `app/Data/Daisy/DaisyInputData.php` - Input DTO
- `app/Data/Daisy/DaisyOutputData.php` - Output DTO

---

## Overview

**Daisy** (Disambiguation Algorithm for Inferring the Semantics of Y) is a frame semantic disambiguator that:
- Takes natural language text as input
- Identifies semantic frames evoked by words using FrameNet
- Disambiguates between multiple candidate frames
- Uses a graph-based spreading activation algorithm (GRID)

### Laravel Implementation

#### API Endpoints

- **POST** `/daisy/parse` - Main disambiguation endpoint, returns HTML view with results
- **POST** `/daisy/graph` - Graph visualization endpoint, returns graph data only
- **GET** `/daisy` - Interface for Daisy semantic parser

#### Main Services

- `DaisyService.php` - Main orchestrator that coordinates the complete pipeline
- `GridService.php` - GRID parsing and window creation
- `TrankitService.php` - Universal Dependencies parsing interface
- `LexicalUnitService.php` - Lexical unit matching
- `SemanticNetworkService.php` - Semantic network construction
- `SpreadingActivationService.php` - Energy spreading algorithm
- `WinnerSelectionService.php` - Winner selection and formatting

#### Data Objects

- `DaisyInputData.php` - Input validation and type-safe input parameters
- `DaisyOutputData.php` - Structured output with results, graph, UD parse, and weights

---

## Pipeline Architecture

```
HTTP POST /daisy/parse
     ↓
DaisyController::parse(DaisyInputData)
     ↓
DaisyService::disambiguate()
     ↓
┌─────────────────────────────────────────────┐
│                                             │
│  [1. UD Parsing]                            │
│      TrankitService                         │
│      → Trankit REST API                     │
│                                             │
│  [2. GRID Window Creation]                  │
│      GridService::processToWindows()        │
│      → Parse to windows & lemmas            │
│                                             │
│  [3. Lexical Unit Matching]                 │
│      LexicalUnitService::matchLexicalUnits()│
│      → Query FrameNet DB                    │
│                                             │
│  [4. Semantic Network Construction]         │
│      SemanticNetworkService::buildSemanticNetworks()│
│      → Build frame graph & pools            │
│                                             │
│  [5. Spreading Activation]                  │
│      SpreadingActivationService::processSpreadingActivation()│
│      → Calculate energy distribution        │
│                                             │
│  [6. Winner Selection]                      │
│      WinnerSelectionService::generateWinners()│
│      → Select and format winners            │
│                                             │
└─────────────────────────────────────────────┘
     ↓
DaisyOutputData (result, graph, sentenceUD, weights)
     ↓
Blade View: Daisy.daisyResults
     ↓
HTTP Response: HTML with results table & graph
```

---

## Step 1: Universal Dependencies Parsing

### Input
- Raw sentence string
- Language ID (1=Portuguese, 2=English)

### Process

1. **Call Trankit REST API**
   - Configured via `config('daisy.trankitUrl')` (default: `http://localhost:8405`)
   - Trankit provides Universal Dependencies parsing for multiple languages
   - Method: `TrankitService::getUDTrankit($sentence, $idLanguage)`

2. **Parse Response**
   Extract UD parse data with columns:
   - `id` - Word index in sentence
   - `word` - Word form (surface text)
   - `pos` - UPOS tag (Universal Part-of-Speech)
   - `deps` - Dependency head index
   - `ud` - Dependency relation type

3. **Filter Relations** (getDaisyUD)
   Keep only these UD relations relevant for semantic parsing:
   ```php
   'root', 'nsubj', 'obj', 'iobj', 'csubj', 'ccomp',
   'xcomp', 'obl', 'advcl', 'acl', 'amod', 'nmod', 'advmod'
   ```

### Output
Array of word objects:
```php
[
  'id' => '1',
  'word' => 'casa',
  'pos' => 'NOUN',
  'ud' => 'root',
  'deps' => '0'
]
```

### Implementation Notes
- If relation contains ':', split and use first part (e.g., `nmod:tmod` → `nmod`)
- Store ALL words but mark which are in `keep` set
- Preserve original word order using `id`
- Trankit is initialized in `DaisyService::__construct()` with configured URL
- Parsing is performed via `DaisyService::parseWithTrankit()`

---

## Step 2: GRID Window Creation

### Input
- UD parsed sentence

### Process

**2.1 Map UPOS to Grid Functions**

```php
$udToGrid = [
    'ADJ'   => 'QE',      // Quality Entity
    'ADP'   => 'REL',     // Relation
    'PUNCT' => 'PUNCT',   // Punctuation
    'ADV'   => 'QV',      // Quality Event
    'AUX'   => 'AUX',     // Auxiliary
    'SYM'   => 'ENT',     // Entity
    'INTJ'  => 'ENT',
    'CCONJ' => 'REL',     // Coordinating conjunction
    'X'     => 'ENT',
    'NOUN'  => 'ENT',     // Entity
    'DET'   => 'REF',     // Reference
    'PROPN' => 'ENT',     // Proper noun
    'NUM'   => 'QUANT',   // Quantifier
    'VERB'  => 'EVT',     // Event
    'PART'  => 'ENT',
    'PRON'  => 'ENT',
    'SCONJ' => 'REL',     // Subordinating conjunction
];
```

**2.2 Query Grid Lemmas**

For each word, query database:
```sql
SELECT lemma, idLemma, pos, word, mwe (multiword expression)
FROM grid_lemmas
WHERE word = :word
```

Match lemma to UD POS and validate against grid functions.

**2.3 Determine Grid Function (fnDef)**

For each word with multiple possible functions:
- Compare with previous word using `combinationValue` matrix
- Compare with next word using `combinationValue` matrix
- Select function with highest combination value
- First word: only compare with next
- Resolve ambiguities based on context

**2.4 Create Clusters**

Group consecutive words with compatible functions:
- `ClusterEnt` - Entity cluster (ENT, REF)
- `ClusterEvt` - Event cluster (EVT, AUX)
- `ClusterQuale` - Quality cluster (QE, QV, QUANT)
- `ClusterRel` - Relation cluster (REL)
- `ClusterJoin` - Join/coordination
- `ClusterPunct` - Punctuation

**2.5 Create Windows**

Windows are semantic units:
- Start with first cluster
- New window on:
  - PUNCT cluster
  - JOIN cluster (coordination)
  - REL cluster that changes domain
- Windows can be nested (have `up` pointer)

### Output

```php
$windows = [
  1 => [ // window ID
    'casa' => [ // word
      'Frame.Building' => [ // frame entry
        'lu' => 'casa.n',
        'idLU' => 123,
        'idFrame' => 45,
        'energy' => 0.5,
        'pool' => [...]
      ]
    ]
  ]
];

$lemmas = [
  1 => (object)[
    'id' => 1,
    'word' => 'casa',
    'fn' => ['ENT'],
    'fnDef' => 'ENT',
    'lemmas' => [...]
  ]
];
```

---

## Step 3: Lexical Unit Matching

### Input
- Window-parsed sentence with grid functions
- Language ID (1=Portuguese, 2=English)

### Process

**3.1 Query Lexical Units**

For each word component, query:

```sql
SELECT DISTINCT
  lu.idFrame,
  lu.frameEntry,
  lu.name AS lu,
  lu.idLU,
  lu.idEntity AS idEntityLU,
  frame.idEntity AS idEntityFrame,
  pos.pos AS POSLemma,
  lu.idLemma,
  (d.idEntityRel IS NOT NULL) AS mknob  -- domain=5 (MKNOB)
FROM View_LU lu
INNER JOIN Frame ON (lu.idFrame = frame.idFrame)
INNER JOIN POS ON (lu.idPOS = POS.idPOS)
LEFT JOIN (
  SELECT idEntityRel
  FROM View_Domain
  WHERE idDomain = 5
) d ON (frame.idEntity = d.idEntityRel)
WHERE lu.idLanguage = :idLanguage
  AND lu.idLemma IN (:idLemmas)
```

**3.2 Match POS**

Filter results where:
- Lemma POS matches grid function
- `ENT` → N (Noun)
- `EVT` → V (Verb)
- `QE` → A (Adjective)
- `QV` → ADV (Adverb)

**3.3 Create Frame Sets**

For each word, store all candidate LUs/frames:
```php
$parse[$frameEntry] = [
    'lu' => 'casa.n',
    'idLU' => 123,
    'idFrame' => 45,
    'energy' => 1.0 / count($candidateFrames), // initial energy
    'iword' => wordIndex,
    'id' => componentId,
    'mwe' => 0, // is multi-word expression
    'mknob' => 1, // is domain-specific (MKNOB)
    'pool' => [] // to be populated
];
```

### Output
Array mapping words → frames with initial energy distribution.

---

## Step 4: Semantic Network Construction

This is the core of GRID algorithm. For each candidate frame, build a semantic network.

### 4.1 Search Types

```php
// searchType parameter controls network depth:
// 1: Direct frames only
// 2: Direct + Frame family relations
// 3: Level 2 + Frame Element constraints
// 4: Level 3 + Qualia relations
```

### 4.2 Direct Frames

Initial frame evoked by the LU:
```php
$pool[$frameEntry] = [
    'frameName' => $frameEntry,
    'factor' => 1.0,
    'baseFrame' => $frameEntry,
    'level' => 1
];
```

### 4.3 Frame Family Relations (searchType ≥ 2)

Query related frames:

```sql
SELECT frame.idFrame, RelationType.entry, frame.Entry AS frameEntry
FROM Frame
INNER JOIN Entity entity1 ON (Frame.idEntity = entity1.idEntity)
INNER JOIN EntityRelation ON (entity1.idEntity = EntityRelation.idEntity1)
INNER JOIN RelationType ON (EntityRelation.idRelationType = RelationType.idRelationType)
INNER JOIN Entity entity2 ON (EntityRelation.idEntity2 = entity2.idEntity)
INNER JOIN Frame relatedFrame ON (entity2.idEntity = relatedFrame.idEntity)
WHERE relatedFrame.idFrame = :idFrame
  AND RelationType.entry IN (
    'rel_inheritance',
    'rel_perspective_on',
    'rel_subframe',
    'rel_using'
  )
ORDER BY RelationType.entry, relatedFrame.idFrame
```

**Relation Weights:**
```php
switch ($relation) {
    case 'rel_inheritance':
        $factor = 1.0 * $parentValue;
        break;
    case 'rel_perspective_on':
        $factor = 0.9 * $parentValue;
        break;
    case 'rel_subframe':
        $factor = 0.7 * $parentValue;
        break;
    case 'rel_using':
        $factor = 0.0; // not used
        break;
}
```

**Recursive Expansion:**
- Continue recursively up to `level` depth (default: 1)
- Reduce value by 0.4 at each level: `value - 0.4`
- Stop when value < 0

### 4.4 Frame Element Constraints (searchType ≥ 3)

Query frames constrained by FE:

```sql
SELECT fr.idFrame, fe.typeEntry
FROM view_frameelement fe
JOIN view_relation r ON (fe.idEntity = r.idEntity2)
JOIN frame fr ON (r.idEntity3 = fr.idEntity)
WHERE r.relationType = 'rel_constraint_frame'
  AND fe.idFrame = :idFrame
```

**FE Core Weight:**
```php
if ($feType == 'cty_core') {
    $factor = 0.5;
} else {
    $factor = 0.0; // ignore non-core
}
```

### 4.5 Qualia Relations (searchType ≥ 4)

For LUs in the sentence, find qualia-related LUs:

```sql
-- Bidirectional qualia search
SELECT lu2.idLU
FROM View_Relation r
JOIN View_LU lu1 ON (r.idEntity1 = lu1.idEntity)
JOIN View_LU lu2 ON (r.idEntity2 = lu2.idEntity)
LEFT JOIN Qualia q ON (r.idEntity3 = q.idEntity)
LEFT JOIN View_relation rq ON (q.idEntity = rq.idEntity1)
WHERE lu1.idLU = :idLU
  AND r.relationGroup = 'rgp_qualia'
  AND rq.relationType = 'rel_qualia_frame'
  AND lu1.idLanguage = :lang
  AND lu2.idLanguage = :lang
UNION
SELECT lu1.idLU
FROM View_Relation r
JOIN View_LU lu1 ON (r.idEntity1 = lu1.idEntity)
JOIN View_LU lu2 ON (r.idEntity2 = lu2.idEntity)
LEFT JOIN Qualia q ON (r.idEntity3 = q.idEntity)
LEFT JOIN View_relation rq ON (q.idEntity = rq.idEntity1)
WHERE lu2.idLU = :idLU
  AND r.relationGroup = 'rgp_qualia'
  AND rq.relationType = 'rel_qualia_frame'
  AND lu1.idLanguage = :lang
  AND lu2.idLanguage = :lang
```

**Qualia Depth Search:**
```php
$depth = 2; // search up to 2 levels
$qualiaValue = [];
$searched = [];
$toSearch = [$idLU1];

for ($i = 1; $i < $depth + 1; $i++) {
    $nextSearch = [];
    foreach ($toSearch as $idLU) {
        $results = queryQualia($idLU);
        foreach ($results as $relatedLU) {
            if (!isset($searched[$relatedLU])) {
                $nextSearch[] = $relatedLU;
                $searched[$relatedLU] = true;
                // Weight decreases with depth
                $qualiaValue[] = [$relatedLU, 0.9 / $i];
            }
        }
    }
    $toSearch = $nextSearch;
}
```

### 4.6 Pool Objects

**Purpose:** Track which words contribute to which frames

For each related frame, create pool object:

```php
$poolObject = (object)[
    'frameName' => $relatedFrameName,
    'set' => [
        $word => [
            'frame' => $baseFrame,
            'energy' => $factor,
            'iword' => $wordIndex,
            'level' => $relationLevel,
            'idWindow' => $windowId,
            'isQualia' => false
        ]
    ]
];
```

**Pool Merging:**
If frame already in pool:
```php
if ($newEnergy > $existingEnergy) {
    // Update with higher energy
    $poolObject->set[$word]['energy'] = $newEnergy;
}
```

### Output

```php
$windows[$idWindow][$word][$frameName] = [
    'lu' => 'casa.n',
    'idLU' => 123,
    'idFrame' => 45,
    'energy' => 0.5,  // initial
    'pool' => [
        'Frame.Building' => (object)[
            'frameName' => 'Frame.Building',
            'set' => [
                'casa' => ['energy' => 1.0, ...],
                'construir' => ['energy' => 0.7, ...]
            ]
        ],
        'Frame.Inheritance_Building' => {...}
    ]
];
```

---

## Step 5: Spreading Activation

### Input
- Windows with frames and their pool objects

### Algorithm

For each window:
  For each word in window:
    For each candidate frame of word:
      For each related frame in pool:
        For each word contributing to related frame:
          If contributing word ≠ current word:
            If (contributing word is from same window) OR (is from qualia):
              **Add contributing energy to current frame**

### Pseudo-code

```php
function processWindows($windows) {
    foreach ($windows as $idWindow => $words) {
        foreach ($words as $word => $frames) {
            foreach ($frames as $frameName => $frame) {
                $currentWord = $word;
                $currentEnergy = $frame['energy'];

                // Spread activation from pool
                foreach ($frame['pool'] as $poolObject) {
                    foreach ($poolObject->set as $contributingWord => $element) {
                        // Don't self-activate
                        if ($currentWord != $contributingWord) {
                            // Check if can use this energy
                            $canUse = $element['isQualia']
                                   || ($element['idWindow'] == $idWindow);

                            if ($canUse) {
                                $currentEnergy += $element['energy'];
                            }
                        }
                    }
                }

                // Update final energy
                $windows[$idWindow][$word][$frameName]['energy'] = $currentEnergy;
            }
        }
    }
    return $windows;
}
```

### Energy Boosts

Additional energy added:

```php
$energy = $baseEnergy;

// Qualia boost
if (isset($qualiaFrames[$idLU])) {
    foreach ($qualiaFrames[$idLU] as $qualiaEnergy) {
        $energy += $qualiaEnergy; // typically 0.9 or 0.45
    }
}

// Multi-word expression boost
if ($isMWE) {
    $energy += 10;
}

// Domain-specific boost (MKNOB)
if ($isMknob) {
    $energy += 5;
}
```

### Output

Updated energy values for all candidate frames:

```php
$weight[$idWindow][$idLU] = $finalEnergy;
```

---

## Step 6: Winner Selection

### Input
- Windows with final energy scores

### Algorithm

```php
function generateWinner($windows, $weight, $lus, $qualiaFrame, $luEquivalence) {
    $winner = [];

    foreach ($windows as $idWindow => $words) {
        foreach ($words as $word => $frames) {
            $maxEnergy = 0;

            foreach ($frames as $frameName => $frame) {
                $energy = $frame['energy'];

                // Apply bonuses
                if (isset($qualiaFrame[$frame['idLU']])) {
                    foreach ($qualiaFrame[$frame['idLU']] as $qualiaValue) {
                        $energy += (float)$qualiaValue;
                    }
                }

                if ($lus[$frame['idLU']][3] == 1) { // is MWE
                    $energy += 10;
                }

                if ($frame['mknob'] == 1) {
                    $energy += 5;
                }

                $finalEnergy = number_format($energy, 2);
                $weight[$idWindow][$frame['idLU']] = $finalEnergy;

                // Winner selection logic
                // Skip verbs (.v) - they are not selected as winners
                if (strpos($frame['lu'], '.v') > 0) {
                    $winner[$frame['iword']] = [];
                } else {
                    if ($energy > $maxEnergy) {
                        // New winner
                        $winner[$frame['iword']] = [[
                            'idLU' => $frame['idLU'],
                            'lu' => $frame['lu'],
                            'frame' => $frameName,
                            'value' => $finalEnergy,
                            'equivalence' => $luEquivalence[$frame['idLU']] ?? ''
                        ]];
                        $maxEnergy = $energy;
                    } else if ($energy == $maxEnergy) {
                        // Tie - empty winner (ambiguous)
                        $winner[$frame['iword']] = [];
                    }
                }
            }
        }
    }

    return $winner;
}
```

### Special Cases

1. **Verb Exclusion**: Verbs (`.v` suffix) are NOT selected as winners
2. **Ties**: If multiple frames have same energy → no winner (ambiguous)
3. **GregNet Mode**: Include all POS (v, a, n) and allow multiple winners per word

### Output

```php
$result = [
    'result' => [ // per window results
        1 => [
            'casa' => [
                ['frame' => '(casa.n:Frame.Building:0)', 'value' => '12.50']
            ]
        ]
    ],
    'graph' => [...], // visualization graph
    'sentenceUD' => [...] // UD parse
];

$winner = [ // winner per word index
    0 => [
        [
            'idLU' => 123,
            'lu' => 'casa.n',
            'frame' => 'Frame.Building',
            'value' => '12.50',
            'equivalence' => 'house.n' // English equivalent
        ]
    ]
];
```

---

## Data Structures

### Window Structure

```php
class Window {
    public $id;
    public $up = 0;          // parent window ID
    public $components = []; // word components
    public $clusters = [];   // clusters in this window

    public function canAddCluster($cluster) {
        // Logic to determine if cluster fits
    }

    public function addCluster($cluster) {
        // Add cluster to window
    }
}
```

### Component Structure

```php
class Component {
    public $id;
    public $idLemma;     // lemma ID
    public $fn;          // grid function
    public $word;        // word text
    public $idCluster;   // parent cluster
    public $main = false;// is main component
    private $head = 0;   // dependency head

    public function head($value = null) {
        if ($value !== null) {
            $this->head = $value;
        }
        return $this->head;
    }
}
```

### Cluster Types

```php
class ClusterEnt {   // Entity
    public $type = 'ENT';
    public $canUse = ['ENT', 'REF', 'QUANT'];
}

class ClusterEvt {   // Event
    public $type = 'EVT';
    public $canUse = ['EVT', 'AUX'];
}

class ClusterQuale { // Quality
    public $type = 'QE';
    public $canUse = ['QE', 'QV', 'QUANT'];
}

class ClusterRel {   // Relation
    public $type = 'REL';
    public $canUse = ['REL'];
}
```

---

## Database Schema Requirements

### Core Tables

**View_LU** (Lexical Units)
```sql
- idLU          : primary key
- name          : LU name (e.g., "casa.n")
- idFrame       : foreign key to Frame
- frameEntry    : frame name (e.g., "Frame.Building")
- idLemma       : foreign key to Lemma
- idPOS         : foreign key to POS
- idLanguage    : 1=PT, 2=EN
- idEntity      : entity ID for relations
```

**Frame**
```sql
- idFrame       : primary key
- Entry         : frame name
- idEntity      : entity ID for relations
```

**View_WFLexemeLemma**
```sql
- idLemma       : lemma ID
- form          : word form
- lemma         : lemma text
- POSLemma      : POS tag (N, V, A, ADV)
- lexeme        : base lexeme
```

**EntityRelation**
```sql
- idEntity1     : source entity
- idEntity2     : target entity
- idRelationType: relation type
- idEntity3     : optional qualia entity
```

**RelationType**
```sql
- idRelationType: primary key
- entry         : relation name
  - rel_inheritance
  - rel_perspective_on
  - rel_subframe
  - rel_using
  - rel_constraint_frame
  - rel_luequivalence
  - rel_qualia_frame
```

**View_FrameElement**
```sql
- idFrame       : frame ID
- typeEntry     : cty_core, cty_peripheral, etc.
- idEntity      : entity ID
```

**Qualia**
```sql
- idEntity      : entity ID
- info about qualia structure
```

**View_Domain**
```sql
- idDomain      : domain ID (5=MKNOB)
- idEntityRel   : related entity
```

---

## Configuration Parameters

### DaisyInputData Structure

The Laravel implementation uses `DaisyInputData` (Data Transfer Object with validation):

```php
class DaisyInputData extends Data
{
    public function __construct(
        public string $sentence,          // Required: min 3 chars
        public int $idLanguage = 1,       // Required: 1=Portuguese, 2=English
        public int $searchType = 2,       // Required: 1-4 (network depth)
        public int $level = 1,            // Required: 1-5 (relation depth)
        public bool $gregnetMode = false, // Optional: GregNet mode
    ) {}
}
```

#### Validation Rules

```php
'sentence'    => 'required|string|min:3'
'idLanguage'  => 'required|integer|in:1,2'
'searchType'  => 'required|integer|min:1|max:4'
'level'       => 'required|integer|min:1|max:5'
'gregnetMode' => 'boolean'
```

#### Request Example (POST /daisy/parse)

```json
{
  "sentence": "O menino construiu uma casa",
  "idLanguage": 1,
  "searchType": 2,
  "level": 1,
  "gregnetMode": false
}
```

### Parameter Descriptions

**idLanguage** - Target language for parsing and lexical unit matching:
- `1`: Portuguese (default)
- `2`: English

**searchType** - Semantic network construction depth:
- `1`: Direct frames only (fastest)
- `2`: Direct + frame family relations (inheritance, perspective, subframe) _(default)_
- `3`: Level 2 + Frame Element constraints
- `4`: Level 3 + Qualia relations (slowest, most comprehensive, recommended for best results)

**level** - Frame relation expansion depth:
- `1`: One level of frame relations (fast) _(default)_
- `2-5`: Deeper network expansion (slower, more comprehensive)
- Higher values explore more distant frame relations

**gregnetMode** - Special mode for knowledge graph construction:
- `false`: Standard disambiguation (default)
- `true`: Include all POS types and multiple winners per word

---

## Laravel API Implementation

### DaisyController::parse() Method

Located in `app/Http/Controllers/Daisy/DaisyController.php:42`

```php
#[Post(path: '/daisy/parse')]
public function parse(DaisyInputData $data)
{
    try {
        // Run Daisy disambiguation pipeline
        $result = $this->daisyService->disambiguate($data);

        // Return results view
        return view('Daisy.daisyResults', [
            'result' => $result->result,
            'graph' => $result->graph,
            'sentenceUD' => $result->sentenceUD,
            'weights' => $result->weights,
            'sentence' => $data->sentence,
        ]);
    } catch (\Exception $e) {
        logger()->error('Daisy parse error: '.$e->getMessage());

        return view('Daisy.daisyResults', [
            'error' => 'Error processing sentence: '.$e->getMessage(),
            'result' => [],
            'graph' => ['nodes' => [], 'links' => []],
            'sentenceUD' => [],
            'weights' => [],
            'sentence' => $data->sentence,
        ]);
    }
}
```

### DaisyOutputData Structure

```php
class DaisyOutputData extends Data
{
    public function __construct(
        public array $result,      // Formatted winners per window
        public array $graph,       // Graph visualization data
        public array $sentenceUD,  // Universal Dependencies parse
        public array $windows,     // Internal window structures
        public array $weights      // Energy scores per LU
    ) {}
}
```

### Output Structure

#### result - Disambiguation Winners
```php
[
  1 => [  // Window ID
    'casa' => [  // Word
      [
        'frame' => '(casa.n:Frame.Building:0)',
        'value' => '12.50'
      ]
    ]
  ]
]
```

#### graph - Visualization Data
```php
[
  'nodes' => [
    // Word nodes
    ['id' => 'word_1', 'label' => 'casa', 'type' => 'word'],
    // Frame nodes
    ['id' => 'frame_45', 'label' => 'Building', 'type' => 'frame'],
  ],
  'links' => [
    // Evokes relations
    ['source' => 'word_1', 'target' => 'frame_45', 'type' => 'evokes'],
    // Frame relations
    ['source' => 'frame_45', 'target' => 'frame_12', 'type' => 'inheritance'],
  ]
]
```

#### sentenceUD - Universal Dependencies Parse
```php
[
  [
    'id' => '1',
    'word' => 'casa',
    'pos' => 'NOUN',
    'ud' => 'root',
    'deps' => '0'
  ],
  // ... more words
]
```

#### weights - Energy Scores
```php
[
  1 => [  // Window ID
    123 => '12.50',  // idLU => energy value
    456 => '8.30',
  ]
]
```

### DaisyService::disambiguate() Pipeline

Located in `app/Services/Daisy/DaisyService.php:52`

The main orchestration method that executes the complete 6-step pipeline:

```php
public function disambiguate(DaisyInputData $input): DaisyOutputData
{
    // Step 1: Parse sentence with Trankit (UD parsing)
    $udParsed = $this->parseWithTrankit($input->sentence, $input->idLanguage);

    // Step 2: Create GRID windows
    $gridResult = $this->gridService->processToWindows($udParsed);
    $windows = $gridResult['windows'];
    $lemmas = $gridResult['lemmas'];

    // Step 3: Match lexical units
    $windows = $this->lexicalUnitService->matchLexicalUnits($windows, $lemmas);

    // Step 4: Build semantic networks
    $windows = $this->semanticNetworkService->buildSemanticNetworks($windows);

    // Step 5: Apply spreading activation
    $windows = $this->spreadingActivationService->processSpreadingActivation($windows);

    // Step 6: Select winners
    $winnerResult = $this->winnerSelectionService->generateWinners($windows);
    $winners = $winnerResult['winners'];
    $weights = $winnerResult['weights'];

    // Format results
    $result = $this->winnerSelectionService->formatWinners($winners, $windows);

    // Generate graph visualization data
    $graph = $this->generateGraphData($windows, $winners, $udParsed);

    return new DaisyOutputData(
        result: $result,
        graph: $graph,
        sentenceUD: $udParsed,
        windows: $windows,
        weights: $weights
    );
}
```

### Error Handling

The controller implements comprehensive error handling:

1. **Input Validation**: Laravel automatically validates via `DaisyInputData::rules()`
2. **Pipeline Errors**: Caught and logged with full stack trace
3. **User Feedback**: Returns view with error message and empty structures
4. **Logging**: Errors logged via Laravel logger with context

```php
logger()->error('Daisy parse error: '.$e->getMessage(), [
    'trace' => $e->getTraceAsString(),
]);
```

### View Integration

The controller returns Blade views:

- `Daisy.daisyResults` - Full results with table and graph
- `Daisy.daisyGraph` - Graph visualization only (POST /daisy/graph)
- `Daisy.daisy` - Input interface (GET /daisy)

---

### Combination Value Matrix

File: `include/combinationValue.php`

Defines which grid functions can combine:

```php
$combinationRel = [
    'ENT' => [
        'REL' => [1, 10],  // [can_combine, weight]
        'QE'  => [1, 9],
        'QUANT' => [1, 8],
    ],
    'EVT' => [
        'ENT' => [1, 10],
        'REL' => [1, 9],
        'QV'  => [1, 8],
    ],
    // ... etc
];
```

### Dependency Value Matrix

Defines cluster dependencies:

```php
$depRel = [
    'ENT' => ['EVT' => 1],
    'EVT' => ['REL' => 1],
    'REL' => ['ENT' => 1],
    'QE'  => ['ENT' => 1],
    // ... etc
];
```

---

## Algorithms

### Algorithm 1: Function Disambiguation

```
For each word i in sentence:
    If word has only one possible function:
        fnDef[i] = function[i][0]
    Else:
        max_score = 0
        best_fn = null

        If i > 1:  // has previous word
            For each candidate function cf:
                score = combinationValue(fnDef[i-1], cf)
                If score > max_score:
                    max_score = score
                    best_fn = cf

        If i < n:  // has next word
            For each candidate function cf:
                For each next function nf:
                    score = combinationValue(cf, nf)
                    If score > max_score:
                        max_score = score
                        best_fn = cf

        fnDef[i] = best_fn
```

### Algorithm 2: Frame Network Expansion

```
Function expandFrame(idFrame, baseFrame, level, value):
    If value < 0:
        Return []

    results = []
    relations = querySuperFrames(idFrame)

    For each relation in relations:
        weight = getRelationWeight(relation.type, value)

        If weight > 0:
            results.append([
                relation.frameEntry,
                weight,
                level
            ])

            If level > 1:
                // Recurse with reduced value and level
                children = expandFrame(
                    relation.idFrame,
                    baseFrame,
                    level - 1,
                    value - 0.4
                )
                results.extend(children)

    Return results
```

### Algorithm 3: Spreading Activation

```
Function spreadActivation(windows):
    For each window in windows:
        For each word in window:
            For each frame in word.frames:
                total_energy = frame.initial_energy

                // Collect energy from related frames
                For each pool_frame in frame.pool:
                    For each contributor in pool_frame.set:
                        If contributor.word != word:
                            If contributor.same_window OR contributor.is_qualia:
                                total_energy += contributor.energy

                // Apply bonuses
                If frame has qualia:
                    total_energy += sum(qualia_values)

                If frame.is_mwe:
                    total_energy += 10

                If frame.is_mknob:
                    total_energy += 5

                frame.final_energy = total_energy
```

---

## Usage Examples

### Example 1: Basic Disambiguation

**Input:** "O menino construiu uma casa"

**Output:**
```json
{
  "result": {
    "1": {
      "menino": [
        {"frame": "(menino.n:Frame.People_by_age:1)", "value": "11.20"}
      ],
      "construiu": [
        {"frame": "(construir.v:Frame.Building:2)", "value": "8.50"}
      ],
      "casa": [
        {"frame": "(casa.n:Frame.Buildings:4)", "value": "12.70"}
      ]
    }
  }
}
```

### Example 2: Injection Mode

**Purpose:** Replace words with frame names for semantic representation

**Input:** "O jogador chutou a bola"

**Output:**
```json
{
  "original": "o jogador chutou a bola",
  "injected": "Competition Cause_motion Sports_equipment",
  "translated": "Competition Cause_motion Sports_equipment"
}
```

### Example 3: GregNet Mode

**Purpose:** Extract lemmas and frames for knowledge graph

**Output:**
```json
{
  "words": ["jogador", "bola"],
  "lemmas": [
    {"idLemma": 456, "lemma": "jogador.n"},
    {"idLemma": 789, "lemma": "bola.n"}
  ],
  "frames": [
    {"idLU": 123, "idFrame": 45},
    {"idLU": 456, "idFrame": 78}
  ]
}
```

---

## Implementation Status

### ✅ Completed - Laravel 12 Implementation

The Daisy disambiguation pipeline has been successfully migrated to Laravel 12 with modern architecture:

#### Phase 1: Infrastructure ✅
- ✅ Set up Trankit REST client (replaced UDPipe)
- ✅ FrameNet database integration (using existing schema)
- ✅ Grid function configuration via `config/daisy.php`
- ✅ Laravel Data DTOs for type safety

#### Phase 2: Core Services ✅
- ✅ `TrankitService` - UD parsing service
- ✅ `GridService` - Grid window creation service
- ✅ `LexicalUnitService` - Lexical unit matching service
- ✅ `SemanticNetworkService` - Frame network builder service

#### Phase 3: Algorithms ✅
- ✅ `SpreadingActivationService` - Spreading activation algorithm
- ✅ `WinnerSelectionService` - Winner selection with formatting
- ✅ Qualia processing integration
- ✅ Equivalence processing support

#### Phase 4: API & Controllers ✅
- ✅ `DaisyController` with route attributes
- ✅ Input validation via `DaisyInputData`
- ✅ Structured output via `DaisyOutputData`
- ✅ Error handling and logging
- ✅ Blade view integration

#### Phase 5: Features ✅
- ✅ Standard disambiguation mode
- ✅ GregNet mode for knowledge graphs
- ✅ Graph visualization data generation
- ✅ Multi-language support (Portuguese/English)

### 🔄 In Progress / Future Enhancements

#### Optimization
- [ ] Cache lemma queries (Redis/Memcached)
- [ ] Optimize frame relation queries
- [ ] Implement batch processing for multiple sentences
- [ ] Add parallel processing for large texts
- [ ] Queue support for async processing

#### Testing
- [ ] Unit tests for each service
- [ ] Feature tests for full pipeline
- [ ] Benchmark against reference implementation
- [ ] Validate output formats
- [ ] Test coverage for edge cases

#### Monitoring & Analytics
- [ ] Performance metrics collection
- [ ] Query performance tracking
- [ ] Success/error rate monitoring
- [ ] Usage analytics

---

## Performance Considerations

### Caching Strategy

**Cache these queries:**
1. Lemma → LU mappings (TTL: 24h)
2. Frame relations (TTL: 24h)
3. Qualia relations (TTL: 24h)
4. Grid function mappings (TTL: permanent)

### Query Optimization

1. **Use prepared statements** for all parameterized queries
2. **Index these columns:**
   - View_LU: idLanguage, idLemma, name
   - Frame: idFrame, idEntity
   - EntityRelation: idEntity1, idEntity2, idRelationType
3. **Batch queries** when possible (e.g., all lemmas at once)

### Memory Management

- Process sentence in windows (don't load entire network)
- Limit frame expansion depth
- Clear pool objects after processing window

---

## References

- UDPipe: https://ufal.mff.cuni.ac.uk/udpipe
- FrameNet: https://framenet.icsi.berkeley.edu/
- Universal Dependencies: https://universaldependencies.org/
- GRID Algorithm: See published papers from FrameNet Brasil team

---

## Contact & Support

For questions about this documentation or the Daisy algorithm:
- FrameNet Brasil: http://www.ufjf.br/framenetbr-eng/
- GitHub: https://github.com/FrameNetBrasil

