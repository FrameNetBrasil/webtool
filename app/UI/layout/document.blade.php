<x-layout::index>

    {{$slot}}
    <!-- Modal for enlarged diagram view -->
    <div class="ui modal" id="diagramModal">
        <i class="close icon"></i>
        <div class="header">
            Diagram Details
        </div>
        <div class="content diagram-modal-content">
            <!-- SVG will be cloned here -->
        </div>
        <div class="actions">
            <div class="ui cancel button">Close</div>
        </div>
    </div>

    <!-- Mermaid.js for rendering diagrams in documentation -->
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';

        mermaid.initialize({
            startOnLoad: false,
            theme: 'default',
            securityLevel: 'loose'
        });

        function renderMermaidDiagrams() {
            document.querySelectorAll('pre > code.language-mermaid').forEach((codeBlock) => {
                const pre = codeBlock.parentElement;
                const mermaidCode = codeBlock.textContent;
                const mermaidPre = document.createElement('pre');
                mermaidPre.className = 'mermaid';
                mermaidPre.textContent = mermaidCode;
                pre.replaceWith(mermaidPre);
            });

            mermaid.run({ querySelector: '.mermaid' }).then(() => {
                // After Mermaid renders, add expand buttons to each diagram
                document.querySelectorAll('.mermaid').forEach((mermaidContainer, index) => {
                    // Check if button already exists
                    if (mermaidContainer.querySelector('.diagram-expand-btn')) {
                        return;
                    }

                    // Wrap mermaid container for positioning
                    if (!mermaidContainer.parentElement.classList.contains('mermaid-wrapper')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'mermaid-wrapper';
                        mermaidContainer.parentElement.insertBefore(wrapper, mermaidContainer);
                        wrapper.appendChild(mermaidContainer);
                    }

                    // Create expand button
                    const expandBtn = document.createElement('button');
                    expandBtn.className = 'ui icon button diagram-expand-btn';
                    expandBtn.setAttribute('aria-label', 'Expand diagram');
                    expandBtn.setAttribute('title', 'View enlarged diagram');
                    expandBtn.innerHTML = '<i class="expand arrows alternate icon"></i>';

                    // Add button to wrapper
                    mermaidContainer.parentElement.appendChild(expandBtn);

                    // Add click handler
                    expandBtn.addEventListener('click', function() {
                        const svg = mermaidContainer.querySelector('svg');
                        if (svg) {
                            // Clone the SVG
                            const clonedSvg = svg.cloneNode(true);

                            // Clear previous content and add cloned SVG
                            const modalContent = document.querySelector('#diagramModal .diagram-modal-content');
                            modalContent.innerHTML = '';
                            modalContent.appendChild(clonedSvg);

                            // Show modal
                            $('#diagramModal').modal('show');
                        }
                    });
                });
            });
        }

        function addGraphvizExpandButtons() {
            document.querySelectorAll('.graphviz-diagram-wrapper').forEach((wrapper) => {
                // Check if button already exists
                if (wrapper.querySelector('.diagram-expand-btn')) {
                    return;
                }

                // Create expand button
                const expandBtn = document.createElement('button');
                expandBtn.className = 'ui icon button diagram-expand-btn';
                expandBtn.setAttribute('aria-label', 'Expand diagram');
                expandBtn.setAttribute('title', 'View enlarged diagram');
                expandBtn.innerHTML = '<i class="expand arrows alternate icon"></i>';

                // Add button to wrapper
                wrapper.appendChild(expandBtn);

                // Add click handler
                expandBtn.addEventListener('click', function() {
                    const diagramContainer = wrapper.querySelector('.graphviz-diagram');
                    const svg = diagramContainer?.querySelector('svg');

                    if (svg) {
                        // Clone the SVG
                        const clonedSvg = svg.cloneNode(true);

                        // Clear previous content and add cloned SVG
                        const modalContent = document.querySelector('#diagramModal .diagram-modal-content');
                        modalContent.innerHTML = '';
                        modalContent.appendChild(clonedSvg);

                        // Show modal
                        $('#diagramModal').modal('show');
                    }
                });
            });
        }

        function initializeDiagrams() {
            renderMermaidDiagrams();
            addGraphvizExpandButtons();
        }

        document.addEventListener('DOMContentLoaded', initializeDiagrams);
        document.body.addEventListener('htmx:afterSwap', initializeDiagrams);
    </script>

    </x-layout::index>
