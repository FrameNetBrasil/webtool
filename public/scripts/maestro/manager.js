var manager = {
    messager(type, message) {
        if ((type === 'error') || (type === 'warning')|| (type === 'info')) {
            $.notify.alert( '', message, type);
        } else {
            $.notify.show({
                cls: 'wt-messager wt-messager-' + type,
                title: null,//type.charAt(0).toUpperCase() + type.slice(1),
                label: type.charAt(0).toUpperCase() + type.slice(1),
                msg: message,
                timeout: 4000,
                showType: 'show',
                style: {
                    right: '',
                    top: document.body.scrollTop + document.documentElement.scrollTop,
                    bottom: ''
                }
            });
        }
    },
    confirmPost(type, message, action) {
        $.messager.confirm({
            cls: 'wt-messager wt-messager-' + type,
            title: type.charAt(0).toUpperCase() + type.slice(1),
            msg: message,
            fn: function(r){
                if (r){
                    console.log('confirmed: '+r);
                }
            }
        });
    },
    confirmDelete(message, action, onApprove, onDeny) {
        $('#confirmtemplate')
            .toast({
                title: 'Warning',
                message: message + ' Confirm?',
                displayTime: 0,
                position: 'centered',
                //class:'ui warning message',
                // className: {
                //     toast: 'ui message'
                // },
                onDeny    : function(){
                    //$.toast({message:'Wait not yet!'});
                    console.log('no');
                    return true;
                },
                onApprove : async function() {
                    //$.toast({message:'Approved'});
                    console.log('yes');
                    await htmx.ajax('DELETE', action, null);
                    // if (event) {
                    //     $("#" + event[0]).trigger(event[1]);
                    // }
                    if (onApprove) {
                        onApprove();
                    }
                    return true;
                }
            });
        /*
        $.notify.confirm('',message, function(r) {
            if (r) {
                htmx.ajax('DELETE', action, target);
                if (event) {
                    $("#" + event[0]).trigger(event[1]);
                }
            }
        });

         */
    },
    notify(type, message) {
        $.toast({
            class: type,
            message: message,
            className: {
                content: 'content  wt-notify-' + type,
            },
        })
        ;
        // $("body").trigger("notify",{
        //     detail: {
        //         type: type,
        //         message: message
        //     }
        // });
    }
};

$(function () {
    document.body.addEventListener("notify", function(evt) {
        console.log(evt.detail.type, evt.detail.message);
        $.toast({
            class: evt.detail.type,
            message: evt.detail.message,
            className: {
                content: 'content  wt-notify-' + evt.detail.type,
            },
        })
        ;
    });
});
