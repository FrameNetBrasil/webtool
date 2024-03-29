<script>
    let dialogPane = {
            template: '#dialog-pane',
            props: [],
            data() {
                return {
                    model: this.$store.state.model,
                    fe: {},
                    frame: {},
                    o: {},
                }
            },
            computed: {
                title: function() {
                    return (this.currentObject ? "Annotated Object #" + this.currentObject.idObject : 'aa' )
                },
                currentFrame: function() {
                    return this.$store.state.currentFrame
                },
                currentObject: function() {
                    return this.$store.state.currentObject
                },
            },
            created() {
            },
            mounted: function () {
                //
                // watch currentObjectState
                //
                this.$store.watch(
                    (state, getters) => getters.currentObjectState,
                    (currentObjectState) => {
                        if ((currentObjectState == 'created') || (currentObjectState == 'editingFE') || (currentObjectState == 'tracking')) {
                            this.o = this.currentObject;
                            this.openDialog();
                        }
                    }
                )

                this.$nextTick(() => {
                    let that = this;

                    $('#dlgObject').dialog({
                        modal: true,
                        closed: true,
                        title: this.title,
                        toolbar: '#dlgObject_tools',
                        border: true,
                        doSize: true
                    });

                    $('#dlgObjectUpdate').linkbutton({
                        iconCls: 'icon-save',
                        plain: true,
                        size: null,
                        onClick: function () {
                            that.o.color = '#' + that.fe.rgbBg;
                            that.o.idFrame = that.frame.idFrame;
                            that.o.frame = that.frame.name;
                            that.o.idFE = that.fe.idFrameElement;
                            that.o.fe = that.fe.name;
                            //that.$emit('updatedObject', that.o);
                            that.$store.dispatch('updateObject', that.o);
                            //that.$store.commit('currentObject', that.o);
                            //that.$store.commit('currentObjectState', 'updated');
                            $('#dlgObject').dialog('close');
                        }
                    });

                    $('#lookupFE').combogrid({
                        panelWidth: 220,
                        // url: that.model.urlLookupFE,
                        idField: 'idFrameElement',
                        textField: 'name',
                        mode: 'remote',
                        fitColumns: true,
                        columns: [[
                            {field: 'idFrameElement', hidden: true},
                            {
                                field: 'name', title: 'Name', width: 202, styler: function (value, row, index) {
                                    return 'background-color:#' + row.rgbBg + ';color:#' + row.rgbFg;
                                }
                            }
                        ]],
                        onChange: function (newValue, oldValue) {
                            if (newValue == '') {
                                $('#lookupFE').combogrid('setValue', '');
                            }
                        },
                        onClickRow: function (index, row) {
                            console.log(row);
                            that.fe = row;
                        }
                    });

                    $('#lookupFrame').combogrid({
                        panelWidth: 220,
                        url: manager.baseURL + "webtool/data/frame/lookupData",
                        idField: 'idFrame',
                        textField: 'name',
                        mode: 'remote',
                        fitColumns: true,
                        columns: [[
                            {field: 'idFrame', hidden: true},
                            {field: 'name', title: 'Name', width: 202}
                        ]],
                        onChange: function (newValue, oldValue) {
                            console.log('idFrame = ' + newValue + ' - ' + oldValue);
                            that.$nextTick(() => {
                                if (parseInt(newValue)) {
                                    console.log('ggg');
                                    //that.combogridFE(newValue);
                                    let urlFE =  manager.baseURL + 'webtool/data/frameelement/lookupDataDecorated' + '/' + newValue;
                                    console.log('urlFE = ' + urlFE);
                                    $('#lookupFE').combogrid({url: urlFE});
                                }
                            });
                        },
                        onClickRow: function (index, row) {
                            console.log(row);
                            that.frame = row;
                        }
                    });


                    $('#formObject').form({
                        success: function (data) {
                            alert(data)
                        }
                    });
                })

            },
            methods: {
                openDialog: function() {
                    $('#dlgObject').dialog({title: this.title});
                    $('#dlgObject').dialog('doLayout');
                    $('#dlgObject').dialog('open');
                },
                combogridFE: function(idFrame) {
                    let urlFE = this.model.urlLookupFE + '/' + idFrame;
                    console.log('urlFE = ' + urlFE);
                    $('#lookupFE').combogrid({url: urlFE});
                }
            },
            watch: {
            }
        }
</script>

<script type="text/x-template" id="dialog-pane">
    <div>
        <div id="dlgObject" :title="title" style="width:400px;height:180px;padding:0px">
            <div id="dlgObject_tools">
                <a href='#' name="dlgObjectUpdate" id="dlgObjectUpdate">
                    Update
                </a>
            </div>
            <form id="formObject" method="post">
                <div class="mFormContainer">
                    <div class="mFormRow">
                        <span>Current frame:</span>@{{currentFrame}}
                    </div>
                    <div class="mFormRow">
                        <label for="lookupFrame">Frame:</label>
                        <input id="lookupFrame" name="lookupFrame"/>
                    </div>
                    <div class="mFormRow">
                        <label for="lookupFE">Frame Element:</label>
                        <input id="lookupFE" name="lookupFE"/>
                    </div>
                </div>
            </form>
        </div>

    </div>
</script>

