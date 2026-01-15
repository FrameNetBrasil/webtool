<script>
    // Initialize Alpine component for Daisy graph
    if (window.Alpine && document.getElementById('graph')) {
        Alpine.data('daisyGrapher', () => window.grapherComponent({
            nodes: {{ Js::from($graph['nodes'] ?? []) }},
            links: {{ Js::from($graph['links'] ?? []) }}
        }));

        // Apply Alpine to the container if not already applied
        const container = document.getElementById('graph');
        if (!container.hasAttribute('x-data')) {
            container.setAttribute('x-data', 'daisyGrapher');
            Alpine.initTree(container);

            // Get the Alpine component instance and initialize it
            const component = Alpine.$data(container);
            if (component && component.init) {
                component.init();
            }
        } else {
            // If already initialized, just update the data
            const component = Alpine.$data(container);
            if (component && component.updateData) {
                component.updateData({{ Js::from($graph['nodes'] ?? []) }}, {{ Js::from($graph['links'] ?? []) }});
            }
        }
    }
</script>
