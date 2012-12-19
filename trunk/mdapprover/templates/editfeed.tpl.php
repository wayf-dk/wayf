    <p>The following entities was fetched from:</p>
    <p><strong><?= $mdurl ?></strong></p>
    <h3>Accepted</h3>
    <table class="table table-condensed table-striped" id="mdapp-accepted-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>entityID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Attributes</th>
                <th>Created</th>
                <th>User</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
<?php
foreach ($accepted AS $entity) {
    echo "<tr>";
    echo "<td>" . $entity['entity']->id . "</td>";
    echo "<td>" . $entity['entity']->entityid . "</td>";
    echo "<td>" . $entity['entity']->name . "</td>";
    echo "<td>" . $entity['entity']->purpose . "</td>";
    if (empty($entity['entity']->attributes)) {
        echo "<td>None</td>";
    } else {
        echo "<td><i class='icon-chevron-down'></i><ul class='attr_view' style='display: none;'>";
        foreach ($entity['entity']->attributes AS $attr) {
            if (isset($attrMap[$attr])) {
                echo "<li>{$attrMap[$attr]}</li>"; 
            } else {
                echo "<li>{$attr}</li>";
            }
        } 
        echo "</ul></td>";
    }
    echo "<td>" . $entity['entity']->created . "</td>";
    echo "<td>" . $entity['entity']->user . "</td>";
    echo '<td><i class="icon-minus-sign mdapp-removeentity" id="' . base64_encode ($entity['entity']->entityid) . '"></i>';
    if (!$entity['same']) {
        echo '<span class="label label-important">Has changed</span>';
    }
    echo '</td>';
    echo "</tr>";
}
?>
        </tbody>
    </table>

    <h3>Not accepted</h3>
    <table class="table table-condensed table-striped" id="mdapp-notaccepted-table">
        <thead>
            <tr>
                <th>entityID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Attributes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
<?php
foreach ($notaccepted AS $entity) {
    echo "<tr>";
    echo "<td>" . $entity->entityid . "</td>";
    echo "<td>" . $entity->name . "</td>";
    echo "<td>" . $entity->purpose . "</td>";
    if (empty($entity->attributes)) {
        echo "<td>None</td>";
    } else {
        echo "<td><i class='icon-chevron-down'></i><ul class='attr_view' style='display: none;'>";
        foreach ($entity->attributes AS $attr) {
            if (isset($attrMap[$attr])) {
                echo "<li>{$attrMap[$attr]}</li>"; 
            } else {
                echo "<li>{$attr}</li>";
            }
        } 
        echo "</ul></td>";
    }
    echo '<td><i class="icon-plus-sign mdapp-addentity" id="' . base64_encode ($entity->entityid) . '"></i></td>';
    echo "</tr>";
}
?>
        </tbody>
    </table>
