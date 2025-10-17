<div class="ui container page-report">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <div class="page-title-section">
                    <div class="page-title">
                        <x-ui::element.frame name="{{$frame->name}}"></x-ui::element.frame>
                    </div>
                    <div
                        class="page-subtitle">{!! str_replace('ex>','code>',nl2br($frame->description)) !!}</div>
                </div>
                @if($isHtmx)
                    <div class="page-actions">
                        <button
                            class="ui basic left labeled icon button"
                            @click="$.tab('change tab','browse')"
                        >
                            <i class="left arrow icon"></i>
                            Back to Frames
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="page-content">
        {{-- Frame Metadata Section --}}
        <div class="frame-metadata-section">
            @include('Frame.Report.partials.frame-metadata')
        </div>

        {{-- Stats Section --}}
        <div class="stats-section mb-8">
            @include('Frame.Report.partials.stats-card')
        </div>

        {{-- Frame Elements Section --}}
        <div class="frame-elements-section mb-8">
            @include('Frame.Report.partials.frame-elements-cards')
        </div>

        {{-- Frame Relations Section --}}
        <div class="frame-relations-section mb-8">
            <div class="section-header">
                <h2 class="ui header section-title" id="frame-relations">
                    <a href="#frame-relations">Frame-Frame Relations</a>
                </h2>
                <button class="ui button basic icon section-toggle"
                        onclick="toggleSection('relations-content')"
                        aria-expanded="true">
                    <i class="chevron up icon"></i>
                </button>
            </div>
            <div class="section-content" id="relations-content">
                @include('Frame.Report.partials.relations-card')
            </div>
        </div>

        {{-- Lexical Units Section --}}
        <div class="lexical-units-section mb-8">
            <div class="section-header">
                <h2 class="ui header section-title" id="lexical-units">
                    <a href="#lexical-units">Lexical Units</a>
                </h2>
                <button class="ui button basic icon section-toggle"
                        onclick="toggleSection('lexical-units-content')"
                        aria-expanded="true">
                    <i class="chevron up icon"></i>
                </button>
            </div>
            <div class="section-content" id="lexical-units-content">
                @include('Frame.Report.partials.lexical-units-card')
            </div>
        </div>

        {{-- Visual Units Section --}}
        @include('Frame.Report.partials.visual-units-section')

    </div>
</div>
<script>
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        let button;

        // Check if button is in the same parent container (new structure)
        const parent = section.parentElement;
        const headerButton = parent.querySelector(".section-header .section-toggle i");

        if (headerButton) {
            button = headerButton;
        } else {
            // Fallback for Frame Elements structure (button in previous sibling)
            button = section.previousElementSibling.querySelector(".section-toggle i");
        }

        if (section.style.display === "none") {
            section.style.display = "block";
            button.className = "chevron up icon";
        } else {
            section.style.display = "none";
            button.className = "chevron down icon";
        }
    }

    function toggleFeDetails(button) {
        const targetId = button.getAttribute("data-target");
        const target = document.querySelector(targetId);
        const icon = button.querySelector("i");

        if (target.style.display === "none") {
            target.style.display = "block";
            icon.className = "chevron up icon";
        } else {
            target.style.display = "none";
            icon.className = "chevron down icon";
        }
    }
</script>
