<table id="qualiaGridTable">
</table>
<script>
    $(function () {
        $('#qualiaGridTable').datagrid({
            fit: true,
            url: "/qualia/listForGrid",
            queryParams: {{ Js::from($data) }},
            showHeader: true,
            rownumbers: false,
            border: false,
            columns: [[
                {
                    title:'Type',
                    field: 'qualiaType',
                    formatter: (value, rowData) => {
                        return `<div><span class='${rowData.icon}'></span><span class='${rowData.color}'>${value}</span></div>`
                    }
                },
                {
                    title:'Relation',
                    field: 'info',
                },
                {
                    title:'Frame',
                    field: 'frame',
                    hformatter: (title) => {
                        return `<div><span class='material-icons-outlined wt-icon wt-icon-frame'></span>${title}</div>`
                    }
                },
                {
                    title:'FrameElement 1',
                    field: 'frameElement1',
                    formatter: (value, rowData) => {
                        return `<div><span class='${rowData.iconFE1}'></span>${value}</div>`
                    }
                },
                {
                    title:'FrameElement 2',
                    field: 'frameElement2',
                    formatter: (value, rowData) => {
                        return `<div><span class='${rowData.iconFE1}'></span>${value}</div>`
                    }
                },
            ]],
            onClickRow: (row) => {
                let idQualia = row.id;
                window.location.href = `/qualia/${idQualia}/edit`;
            },
        });
    });
</script>
<style>
    .definition {
        display: inline-block;
        font-size: 12px;
    }

    .fe-name {
        display: inline-block;
        font-size: 12px;
    }

    .datagrid-body table tbody tr td div.datagrid-cell {
        height: 24px !important;
        padding-top: 4px;
    }
</style>