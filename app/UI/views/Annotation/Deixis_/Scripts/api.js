annotation.api = {
    loadLayerList: async function() {
        await $.ajax({
            url: "/annotation/deixis/loadLayerList/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                annotation.layerList = response;
                Alpine.store("doStore").dataState = "loaded";
            }
        });
    },
    loadObjects: async function() {
        await $.ajax({
            url: "/annotation/deixis/gridObjects/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                annotation.objectList = response;
                Alpine.store("doStore").dataState = "loaded";
            }
        });
    },
    deleteObject: async (idDynamicObject) => {
        console.log("deletting api", idDynamicObject, annotation._token);

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
            toDelete: toDelete
        };
        try {
            let url = "/index.php/webtool/annotation/multimodal/deleteObjects";
            manager.doAjax(url, (response) => {
                if (response.type === "success") {
                    // $.messager.alert('Ok', 'Objects deleted.','info');
                } else if (response.type === "error") {
                    throw new Error(response.message);
                }
            }, params);
        } catch (e) {
            $.messager.alert("Error", e.message, "error");
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
            error: (xhr, status, error) => {
                console.error(error);
            }
        });
        return result;
    },
    updateObjectAnnotation: async (params, callback) => {
        params._token = annotation._token;
        await $.ajax({
            url: "/annotation/deixis/updateObjectAnnotation",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                callback(response);
            }
        });
    },
    updateObjectRange: async (params, callback) => {
        params._token = annotation._token;
        await $.ajax({
            url: "/annotation/deixis/updateObjectRange",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                callback(response);
            }
        });
    },
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
    deleteBBox: async (params, callback) => {
        params._token = annotation._token;
        await $.ajax({
            url: "/annotation/deixis/deleteBBox",
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

    createNewObjectAtLayer: async (params, callback) => {
        params._token = annotation._token;
        await $.ajax({
            url: "/annotation/deixis/createNewObjectAtLayer",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                callback(response);
            }
        });
    },

    updateObjectFrame: async (params, callback) => {
        params._token = annotation._token;
        await $.ajax({
            url: "/annotation/deixis/updateObjectFrame",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                callback(response);
            }
        });
    }


};
