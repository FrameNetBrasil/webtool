<script>
    // Initialize Alpine component following frameGraph.blade.php pattern
    if (window.Alpine && document.getElementById('feRelationsGraphApp')) {
        const container = document.getElementById('feRelationsGraphApp');

        if (!container.hasAttribute('x-data') || !Alpine.$data(container).initialized) {
            // First time - initialize
            container.setAttribute('x-data', 'grapher({nodes: @json($graph['nodes']), links: @json($graph['links'])})');
            Alpine.initTree(container);

            const component = Alpine.$data(container);
            if (component && component.init) {
                component.init();
            }
        } else {
            // Already initialized - update data
            const component = Alpine.$data(container);
            if (component && component.updateData) {
                component.updateData(@json($graph['nodes']), @json($graph['links']));
            }
        }
    }
</script>
