<script type="text/javascript">
    let annotationFE = {
        selection: {
            type: "",
            id: "",
            start: 0,
            end: 0
        }
    };
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
                    annotationFE.selection.type = "ni";
                    annotationFE.selection.id = startNode.dataset.id;
                }
                if (startNode.dataset.type === "word") {
                    annotationFE.selection.type = "word";
                    if (startNode.dataset.startchar) {
                        annotationFE.selection.start = startNode.dataset.startchar;
                    }
                    if (endNode.dataset.endchar) {
                        annotationFE.selection.end = endNode.dataset.endchar;
                    }
                }
            }
        };
    });

</script>
