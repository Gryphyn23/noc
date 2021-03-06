<?php
$this->breadcrumbs = array(
    Authitem::AUTHITEM_LABEL . 's' => array('admin'),
    'Manage',
);


Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
$('.search-form').toggle();
return false;
});
$('.search-form form').submit(function(){
$.fn.yiiGridView.update('authitem-grid', {
data: $(this).serialize()
});
return false;
});
");
?>

<?php echo CHtml::link('Advanced Search', '#', array('class' => 'search-button btn btn-primary btn-flat')); ?>&nbsp;
<?php echo CHtml::link('Create', array('Authitem/create'), array('class' => 'btn btn-primary btn-flat')); ?>

<div class="btn-group">
    <button type="button" class="btn btn-info btn-flat">More Options</button>
    <button type="button" class="btn btn-info btn-flat dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu" role="menu">
        <li><a href="#">Download All Records</a></li>
        <li><a href="#">Download All Filtered Records</a></li>
        <li><a href="#">Upload</a></li>
    </ul>
</div>

<br/>
<br/>

<div class="search-form" style="display:none">
    <?php
    $this->renderPartial('_search', array(
        'model' => $model,
    ));
    ?>
</div><!-- search-form -->

<?php $fields = Authitem::model()->attributeLabels(); ?>
<div class="box-body table-responsive">
    <table id="authitem_table" class="table table-bordered">
        <thead>
            <tr>
                <th><?php echo $fields['name']; ?></th>
                <!--<th><?php echo $fields['type']; ?></th>-->
                <th><?php echo $fields['description']; ?></th>
                <!--<th><?php echo $fields['bizrule']; ?></th>-->
                <!--<th><?php echo $fields['data']; ?></th>-->
                <th>Actions</th>
            </tr>
        </thead>

    </table>
</div>

<script type="text/javascript">
    $(function() {
        var table = $('#authitem_table').dataTable({
            "filter": false,
            "processing": true,
            "serverSide": true,
            "bAutoWidth": false,
            "ajax": "<?php echo Yii::app()->createUrl($this->module->id . '/Authitem/data'); ?>",
            "columns": [
                {"name": "name", "data": "name"},
//                {"name": "type", "data": "type"}, 
                {"name": "description", "data": "description"},
//                {"name": "bizrule", "data": "bizrule"},
//                {"name": "data", "data": "data"}, 
                {"name": "links", "data": "links", 'sortable': false}
            ]
        });

        $('#btnSearch').click(function() {
            table.fnMultiFilter({
                "name": $("#Authitem_name").val(), "type": $("#Authitem_type").val(), "description": $("#Authitem_description").val(), "bizrule": $("#Authitem_bizrule").val(), "data": $("#Authitem_data").val(), });
        });



        jQuery(document).on('click', '#authitem_table a.delete', function() {
            if (!confirm('Are you sure you want to delete this item?'))
                return false;
            $.ajax({
                'url': jQuery(this).attr('href') + '&ajax=1',
                'type': 'POST',
                'dataType': 'text',
                'success': function(data) {
                    $.growl(data, {
                        icon: 'glyphicon glyphicon-info-sign',
                        type: 'success'
                    });

                    table.fnMultiFilter();
                },
                error: function(jqXHR, exception) {
                    alert('An error occured: ' + exception);
                }
            });
            return false;
        });
    });
</script>