{{-- Custom Search Component --}}
@props([
    'name' => 'search',
    'displayName' => null, // Name for the display field (optional)
    'placeholder' => 'Search...',
    'searchUrl' => '/__api/search',
    'searchFields' => ['q'], // Array of search field names
    'displayField' => 'name', // Field to display in readonly input OR function name
    'searchField' => null, // Field to use for search pre-population (defaults to displayField if null)
    'searchValue' => null, // Actual value to use for search (when different from displayValue)
    'displayFormatter' => null, // Function name for custom result display formatting
    'valueField' => 'id', // Field to store in hidden input
    'value' => '',
    'displayValue' => '',
    'modalTitle' => 'Search',
    'required' => false,
    'onChange' => null, // Function name as string
    'resolveUrl' => null // URL to resolve display value from value
])

<div x-data="searchComponent({
    name: '{{ $name }}',
    searchUrl: '{{ $searchUrl }}',
    searchFields: {!! Js::from($searchFields) !!},
    displayField: '{{ $displayField }}',
    searchField: '{{ $searchField ?? $displayField }}',
    displayFormatter: '{{ $displayFormatter }}',
    valueField: '{{ $valueField }}',
    initialValue: '{{ $value }}',
    initialDisplayValue: '{{ $displayValue }}',
    initialSearchValue: '{{ $searchValue }}',
    onChange: '{{ $onChange }}',
    resolveUrl: '{{ $resolveUrl }}'
})" class="search-component">

    {{-- Hidden field to store the selected value --}}
    <input type="hidden"
           name="{{ $name }}"
           x-model="selectedValue"
        {{ $required ? 'required' : '' }}>

    @if($displayName)
        {{-- Hidden field to store the display value for form repopulation --}}
        <input type="hidden"
               name="{{ $displayName }}"
               x-model="displayValue">
    @endif

    {{-- Display field (readonly) --}}
    <div class="ui medium fluid left icon input">
        <input type="text"
               x-model="rawDisplayValue"
               placeholder="{{ $placeholder }}"
               readonly
               @click="openModal()"
               style="cursor: pointer;"
               :style="displayValue && displayValue !== rawDisplayValue ? 'color: transparent; cursor: pointer;' : 'cursor: pointer;'">
        
        {{-- HTML overlay for formatted display (positioned over input text area only) --}}
        <div x-show="displayValue && displayValue !== rawDisplayValue"
             x-html="displayValue"
             @click="openModal()"
             style="position: absolute; top: 1px; left: 2.67142857em; right: 2.67142857em; bottom: 1px; cursor: pointer; padding: 0.67857143em 0; line-height: 1.21428571em; background: transparent; pointer-events: none; display: flex; align-items: center; z-index: 1;">
        </div>
        
        <i class="stream icon" style="cursor: pointer;" @click="openModal()"></i>
    </div>

    {{-- Modal Background --}}
    <div x-show="isModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="ui dimmer modals page active"
         @click="closeModal()"
         style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1001; background-color: rgba(0, 0, 0, 0.85);">
    </div>

    {{-- Modal Window --}}
    <div x-show="isModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="ui modal active"
         style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1002; height: 50vh; width: 600px; max-width: 90vw; display: flex; flex-direction: column; background: white; border-radius: 0.28571429rem; box-shadow: 0 0 0 1px rgba(34,36,38,.15), 0 1px 3px 0 rgba(34,36,38,.15);"
         @click.stop>
        <div class="header" style="flex-shrink: 0; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(34,36,38,.15);">
            <i class="search icon"></i>
            {{ $modalTitle }}
        </div>

        <div class="content" style="flex: 1; overflow-y: auto; padding: 1rem;">
            {{-- Search Form --}}
            <form class="ui form" @submit.prevent="performSearch()">
                <template x-for="(fieldValue, fieldName) in searchParams" :key="fieldName">
                    <div class="field">
                        <div class="ui left icon input">
                            <input type="search"
                                   x-model="searchParams[fieldName]"
                                   :placeholder="'Enter ' + fieldName + '...'"
                                   @input.debounce.300ms="performSearch()">
                            <i class="search icon"></i>
                        </div>
                    </div>
                </template>
            </form>

            {{-- Results/Loading Container with fixed height --}}
            <div style="position: relative; height: 300px; margin-top: 1rem; overflow-y: auto;">
                {{-- Loading State --}}
                <div x-show="isLoading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10;">
                    <div class="ui active centered inline loader"></div>
                </div>

                {{-- Results Container --}}
                <div x-show="!isLoading && searchResults.length > 0" class="ui relaxed divided list" style="height: 100%;">
                    <template x-for="(result, index) in searchResults" :key="index">
                        <div class="item" style="cursor: pointer; padding: 10px;"
                             @click="selectResult(result)"
                             @mouseenter="$el.style.backgroundColor = '#f8f9fa'"
                             @mouseleave="$el.style.backgroundColor = 'transparent'">
                            <div class="content">
                                <div class="header" x-html="formatResultDisplay(result)"></div>
                                <div class="description" x-text="result.description || ''"></div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- No Results Message --}}
                <div x-show="!isLoading && searchPerformed && searchResults.length === 0"
                     style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%;">
                    <div class="ui message">
                        <div class="header">No results found</div>
                        <p>Try adjusting your search criteria.</p>
                    </div>
                </div>
            </div>

            {{-- Error Message --}}
            <div x-show="errorMessage" class="ui error message">
                <div class="header">Error</div>
                <p x-text="errorMessage"></p>
            </div>
        </div>

        <div class="actions" style="flex-shrink: 0; padding: 1rem 1.5rem; border-top: 1px solid rgba(34,36,38,.15); text-align: right;">
            <button type="button" class="ui button" @click="closeModal()">Cancel</button>
            <button type="button" class="ui red button" @click="clearSelection()">Clear</button>
        </div>
    </div>
</div>
