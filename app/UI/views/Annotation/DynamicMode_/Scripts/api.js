annotation.api = {
    loadObjects: async function () {
        await $.ajax({
            url: "/annotation/dynamicMode/gridObjects/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                annotation.objectList = response;
                //document.dispatchEvent(evtDOObjects);
                Alpine.store('doStore').dataState = 'loaded';
            }
        });
    },
    loadObject: async (idDynamicObject) => {
        return await ky.get("/annotation/dynamicMode/object/" + idDynamicObject, {}).json();
    },
    deleteObject: async (idDynamicObject) => {
        console.log('deletting api', idDynamicObject, annotation._token);

        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/" + idDynamicObject,
            method: "DELETE",
            dataType: "json",
            data: {
                _token: annotation._token
            },
            success: (response) => {
                result = response;
            }
        });
        return result;
    },

    deleteObjects: (toDelete) => {
        let params = {
            toDelete: toDelete,
        };
        try {
            let url = "/index.php/webtool/annotation/multimodal/deleteObjects";
            manager.doAjax(url, (response) => {
                if (response.type === 'success') {
                    // $.messager.alert('Ok', 'Objects deleted.','info');
                } else if (response.type === 'error') {
                    throw new Error(response.message);
                }
            }, params);
        } catch (e) {
            $.messager.alert('Error', e.message, 'error');
        }
    },
    loadSentences: async () => {
        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/gridSentences/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    listFrame: () => {
        let url = "/index.php/webtool/data/frame/combobox";
        let frames = [];
        manager.doAjax(url, (response) => {
            frames = response;
        }, {});
        return frames;
    },
    listFrameElement: () => {
        let url = "/index.php/webtool/data/frameelement/listAllDecorated";
        let frames = [];
        manager.doAjax(url, (response) => {
            frames = response;
        }, {});
        return frames;
    },
    updateObject: async (params) => {
        params._token = annotation._token;
        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/updateObject",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            },
            error: (xhr,status,error) => {
                console.error(error);
            }
        });
        return result;
    },
    // updateObjectAnnotation: async (params) => {
    //     params._token = annotation._token;
    //     let result = null;
    //     await $.ajax({
    //         url: "/annotation/dynamicMode/updateObjectAnnotation",
    //         method: "POST",
    //         dataType: "json",
    //         data: params,
    //         success: (response) => {
    //             result = response;
    //         }
    //     });
    //     return result;
    // },
    updateBBox: async (params) => {
        params._token = annotation._token;
        let result = null;
        params.bbox.blocked = (params.bbox.blocked ? 1 : 0);
        await $.ajax({
            url: "/annotation/dynamicMode/updateBBox",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    createBBox: async (params, callback) => {
        params._token = annotation._token;
        params.bbox.blocked = (params.bbox.blocked ? 1 : 0);
        await $.ajax({
            url: "/annotation/dynamicMode/createBBox",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                callback(response);
            }
        });
    },
    cloneObject: async (params) => {
        params._token = annotation._token;
        console.log(params);
        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/cloneObject",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            }
        });
        return result;
    },

    updateObjectData: (params) => {
        return new Promise((resolve, reject) => {
            try {
                let result = {};
                let url = "/index.php/webtool/annotation/dynamic/updateObjectData";
                manager.doAjax(url, (response) => {
                    if (response.type === 'success') {
                        resolve(response.data);
                    } else if (response.type === 'error') {
                        throw new Error(response.message);
                    }
                }, params);

            } catch (e) {
                $.messager.alert('Error', e.message, 'error');
            }
        });
    },

    loadWords: async function () {
        console.log(annotation.videoObject);
        await $.ajax({
            url: "/annotation/dynamicMode/words/" + annotation.videoObject.idVideo,
            method: "GET",
            dataType: "json",
            success: (response) => {
                annotation.wordList = response;
                Alpine.store('doStore').dataState = 'loaded';
            }
        });
    },

    joinWords: async function (params) {
        params._token = annotation._token;
        params.idVideo  = annotation.videoObject.idVideo;
        params.idLanguage  = annotation.videoObject.idLanguage;
        params.idDocument  = annotation.document.idDocument;
        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/joinWords",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
                console.log(result);
            }
        });
        return result;
    },

    splitSentence: async function (params) {
        params._token = annotation._token;
        let result = null;
        await $.ajax({
            url: "/annotation/dynamicMode/splitSentence",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
                console.log(result);
            }
        });
        return result;
    },

};
