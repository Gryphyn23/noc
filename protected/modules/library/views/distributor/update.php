<?php
$this->breadcrumbs=array(
	'Distributors'=>array('admin'),
	$model->distributor_name=>array('view','id'=>$model->distributor_id),
	'Update',
);

	?>


<?php echo $this->renderPartial('_form',array('model'=>$model)); ?>