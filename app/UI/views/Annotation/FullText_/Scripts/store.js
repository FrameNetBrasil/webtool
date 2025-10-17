document.addEventListener("alpine:init", () => {
    window.ftStore = Alpine.store("ftStore", {
        _token: "",
        idAnnotationSet: 0,
        asData: [],
        selection: {
            type: "",
            id: "",
            start: 0,
            end: 0
        },
        init() {
        },
        config() {
        },
        setSelection(type,id,start,end) {
            this.selection = {
                type,
                id,
                start,
                end
            };
        },
        setASData(asData) {
            this.asData = asData;
            console.log(asData);
        },
        async updateASData() {
            this.selection = {type:"",id:"",start:0,end:0};
            await annotationFullText.api.loadASData(this.idAnnotationSet);
        },
        async annotate(idEntity, layerType) {
            try {
                console.log(idEntity);
                console.log(this.selection, this.idAnnotationSet);
                await annotationFullText.api.annotate({
                    idAnnotationSet: this.idAnnotationSet,
                    idEntity: idEntity,
                    selection: this.selection,
                    layerType: layerType,
                });
            } catch(e) {
                console.log(e);
            }
        },
        async deleteLabel(idEntity) {
            try {
                await annotationFullText.api.deleteLabel({
                    idAnnotationSet: this.idAnnotationSet,
                    idEntity: idEntity
                });
            } catch(e) {
                console.log(e);
            }
        }
    });

});
