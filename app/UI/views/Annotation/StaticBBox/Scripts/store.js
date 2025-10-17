/*
Annotation Store
 */

document.addEventListener('alpine:init', () => {
    console.error('alpine init');
    window.doStore = Alpine.store('doStore', {
        dataState: 'none',
        currentObject: null,
        currentObjectState: 'none',
        newObjectState: 'none',
        showHideBoxesState: 'hide',
        dimensions: {},
        objects: [],
        init() {
            console.error('init store');
            annotation.objects.init();
            Alpine.store('doStore').dimensions = annotation.dimensions;
            Alpine.store('doStore').updateObjectList();
        },
        setObjects(objects) {
            this.objects = objects;
        },
        async updateObjectList() {
            console.error('updateObjectList');
            this.dataState = 'loading';
            annotation.objects.clearBBoxes();
            await annotation.api.loadObjects();
            this.showHideBoxesState = 'show';
            annotation.objects.drawBoxes();
        },
        selectObject(idObject) {
            if (idObject === null) {
                this.currentObject = null;
                this.newObjectState = 'none';
            } else {
                let object = annotation.objects.get(idObject);
                this.currentObject = object;
                object.drawBox();
                this.newObjectState = 'showing';
                htmx.ajax("GET","/annotation/staticBBox/formObject/" + object.object.idStaticObject + "/" + idObject, "#formObject");
            }
        },
        selectObjectByIdStaticObject(idStaticObject) {
            let object = annotation.objects.getByIdStaticObject(idStaticObject);
            this.selectObject(object.idObject);
        },
        commentObject(idStaticObject) {
            let object = annotation.objects.getByIdStaticObject(idStaticObject);
            this.selectObject(object.idObject);
            let context= {
                target: "#formObject",
                values: {
                    idStaticObject,
                    order: object.idObject,
                    idDocument: annotation.document.idDocument
                }
            };
            htmx.ajax("GET", "/annotation/staticBBox/formComment", context );
        },
        createObject() {
            this.selectObject(null);
            this.newObjectState = 'creating';
            annotation.objects.hideBoxes();
            annotation.objects.creatingObject();
        },
        // async deleteObject(idStaticObject) {
        //     annotation.objects.clearBBoxes();
        //     await annotation.api.deleteObject(idStaticObject);
        // },
        clear() {
            console.log('clear');
            this.newObjectState = 'none';
            this.selectObject(null);
            htmx.ajax("GET","/annotation/staticBBox/formObject/0/0", "#formObject");
        },
        showHideObjects() {
            console.log('show/hide objects');
            if (this.showHideBoxesState === 'show') {
                this.showHideBoxesState = 'hide';
            } else {
                this.showHideBoxesState = 'show';
            }
            annotation.objects.drawBoxes();
        },
    });

    Alpine.effect(async () => {
        console.log('------');
        const newObjectState = Alpine.store('doStore').newObjectState;
        console.log("newobjectstate = " + newObjectState);
        if (newObjectState === 'creating') {
            $('#btnCreateObject').addClass('disabled');
            // $('#btnStartTracking').addClass('disabled');
            // $('#btnPauseTracking').addClass('disabled');
            // $('#btnStopTracking').addClass('disabled');
            // $('#btnEndObject').addClass('disabled');
            $('#btnShowHideObjects').addClass('disabled');
            // annotation.video.disablePlayPause();
            // annotation.video.disableSkipFrame();
        }
        if (newObjectState === 'created') {
            await annotation.objects.createdObject();
            $('#btnCreateObject').removeClass('disabled');
            $('#btnShowHideObjects').removeClass('disabled');
        }
        // if (newObjectState === 'showing') {
        //     $('#btnCreateObject').addClass('disabled');
            // $('#btnStartTracking').removeClass('disabled');
            // $('#btnPauseTracking').addClass('disabled');
            // // $('#btnStopTracking').addClass('disabled');
            // $('#btnEndObject').addClass('disabled');
            // $('#btnShowHideObjects').addClass('disabled');
        //     annotation.video.enablePlayPause();
        //     annotation.video.enableSkipFrame();
        // }
//        if (newObjectState === 'none') {
            //annotation.objects.clearFrameObject();
  //          $('#btnCreateObject').removeClass('disabled');
            // $('#btnStartTracking').addClass('disabled');
            // $('#btnPauseTracking').addClass('disabled');
            // $('#btnStopTracking').addClass('disabled');
            // $('#btnEndObject').addClass('disabled');
    //        $('#btnShowHideObjects').removeClass('disabled');
            // annotation.video.enablePlayPause();
      //  }
    });
    Alpine.effect(async () => {
        const dataState = Alpine.store('doStore').dataState;
        if (dataState === 'loaded') {
            console.log('Data Loaded');
            console.log(annotation.objectList);
            window.annotation.objects.annotateObjects(annotation.objectList);
            Alpine.store('doStore').setObjects(annotation.objectList);
            Alpine.store('doStore').newObjectState = 'none';
            if (annotation.idStaticObject) {
                setTimeout(function() {
                    const elmnt = document.getElementById("so_" + annotation.idStaticObject);
                    elmnt.scrollIntoView();
                    Alpine.store("doStore").selectObjectByIdStaticObject(annotation.idStaticObject);
                },100);
            }
        }
    });
});
