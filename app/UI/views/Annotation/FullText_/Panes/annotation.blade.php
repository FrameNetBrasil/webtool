<script type="text/javascript">
    @include("Annotation.FullText.Scripts.api")
    @include("Annotation.FullText.Scripts.store")

    $(function() {
        document.onselectionchange = () => {
            let selection = document.getSelection();
            let { anchorNode, anchorOffset, focusNode, focusOffset } = selection;
            var startNode = anchorNode?.parentNode || null;
            var endNode = focusNode?.parentNode || null;
            if ((startNode !== null) && (endNode !== null)) {
                if (startNode.dataset.type === "ni") {
                    let range = new Range();
                    range.setStart(startNode, 0);
                    range.setEnd(startNode, 1);
                    document.getSelection().removeAllRanges();
                    document.getSelection().addRange(range);
                    Alpine.store('ftStore').selection.type = "ni";
                    Alpine.store('ftStore').selection.id = startNode.dataset.id;
                }
                if (startNode.dataset.type === "word") {
                    Alpine.store('ftStore').selection.type = "word";
                    if (startNode.dataset.startchar) {
                        Alpine.store('ftStore').selection.start = startNode.dataset.startchar;
                    }
                    if (endNode.dataset.endchar) {
                        Alpine.store('ftStore').selection.end = endNode.dataset.endchar;
                    }
                }
            }
        };
    });

</script>
