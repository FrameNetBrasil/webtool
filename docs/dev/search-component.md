# Search Component Documentation

## Overview

The search component is a reusable, modal-based search system built with **AlpineJS** and **Fomantic-UI**. It provides an autocomplete-like interface where users can search for entities (lemmas, lexical units, frames, etc.) and select them via a modal dialog.

---

## Architecture

### Component Hierarchy

1. **`app/UI/components/search/lemma.blade.php`** - Specific implementation for lemma search
2. **`app/UI/components/search/base.blade.php`** - Generic base component template
3. **`resources/js/components/searchComponent.js`** - AlpineJS component logic (JavaScript)

---

## Component Files

### 1. `lemma.blade.php` (Specific Implementation)

**Purpose**: Pre-configured wrapper for searching lemmas specifically.

**Key Features**:
- Sets lemma-specific defaults (search URL, value field, display field)
- Includes commented-out custom display formatter example
- Passes configuration to base component

**Configuration**:
```php
search-url="/lemma/listForSearch"     // API endpoint for lemma search
display-field="name"                   // Field to display from results
value-field="idLemma"                  // Field to use as value (primary key)
placeholder="Search Lemma"             // Input placeholder
modal-title="Search Lemma"             // Modal header title
```

**Custom Formatter Example** (commented out):
```javascript
function displayFormaterLUSearch(lu) {
    return `<div class="result"><span class="color_frame">${lu.frameName}</span>.${lu.name}</span></div>`;
}
```

---

### 2. `base.blade.php` (Generic Template)

**Purpose**: Reusable base component that renders the UI and wires up AlpineJS.

#### Configuration Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | string | 'search' | Form field name for hidden input |
| `displayName` | string | null | Optional separate field name for display value |
| `placeholder` | string | 'Search...' | Input placeholder text |
| `searchUrl` | string | '/__api/search' | API endpoint for search requests |
| `searchFields` | array | `['q']` | Array of search parameter names |
| `displayField` | string | 'name' | Field from result object to display in UI |
| `searchField` | string | null | Field from result object to use for search pre-population (defaults to `displayField`) |
| `displayFormatter` | string | null | Global function name for custom formatting |
| `valueField` | string | 'id' | Field from result object to use as value |
| `value` | string | '' | Initial selected value (ID) |
| `displayValue` | string | '' | Initial display text |
| `modalTitle` | string | 'Search' | Modal header title |
| `required` | bool | false | HTML5 required attribute |
| `onChange` | string | null | Global function name to call on selection |
| `resolveUrl` | string | null | URL to fetch display value from value ID |

#### UI Structure

**1. Hidden Inputs** (lines 32-43):
- Primary hidden input stores `selectedValue` (the ID)
- Optional second hidden input stores `displayValue` (for form repopulation)

**2. Display Field** (lines 45-63):
- Readonly text input showing selected value
- **Dual Display System**:
  - `rawDisplayValue`: Clean text version (used in input)
  - `displayValue`: HTML-formatted version (overlaid div)
- When `displayValue` contains HTML, the text becomes transparent and HTML overlay shows instead
- Stream icon triggers modal
- Everything is clickable to open modal

**3. Modal Structure** (lines 65-153):

**Modal Dimmer** (lines 66-76):
- Full-screen semi-transparent overlay
- Clicking background closes modal
- Alpine transitions for smooth appearance

**Modal Window** (lines 79-153):
- Fixed positioning, centered
- 600px width, 50vh height
- Three sections: header, content, actions

**Modal Content**:
- **Search Form** (lines 96-108):
  - Dynamically generates input fields based on `searchFields`
  - 300ms debounced search on input
  - Uses `@submit.prevent` to prevent form submission

- **Results Container** (lines 111-140):
  - Fixed 300px height with scrolling
  - Three states: loading, results, no results
  - Loading spinner (centered)
  - Results list (clickable items with hover effect)
  - No results message

- **Actions** (lines 149-152):
  - Cancel button (closes modal)
  - Clear button (clears selection without closing)

---

### 3. `searchComponent.js` (AlpineJS Logic)

**Purpose**: Manages all component state and behavior.

**Location**: `resources/js/components/searchComponent.js`

#### Configuration (lines 3-11)
Stores props passed from Blade template:
```javascript
name, searchUrl, displayField, searchField, displayFormatter,
valueField, onChange, resolveUrl
```

#### State Variables (lines 13-22)

| Variable | Type | Purpose |
|----------|------|---------|
| `isModalOpen` | boolean | Modal visibility |
| `isLoading` | boolean | Loading spinner visibility |
| `searchPerformed` | boolean | Whether search has been attempted |
| `selectedValue` | string | Selected item ID |
| `displayValue` | string | Formatted display text (HTML) |
| `rawDisplayValue` | string | Clean text version for display |
| `searchValue` | string | Value to use for search pre-population |
| `searchResults` | array | Search results from API |
| `errorMessage` | string | Error message to display |
| `searchParams` | object | Current search field values |

#### Key Methods

