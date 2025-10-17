function luComponent(idDocumentSentence, corpusAnnotationType) {
    return {
        idDocumentSentence: null,
        corpusAnnotationType: "fe",

        init() {
            this.idDocumentSentence = idDocumentSentence;
            this.corpusAnnotationType = corpusAnnotationType;
        },

        getWordData() {
            return JSON.stringify(
                _.map(
                    _.filter(
                        document.querySelectorAll(".words"),
                        (x) => x.checked
                    ),
                    (y) => {
                        return {
                            startChar: y.dataset.startchar,
                            endChar: y.dataset.endchar
                        };
                    }
                )
            );
        },

        onCreateAS(idLU) {
            let values = {
                idDocumentSentence: this.idDocumentSentence,
                corpusAnnotationType: this.corpusAnnotationType,
                idLU,
                wordList: this.getWordData()
            };
            htmx.ajax('POST', '/annotation/corpus/createAS', {target:'.annotation-workarea', swap:'innerHTML',values: values });
        }
    };
}
