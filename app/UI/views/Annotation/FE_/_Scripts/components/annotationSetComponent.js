function annotationSetComponent(idAnnotationSet, token) {
    return {
        idAnnotationSet: null,
        selectionRaw: null,
        selectionNI: null,
        token: "",

        init() {
            this.idAnnotationSet = idAnnotationSet;
            this.token = token;
        },

        get selection() {
            let type = "", id = "", start = 0, end = 0;
            if (this.selectionRaw) {
                let { anchorNode, anchorOffset, focusNode, focusOffset } = this.selectionRaw;
                var startNode = anchorNode?.parentNode || null;
                var endNode = focusNode?.parentNode || null;
                if ((startNode !== null) && (endNode !== null)) {
                    if (startNode.dataset.type === "word") {
                        type = "word";
                        if (startNode.dataset.startchar) {
                            start = startNode.dataset.startchar;
                        }
                        if (endNode.dataset.endchar) {
                            end = endNode.dataset.endchar;
                        }
                    }
                }
            }
            if (this.selectionNI) {
                type = "ni";
                id = this.selectionNI.dataset.id;
                start = end = 0;
            }
            return {
                type,
                id,
                start,
                end
            };
        },

        onSelectNI(e) {
            this.selectionNI = e;
            let range = new Range();
            range.setStart(e, 0);
            range.setEnd(e, 1);
            document.getSelection().removeAllRanges();
            document.getSelection().addRange(range);
        },

        onLabelAnnotate(idFrameElement) {
            console.log(this.selection);
            let values = {
                idAnnotationSet: this.idAnnotationSet,
                token: this.token,
                idFrameElement,
                selection: this.selection
            };
            htmx.ajax("POST", "/annotation/fe/annotate", {
                target: ".annotationSetColumns",
                swap: "innerHTML",
                values: values
            });
        },

        onLabelDelete(idFrameElement) {
            let values = {
                idAnnotationSet: this.idAnnotationSet,
                token: this.token,
                idFrameElement
            };
            htmx.ajax("DELETE", "/annotation/fe/frameElement", {
                target: ".annotationSetColumns",
                swap: "innerHTML",
                values: values
            });
        }
    };
}