**1. `formatResultDisplay(result)`** (lines 26-31)
- Checks if custom formatter function exists globally
- Falls back to simple field display
- Returns HTML string for result display

**2. `resolveDisplayValue(value)`** (lines 35-64)
- **Async** method to fetch display text from value ID
- Used when form has value but no display text (e.g., validation errors)
- Makes GET request to `resolveUrl` with value parameter
- Applies formatter if provided
- Returns object: `{ formatted: '...', raw: '...', search: '...' }`
- Handles errors gracefully with fallback

**3. `init()`** (lines 66-95)
- **Lifecycle hook** - runs when component initializes
- Initializes `searchParams` from `searchFields` config
- Resolves display value if needed (value without display text)
- Also resolves search value when resolving display value
- Sets up ESC key listener to close modal
- Ensures modal starts closed

**4. `openModal()`** (lines 96-121)
- Opens modal, adds body class
- Pre-populates first search field with `searchValue` (falls back to `rawDisplayValue`)
- Performs automatic search if value exists
- Focuses first search input
- Uses `$nextTick` for DOM-dependent operations

**5. `closeModal()`** (lines 118-122)
- Closes modal, removes body class
- Resets search state

**6. `resetSearch()`** (lines 124-132)
- Clears all search parameters
- Clears results and error messages
- Resets `searchPerformed` flag

**7. `performSearch()`** (lines 134-173)
- **Async** method triggered by input debounce
- Validates that at least one search term exists
- Builds URL with non-empty search parameters
- Fetches results from API
- Expects JSON response: `{ results: [...] }` or direct array
- Handles errors and loading states

**8. `selectResult(result)`** (lines 180-229)
- Sets `selectedValue`, `displayValue`, `rawDisplayValue`, and `searchValue`
- `searchValue` is populated from `result[searchField]` (for search pre-population)
- Closes modal
- **Dispatches multiple events**:
  - Standard `change` event on hidden input
  - Custom `search-component-change` event (on input)
  - Custom `search-component-change` event (on component container)
  - All custom events include detailed payload
- Calls global `onChange` function if provided
- Uses `$nextTick` to ensure DOM updates first

**9. `clearSelection()`** (lines 231-284)
- Clears all selection values including `searchValue`
- Resets search state
- Dispatches same events as `selectResult` but with empty values
- Includes `action: 'clear'` in event detail
- **Does NOT close modal** - allows immediate new search

---

## Data Flow

### 1. Initialization
```
lemma.blade.php → base.blade.php → searchComponent.js init()
                                  ↓
                          Initialize searchParams
                                  ↓
                    Resolve display value if needed
```

### 2. Opening Modal
```
User clicks input/icon → openModal()
                          ↓
                 Pre-populate search field
                          ↓
                  Auto-search if value exists
                          ↓
                   Focus first input
```

### 3. Searching
```
User types → Input debounce (300ms) → performSearch()
                                        ↓
                              Build URL with params
                                        ↓
                                  Fetch from API
                                        ↓
                              Update searchResults
```

### 4. Selection
```
User clicks result → selectResult(result)
                          ↓
                  Update state variables
                          ↓
                     Close modal
                          ↓
              Dispatch change events
                          ↓
                Call onChange callback
```

### 5. Form Submission
```
Form submits → Hidden input contains selectedValue (ID)
             → Optional displayName input contains displayValue
```

---

## API Contract

### Search Endpoint

**Request**:
```
GET /lemma/listForSearch?q=search+term
```

**Response** (expected):
```json
{
  "results": [
    {
      "idLemma": 123,
      "name": "lemma_name",
      "fullName": "lemma_name.V [en]",
      "form": "lemma_name",
      "description": "Optional description"
    }
  ]
}
```
Or direct array: `[{...}, {...}]`

**Note**: If using separate `displayField` and `searchField`, ensure the API returns both fields in the results.

### Resolve Endpoint (Optional)

**Request**:
```
GET /resolve/endpoint?idLemma=123
```

**Response**:
```json
{
  "result": {
    "idLemma": 123,
    "name": "lemma_name",
    "fullName": "lemma_name.V [en]",
    "form": "lemma_name"
  }
}
```
Or direct object: `{...}`

**Note**: The resolve endpoint should return both `displayField` and `searchField` when they are different.

---

## Event System

### Events Dispatched on Selection/Clear

**1. Standard Change Event**
```javascript
new Event('change', { bubbles: true })
```

**2. Custom Component Event**
```javascript
new CustomEvent('search-component-change', {
  bubbles: true,
  detail: {
    value: '123',              // Selected ID or empty
    displayValue: 'Text',      // Display text or empty
    selectedItem: {...},       // Full result object or null
    componentName: 'idLemma',  // Component name
    action: 'clear'            // Only present on clear
  }
})
```

### Listening to Events

**Alpine.js**:
```html
<div @search-component-change="handleChange($event)">
```

**Global Function**:
```javascript
function handleLemmaChange(event) {
  console.log(event.detail.value);
}
```
Then set: `onChange="handleLemmaChange"`

---

## Display Formatting

### Two-Tier Display System

**1. Simple Text Display**
- Set `displayField="name"`
- Component shows `result.name` directly

