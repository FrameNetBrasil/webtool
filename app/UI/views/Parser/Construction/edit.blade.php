<div class="ui card form-card w-full p-1">
    <div class="content">
        <div class="header">Edit Construction: {{ $construction->name }}</div>
        <div class="meta">
            Grammar: {{ $grammar->name }} ({{ $grammar->language }}) |
            Type: {{ ucfirst($construction->constructionType) }} |
            Priority: {{ $construction->priority }}
        </div>
    </div>

    <div class="content">
        <div class="ui top attached tabular menu">
            <a class="item active" data-tab="basic">Basic</a>
            <a class="item" data-tab="pattern">Pattern</a>
            <a class="item" data-tab="ce-labels">CE Labels</a>
            <a class="item" data-tab="constraints">Constraints</a>
            <a class="item" data-tab="mwe">MWE</a>
            <a class="item" data-tab="examples">Examples</a>
            <a class="item" data-tab="graph-pattern">Pattern Graph</a>
            <a class="item" data-tab="graph-hierarchy">Hierarchy</a>
            <a class="item" data-tab="graph-priority">Priority</a>
        </div>

        <div class="ui bottom attached tab segment active" data-tab="basic"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/basic"
             hx-trigger="load"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="pattern"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/pattern"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="ce-labels"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/ce-labels"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="constraints"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/constraints"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="mwe"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/mwe"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="examples"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/tab/examples"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="graph-pattern"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/graph/pattern"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="graph-hierarchy"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/graph/hierarchy"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>

        <div class="ui bottom attached tab segment" data-tab="graph-priority"
             hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/graph/priority"
             hx-trigger="intersect once"
             hx-swap="innerHTML">
            <div class="ui active centered inline loader"></div>
        </div>
    </div>

    <div class="extra content">
        <button
            class="ui red button"
            hx-delete="/parser/v4/construction/{{ $construction->idConstruction }}"
            hx-confirm="Are you sure you want to delete this construction?"
        >
            Delete Construction
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.tabular.menu .item').tab();
});
</script>
