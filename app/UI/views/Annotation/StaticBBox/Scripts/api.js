/*
Annotation API
 */
annotation.api = {
    loadObjects: async function () {
        await $.ajax({
            url: "/annotation/staticBBox/gridObjects/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                annotation.objectList = response;
                //document.dispatchEvent(evtDOObjects);
                Alpine.store('doStore').dataState = 'loaded';
            }
        });
    },
    loadSentences: async () => {
        let result = null;
        await $.ajax({
            url: "/annotation/staticBBox/gridSentences/" + annotation.document.idDocument,
            method: "GET",
            dataType: "json",
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    updateObject: async (params) => {
        params._token = annotation._token;
        let result = null;
        await $.ajax({
            url: "/annotation/staticBBox/updateObject",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    updateObjectAnnotation: async (params) => {
        params._token = annotation._token;
        let result = null;
        await $.ajax({
            url: "/annotation/staticBBox/updateObjectAnnotation",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    updateBBox: async (params) => {
        params._token = annotation._token;
        let result = null;
        await $.ajax({
            url: "/annotation/staticBBox/updateBBox",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                result = response;
            }
        });
        return result;
    },
    cloneObject: async (params, callback) => {
        params._token = annotation._token;
        console.log("before clone", params);
        await $.ajax({
            url: "/annotation/staticBBox/cloneObject",
            method: "POST",
            dataType: "json",
            data: params,
            success: (response) => {
                console.log("response",response);
                callback(response);
            }
        });
    },
};
