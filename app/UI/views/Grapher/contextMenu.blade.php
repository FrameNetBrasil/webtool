<div
    id="grapherContextMenu"
    x-data="contextMenu()"
    x-show="visible"
    x-cloak
    @@mouseenter="handleMouseEnter()"
    @@mouseleave="handleMouseLeave()"
    @@context-menu-action.window="$dispatch('grapher-context-action', $event.detail)"
    :class="{'context-menu-wrapper': true, 'visible': positioned}"
    :style="'left: ' + x + 'px; top: ' + y + 'px;'"
>
    <div class="context-menu">
        <div class="context-menu-header" x-show="nodeData">
            <span x-text="nodeData?.name"></span>
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" @@click="handleAction('view-report')">
            <i class="file alternate outline icon"></i>
            <span>View Report</span>
        </div>
        <div class="context-menu-item" @@click="handleAction('expand')">
            <i class="sitemap icon"></i>
            <span>Expand Relations</span>
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item context-menu-item--danger" @@click="handleAction('remove')">
            <i class="trash alternate outline icon"></i>
            <span>Remove from Graph</span>
        </div>
    </div>
</div>
