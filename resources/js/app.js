import './bootstrap';
// import './webcomponents';
import Alpine from 'alpinejs';


import Chart from 'chart.js/auto';
import * as d3 from 'd3';

import svgPanZoom from "svg-pan-zoom";
import ky from 'ky';
import Split from 'split.js';

// Component imports
import './components/messengerComponent.js';
import browseSearchComponent from './components/browseSearchComponent.js';
import searchComponent from './components/searchComponent.js';
import treeComponent from './components/treeComponent.js';
import searchFormComponent from './components/searchFormComponent.js';
import dataGridComponent from './components/dataGridComponent.js';
import udTreeComponent from './components/udTreeComponent.js';
import grapherComponent from './components/grapherComponent.js';
import contextMenuComponent from './components/contextMenuComponent.js';

import '../css/fomantic-ui/semantic.less';
// import 'primeflex/primeflex.css';
import '../css/app.less';
// import '../css/webcomponents.scss';

window.Chart = Chart;
window.d3 = d3;
window.svgPanZoom = svgPanZoom;
window.ky = ky;
window.Split = Split;

// Make components available globally
window.udTreeComponent = udTreeComponent;
window.grapherComponent = grapherComponent;

// Make Alpine available globally before any components try to use it
window.Alpine = Alpine;

document.addEventListener("DOMContentLoaded", () => {
    // Register legacy components
    Alpine.data('searchFormComponent', searchFormComponent);
    Alpine.data('searchComponent', searchComponent);
    Alpine.data('treeComponent', treeComponent);
    Alpine.data('browseSearchComponent', browseSearchComponent);
    Alpine.data('dataGrid', dataGridComponent);
    Alpine.data('udTree', udTreeComponent);
    Alpine.data('grapher', grapherComponent);
    Alpine.data('contextMenu', contextMenuComponent);
    Alpine.start();

});

