function luComponent(idDocumentSentence) {
    return {
        idDocumentSentence: null,

        init() {
            this.idDocumentSentence = idDocumentSentence;
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
                idLU,
                wordList: this.getWordData()
            };
            htmx.ajax('POST', '/annotation/fe/createAS', {target:'.annotation-workarea', swap:'innerHTML',values: values });
        }
    };
}
