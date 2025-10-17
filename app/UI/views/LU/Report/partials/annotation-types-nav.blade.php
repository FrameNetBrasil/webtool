{{--
    Annotation Types Navigation - Fomantic UI Tab Component
    Uses native Fomantic UI tab behavior with HTMX content loading

    Parameters:
    - $lu: LU object with idLU
--}}

<div class="section-header">
    <h2 class="ui header section-title">
        <a>Realizations</a>
    </h2>
</div>
 <x-ui::tabs
      id="luReportTabs"
      style="secondary"
      :tabs="[
          'textual' => ['id' => 'textual', 'label' => 'Textual', 'icon' => 'text', 'url' => '/report/lu/'.$lu->idLU.'/textual'],
          'static' => ['id' => 'static', 'label' => 'Static', 'icon' => 'image', 'url' => '/report/lu/'.$lu->idLU.'/static'],
//          ['id' => 'dynamic', 'label' => 'Dynamic', 'icon' => 'video', 'url' => '/api/dynamic']
      ]"
      defaultTab="textual"
      context="annotation-types"
      sectionTitle="Annotation Reports"
      :sectionToggle="true"
  />


{{--<div class="section-header">--}}
{{--    <h1 class="ui header section-title" id="annotation-types">--}}
{{--        <a href="#annotation-types">Annotation Reports</a>--}}
{{--    </h1>--}}
{{--    <button class="ui button basic icon section-toggle" --}}
{{--            onclick="toggleSection('annotation-types-content')" --}}
{{--            aria-expanded="true">--}}
{{--        <i class="chevron up icon"></i>--}}
{{--    </button>--}}
{{--</div>--}}
{{--<div class="section-content" id="annotation-types-content">--}}
{{--    --}}{{-- Tab Navigation Menu --}}
{{--    <div class="ui three item stackable tabs menu">--}}
{{--        <a class="item active" data-tab="textual">--}}
{{--            <i class="file text icon"></i>--}}
{{--            Textual--}}
{{--        </a>--}}
{{--        <a class="item" data-tab="static">--}}
{{--            <i class="image icon"></i>--}}
{{--            Static--}}
{{--        </a>--}}
{{--        <a class="item" data-tab="dynamic">--}}
{{--            <i class="video icon"></i>--}}
{{--            Dynamic--}}
{{--        </a>--}}
{{--    </div>--}}

{{--    --}}{{-- Tab Content Areas --}}
{{--    <div class="ui tab active" data-tab="textual">--}}
{{--        <div class="tab-loading-indicator" style="display: none;">--}}
{{--            <div class="ui active centered inline loader"></div>--}}
{{--            <p>Loading textual report...</p>--}}
{{--        </div>--}}
{{--        <div class="tab-content" id="textual-content">--}}
{{--            --}}{{-- Textual content will be loaded here --}}
{{--        </div>--}}
{{--    </div>--}}

{{--    <div class="ui tab" data-tab="static">--}}
{{--        <div class="tab-loading-indicator" style="display: none;">--}}
{{--            <div class="ui active centered inline loader"></div>--}}
{{--            <p>Loading static report...</p>--}}
{{--        </div>--}}
{{--        <div class="tab-content" id="static-content">--}}
{{--            --}}{{-- Static content will be loaded here --}}
{{--        </div>--}}
{{--    </div>--}}

{{--    <div class="ui tab" data-tab="dynamic">--}}
{{--        <div class="tab-loading-indicator" style="display: none;">--}}
{{--            <div class="ui active centered inline loader"></div>--}}
{{--            <p>Loading dynamic report...</p>--}}
{{--        </div>--}}
{{--        <div class="tab-content" id="dynamic-content">--}}
{{--            --}}{{-- Dynamic content will be loaded here --}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
