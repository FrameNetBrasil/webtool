function annotationSetComponent(idAnnotationSet, token, corpusAnnotationType) {
    return {
        idAnnotationSet: null,
        selectionRaw: null,
        selectionNI: null,
        corpusAnnotationType: "fe",
        token: "",

        init() {
            this.idAnnotationSet = idAnnotationSet;
            this.token = token;
            this.corpusAnnotationType = corpusAnnotationType;
        },

        get selection() {
            console.error("called selection");
            let type = "", id = "", start = 0, end = 0, startNode, endNode;
            if (this.selectionRaw) {
                let { anchorNode, anchorOffset, focusNode, focusOffset } = this.selectionRaw;
                console.log("====");
                console.log(anchorNode, anchorOffset, focusNode, focusOffset );
                if (anchorNode.nodeType === Node.TEXT_NODE) {
                    startNode = anchorNode?.parentNode || null;
                } else if (anchorNode.nodeType === Node.ELEMENT_NODE) {
                    startNode = anchorNode;
                }
                if (focusNode.nodeType === Node.TEXT_NODE) {
                    endNode = focusNode?.parentNode || null;
                } else if (focusNode.nodeType === Node.ELEMENT_NODE) {
                    endNode = focusNode;
                }
                console.log("==== startNode", startNode);
                console.log("==== endNode", endNode);
                console.log("==== dataset", startNode.dataset);
                if ((startNode !== null) && (endNode !== null)) {
                    if (startNode.dataset.type === "word") {
                        this.selectionNI = false;
                        type = "word";
                        if (startNode.dataset.startchar) {
                            start = startNode.dataset.startchar;
                        }
                        if (endNode.dataset.endchar) {
                            end = endNode.dataset.endchar;
                            if (endNode.classList.contains('colSpace')) {
                                --end;
                            }
                        }
                    }
                }
                console.log(type,id,start,end);
            }
            console.log("==== selectionNI", (this.selectionNI ? 'yes' : 'no'));
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

        onLabelAnnotate(idEntity) {
            //console.log(this.selection);
            let selection = this.selection;
            let values = {
                idAnnotationSet: this.idAnnotationSet,
                corpusAnnotationType: this.corpusAnnotationType,
                token: this.token,
                idEntity,
                selection
            };
            htmx.ajax("POST", `/annotation/corpus/object`, {
                target: ".annotationSetColumns",
                swap: "innerHTML",
                values: values
            });
        },

        onLabelDelete(idEntity) {
            let values = {
                idAnnotationSet: this.idAnnotationSet,
                corpusAnnotationType: this.corpusAnnotationType,
                token: this.token,
                idEntity
            };
            htmx.ajax("DELETE", `/annotation/corpus/object`, {
                target: ".annotationSetColumns",
                swap: "innerHTML",
                values: values
            });
        },

        onLOMEAccepted(idAnnotationSet) {
            let values = {
                idAnnotationSet,
                corpusAnnotationType: this.corpusAnnotationType,
                token: this.token
            };
            htmx.ajax("POST", `/annotation/corpus/lome/accepted`, {
                target: "#statusField",
                swap: "innerHTML",
                values: values
            });
        }

    };
}
