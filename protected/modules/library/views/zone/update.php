<?php

$this->breadcrumbs = array(
    'Zones' => array('admin'),
    $model->zone_name => array('view', 'id' => $model->zone_id),
    'Update',
);
?>


<?php

echo $this->renderPartial('_form', array(
    'model' => $model,
    'sales_office_list' => $sales_office_list,
));
?>