<?php
$json = file_get_contents('files/bbox_labels_600_hierarchy.json');
$tree = json_decode($json);

print_r($tree);
$s = [];
traverseTree($tree, $s);
$csv = '';
foreach($s as $line) {
    $csv .= $line[0] . ';' . $line[1] . "\n";
}
file_put_contents('files/bbox_labels_600_hierarchy.csv', $csv);

function traverseTree($t, &$s) {
    $label = $t->LabelName;
    foreach($t->Subcategory as $l) {
        $lb = $l->LabelName;
        $s[] = [$label, $lb];
        if (isset($l->Subcategory)) {
            traverseTree($l, $s);
        }
    }
}

