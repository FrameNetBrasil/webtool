{{--Base snippet for graph rendering --}}
{{--Goal: graph rendering using grapherComponent; used with baseGraph.blade.php --}}
<script>
    // Initialize Alpine component on the parent container
    if (window.Alpine && document.getElementById('grapherApp')) {
        Alpine.data('grapherInstance', () => window.grapherComponent({
            nodes: {{ Js::from($graph['nodes']) }},
            links: {{ Js::from($graph['links']) }}
        }));

        // Apply Alpine to the container if not already applied
        const container = document.getElementById('grapherApp');
        if (!container.hasAttribute('x-data')) {
            container.setAttribute('x-data', 'grapherInstance');
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
                component.updateData({{ Js::from($graph['nodes']) }}, {{ Js::from($graph['links']) }});
            }
        }
    }
</script>