**2. Custom HTML Formatting**
- Set `displayFormatter="formatMyResult"`
- Define global function:
```javascript
function formatMyResult(result) {
  return `<span class="color_frame">${result.frameName}</span>.${result.name}`;
}
```

### Overlay Mechanism

When `displayValue` contains HTML different from `rawDisplayValue`:
- Input text becomes transparent
- Absolutely positioned div overlays with HTML content
- Both remain clickable to open modal
- `rawDisplayValue` used for search pre-population

---

## Usage Examples

### Basic Usage
```html
<x-search::base
    name="idFrame"
    search-url="/frame/listForSearch"
    display-field="name"
    value-field="idFrame"
    placeholder="Search Frame"
/>
```

### With Custom Formatter
```html
<x-search::base
    name="idLexicalUnit"
    search-url="/lu/listForSearch"
    display-field="name"
    value-field="idLU"
    display-formatter="formatLU"
/>

<script>
function formatLU(lu) {
    return `<strong>${lu.name}</strong> (${lu.pos})`;
}
</script>
```

### With Change Handler
```html
<x-search::base
    name="idLemma"
    search-url="/lemma/listForSearch"
    display-field="name"
    value-field="idLemma"
    on-change="handleLemmaSelect"
/>

<script>
function handleLemmaSelect(event) {
    console.log('Selected:', event.detail.selectedItem);
}
</script>
```

### With Resolve URL (for form repopulation)
```html
<x-search::base
    name="idLemma"
    search-url="/lemma/listForSearch"
    resolve-url="/lemma/resolve"
    display-field="name"
    value-field="idLemma"
    value="{{ old('idLemma', $lemma->idLemma ?? '') }}"
/>
```

### Creating a Specific Search Component

To create a new specific search component (e.g., for frames):

1. Create `app/UI/components/search/frame.blade.php`:
```php
@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    name="{{$id}}"
    placeholder="{{$placeholder ?? 'Search Frame'}}"
    search-url="/frame/listForSearch"
    display-field="name"
    value="{{$value ?? 0}}"
    display-value="{{ $displayValue ?? '' }}"
    value-field="idFrame"
    modal-title="{{$modalTitle ?? 'Search Frame'}}"
/>
```

2. Use in views:
```html
<x-search::frame
    id="idFrame"
    label="Select Frame"
    value="{{ old('idFrame', $entity->idFrame ?? '') }}"
/>
```

---

## Key Design Decisions

1. **AlpineJS**: Reactive state management without full framework overhead
2. **Modal Pattern**: Keeps UI clean, focuses user attention
3. **Dual Display Values**: Supports both plain text and rich HTML formatting
4. **Debounced Search**: 300ms delay reduces API calls
5. **Event Broadcasting**: Multiple event types for flexibility
6. **Resolve URL**: Handles form validation errors gracefully
7. **Auto-search on Open**: Shows context-relevant results immediately
8. **No Close on Clear**: Allows quick re-selection after clearing
9. **Component Hierarchy**: Specific wrappers → Base template → JS logic for reusability

---

## Dependencies

- **AlpineJS v3**: Core reactivity
- **Fomantic-UI**: CSS framework for styling
- **Fetch API**: HTTP requests
- **Modern Browser**: ES6+ features (async/await, template literals, etc.)

---

## Common Adjustments

### Separating Display and Search Values

Use `searchField` when you want to display one value but search by another:

```html
<x-search::base
    name="idLemma"
    search-url="/lemma/listForSearch"
    display-field="fullName"
    search-field="form"
    value-field="idLemma"
/>
```

**Example**: Display "run.V [en]" (`fullName`) but search using just "run" (`form`).

When the user reopens the modal, the search field will be pre-populated with the `form` value instead of the full `fullName`.

### Adding Multiple Search Fields

```html
<x-search::base
    name="idLemma"
    search-url="/lemma/listForSearch"
    :search-fields="['name', 'description']"
    display-field="name"
    value-field="idLemma"
/>
```

The component will generate separate input fields for each search parameter.

### Custom Result Display in Modal

Modify the `displayFormatter` function to customize how results appear in the search list:

```javascript
function formatLemmaResult(lemma) {
    return `
        <div>
            <strong>${lemma.name}</strong>
            <span class="ui label tiny">${lemma.language}</span>
        </div>
    `;
}
```

### Handling Selection Changes

Listen for the `search-component-change` event to react to selections:

```html
<div x-data="{ selectedId: '' }" @search-component-change="selectedId = $event.detail.value">
    <x-search::lemma id="idLemma" />
    <div x-show="selectedId">
        Selected ID: <span x-text="selectedId"></span>
    </div>
</div>
```

### Making Field Required

```html
<x-search::base
    name="idLemma"
    search-url="/lemma/listForSearch"
    display-field="name"
    value-field="idLemma"
    :required="true"
/>
```

---

This component provides a robust, reusable search interface suitable for entity selection throughout the Webtool 4.2 application. The separation of concerns (specific → base → logic) allows easy creation of new search components for different entity types.
