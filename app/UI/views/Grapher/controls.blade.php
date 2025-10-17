<div class="ui modal" id="grapherOptionsModal">
    <i class="close icon"></i>
    <div class="header">
        Grapher Options
    </div>
    <div class="content">
        <div class="ui form">
            <div class="field">
                <label for="ranker">Ranker</label>
                <select id="ranker">
                    <option value="network-simplex">network-simplex</option>
                    <option value="tight-tree">tight-tree</option>
                    <option value="longest-path">longest-path</option>
                </select>
            </div>
            <div class="field">
                <label for="rankdir">RankDir</label>
                <select id="rankdir">
                    <option value="TB">Top-Bottom</option>
                    <option value="BT">Bottom-Top</option>
                    <option value="RL">Right-Left</option>
                    <option value="LR">Left-Right</option>
                </select>
            </div>
            <div class="field">
                <label for="align">Align</label>
                <select id="align">
                    <option value="DL">Down-Left</option>
                    <option value="DR">Down-Right</option>
                    <option value="UL">Up-Left</option>
                    <option value="UR">Up-Right</option>
                </select>
            </div>
            <div class="field">
                <label for="connector">Connector</label>
                <select id="connector">
                    <option value="normal">Normal</option>
                    <option value="smooth">Smooth</option>
                    <option value="jumpover">Jumpover</option>
                    <option value="curve">Curve</option>
                </select>
            </div>
            <div class="field">
                <div class="ui checkbox">
                    <input type="checkbox" id="vertices">
                    <label for="vertices">Vertices</label>
                </div>
            </div>
            <div class="field">
                <label for="ranksep">RankSep: <span id="ranksep-value"></span></label>
                <input id="ranksep" type="range" min="1" max="100" />
            </div>
            <div class="field">
                <label for="edgesep">EdgeSep: <span id="edgesep-value"></span></label>
                <input id="edgesep" type="range" min="1" max="100" />
            </div>
            <div class="field">
                <label for="nodesep">NodeSep: <span id="nodesep-value"></span></label>
                <input id="nodesep" type="range" min="1" max="100" />
            </div>
        </div>
    </div>
    <div class="actions">
        <div class="ui cancel button">Cancel</div>
        <div class="ui primary button" id="btnApplyGrapherOptions">Apply</div>
    </div>
</div>

<script>
$(function() {
    const modal = $('#grapherOptionsModal');

    // When modal is shown, populate form with current Alpine data
    modal.modal({
        detachable: false,
        onShow: function() {
            const grapherApp = document.getElementById('grapherApp');
            if (grapherApp && Alpine && Alpine.$data) {
                const data = Alpine.$data(grapherApp);

                // Populate form fields with current values
                document.getElementById('ranker').value = data.ranker;
                document.getElementById('rankdir').value = data.rankdir;
                document.getElementById('align').value = data.align;
                document.getElementById('connector').value = data.connector;
                document.getElementById('vertices').checked = data.vertices;
                document.getElementById('ranksep').value = data.ranksep;
                document.getElementById('edgesep').value = data.edgesep;
                document.getElementById('nodesep').value = data.nodesep;

                // Update range value displays
                document.getElementById('ranksep-value').textContent = data.ranksep;
                document.getElementById('edgesep-value').textContent = data.edgesep;
                document.getElementById('nodesep-value').textContent = data.nodesep;
            }
        }
    });

    // Update range value displays as user moves sliders
    $('#ranksep').on('input', function() {
        $('#ranksep-value').text(this.value);
    });
    $('#edgesep').on('input', function() {
        $('#edgesep-value').text(this.value);
    });
    $('#nodesep').on('input', function() {
        $('#nodesep-value').text(this.value);
    });

    // Handle Apply button click
    $('#btnApplyGrapherOptions').on('click', function() {
        const grapherApp = document.getElementById('grapherApp');
        if (grapherApp && Alpine && Alpine.$data) {
            const data = Alpine.$data(grapherApp);

            // Update Alpine component data from form fields
            data.ranker = document.getElementById('ranker').value;
            data.rankdir = document.getElementById('rankdir').value;
            data.align = document.getElementById('align').value;
            data.connector = document.getElementById('connector').value;
            data.vertices = document.getElementById('vertices').checked;
            data.ranksep = parseInt(document.getElementById('ranksep').value);
            data.edgesep = parseInt(document.getElementById('edgesep').value);
            data.nodesep = parseInt(document.getElementById('nodesep').value);

            // Trigger relayout
            if (data.relayout) {
                data.relayout();
            }
        }

        // Close modal
        modal.modal('hide');
    });
});
</script>

