<x-layout::index>
    <div class="app-layout no-tools">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','UD Tree']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="page-content">
                @if(isset($sentenceText))
                    <div class="w-full" style="margin-bottom: 20px;">
                        <h3>Sentence #{{ $idDocumentSentence }}</h3>
                        <p style="font-size: 1.1em; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                            {{ $sentenceText }}
                        </p>
                        <div class="ui buttons" style="margin-top: 10px;">
                            <button class="ui button" onclick="window.treeInstance.setOrientation('horizontal')">Horizontal</button>
                            <button class="ui button" onclick="window.treeInstance.setOrientation('vertical')">Vertical</button>
                            <button class="ui button" onclick="window.treeInstance.setOrientation('arc')">Arc</button>
                        </div>
                    </div>
                @endif

                {{-- UD Tree Component --}}
                <div
                    class="w-full"
                    id="tree-container"
                    x-data="udTreeInstance"
                    x-init="init()"
                >
                </div>
            </div>

        </main>
        <x-layout::footer></x-layout::footer>
    </div>

    <script>
        // Initialize tree data before Alpine starts
        window.treeData = {!! $treeData ?? '{}' !!};

        // Create tree instance that Alpine can use
        document.addEventListener('alpine:init', () => {
            Alpine.data('udTreeInstance', () => {
                const config = {
                    data: window.treeData,
                    orientation: 'horizontal',
                    width: 1200,
                    nodeSize: { dx: 10, dy: null },
                    fontSize: '12px'
                };

                const instance = udTreeComponent(config);
                window.treeInstance = instance; // Make available for buttons
                return instance;
            });
        });
    </script>
</x-layout::index>
