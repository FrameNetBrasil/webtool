<div class="ui large modal" id="grapherFERelationsModal">
    <i class="close icon"></i>
    <div class="header">
        Frame Element Relations
    </div>
    <div class="scrolling content" style="height: 50vh;">
        <div id="feRelationsGraphApp" x-data="grapher({})" style="height: 100%;">
            <div id="feRelationsGraph" class="wt-layout-grapher"></div>
        </div>
    </div>
    <div class="actions">
        <div class="ui cancel button">Close</div>
    </div>
</div>

<style>
#grapherFERelationsModal {
    width: 90vw !important;
}
#grapherFERelationsModal .content {
    height: 50vh !important;
}
#feRelationsGraph {
    height: 100%;
    min-height: 400px;
}
/* Ensure JointJS tooltips appear above modal */
.joint-tools {
    z-index: 1001 !important;
}
</style>
