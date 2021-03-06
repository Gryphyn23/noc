<?php
/* @var $this DefaultController */

$this->breadcrumbs=array(
	$this->module->id,
);


?>

<style>
 #test {display: none; }  
 #ph2_table {display: none; } 
</style>

      <div class="panel panel-default">
          <div class="panel-heading">Survey</div>
          
          <div class="panel-body" id ="Survey">
              <div class="row">
                  <div class="col-md-6">                    
                       <?php

                        $form = $this->beginWidget('booster.widgets.TbActiveForm', array(
                            'id' => 'survey-form',
                            'enableAjaxValidation' => false,
                            'type'=>'horizontal',
                        ));
                        
                        
                        echo $form->dropDownListGroup(
                                $model, 'bws', array(
                            'wrapperHtmlOptions' => array(
                                'class' => 'col-sm-5',
                            ),
                            'widgetOptions' => array(
                                'data' =>$bws,
                                'htmlOptions' => array('multiple' => false, 'id' => 'bws_survey' , 'prompt' => 'Select bws')
                           )

                                )
                        );
                        
                        echo $form->dropDownListGroup(
                            $model, 'ph', array(
                        'wrapperHtmlOptions' => array(
                            'class' => 'col-sm-5',
                        ),
                        'widgetOptions' => array(
                            'data' =>$ph,
                            'htmlOptions' => array('multiple' => false, 'id' => 'ph_survey', 'prompt' => 'Select ph'
                                ),
                       )
                        
                            )
                    );
                
                        ?>
                  </div>
                  
              </div>
              <div class="row">
                  <div class="col-md-6"> 
                      <table class="table table-bordered">
                          <tr>
                              <td>PAMPERS BABY WELLNESS SPECIALIST</td>
                          </tr>
                          <tr>
                              <td>INTERNAL QA CHECKLIST</td>
                          </tr>
                      </table>
                      <table class="table table-bordered">
                          <tr>
                              <td>
                                  NAME OF BWS:
                              </td>
                              <td id="name_bws">
                                  
                              </td>
                          </tr>
                          <tr>
                              <td>
                                  HOSPITAL/CLINIC CHECKED:
                              </td>
                              <td>
                                  <?php
                                    echo $form->dropDownListGroup(
                                            $model, 'hospital', array(
                                        
                                        'widgetOptions' => array(
                                           
                                            'htmlOptions' => array('multiple' => false, 'id' => 'hospital_survey', 'prompt' => 'Select hospital'),
                                       ),'labelOptions'=> array(
                                           'label'=>false,
                                       )

                                            )
                                    );
                                    ?>
                              </td>
                          </tr>
                          <tr>
                              <td>
                                  DATE CHECKED:
                              </td>
                              <td>
                                   <?php echo $form->textField($model,'date',array('size'=>10,'maxlength'=>50,'value'=>date('Y-m-d'), 'readonly'=>'readonly')); ?>
                                   <?php echo $form->error($model,'date'); ?>
                              </td>
                          </tr>
                          <tr>
                              <td>
                                  NAME OF RATER:
                              </td>
                          
                              <td>
                                   <?php echo $form->textField($model,'rater',array('size'=>30,'maxlength'=>50, 'readonly'=>'readonly','value'=>Yii::app()->user->userObj->user_name)); ?>
                                   <?php echo $form->error($model,'rater'); ?>
                              </td>
                         </tr>
                      </table>
 
                      <table class="table table-bordered" id ="Question">
                          <?php 
                            foreach($question as $key => $val){
                           ?>
                          <tr>
                              <th>
                                  <?php echo $key ?>
                              </th>
                              <th>
                                  
                              </th>
                          </tr>
                          <?php
                           foreach($val as $k => $v){
                          ?>
                          <tr>
                              <td><?php echo $v ?></td>
                              <td>  <select name ="answer[]">
                                      <option value="1">1</option>
                                      <option value="0.5">0.5</option>
                                      <option value="0.25">0.25</option>
                                      <option value="0.75">0.75</option>
                                      <option value="0">0</option>
                                    </select>
<!--                                  <input type="textbox" name ="answer[]" maxlength='2' onkeypress="javascript: return acceptValidNumbersOnly(this,event);"</td>-->
                              <input type="hidden" name ="question[]" value="<?php echo $v?>" ></td>
                          </tr>
                          <?php
                           }
                           }                        
                          ?>
                      </table>
                  </div>
              </div>
           
             <?php echo CHtml::submitButton('Save', array('class' => 'btn btn-primary btn-flat','id'=>'test_sub')); ?></div>                              
        </div>
<!--      this.style.visibility=hidden-->
        
      </div>
 
<?php $this->endWidget(); ?>

      <script>
               function disable()
{
     $('#test_sub').attr("disabled", true);
}
      $(document).ready(function(){
      $('#bws_survey').val('');
      

        $("form").submit(function() {
            $(this).submit(function() {
                return false;
            });
            return true;
        });
      
      $('#bws_survey').change(function() {
    
        $.ajax({
            type: 'GET',
            url: '<?php echo Yii::app()->createUrl('/pome/survey/getBwsDetail'); ?>' + '&bws_id=' + this.value,
            dataType: "json",
            success: function(data) {
                document.getElementById('name_bws').innerHTML = data.firstname+' '+data.lastname;
//                document.getElementById('S').innerHTML = data.firstname+' '+data.lastname;
            },
            error: function(data) {
                alert("Error occured: Please try again.");
            }
        });
        
        var ph = document.getElementById('ph_survey');
        $.ajax({
            type: 'GET',
            url: '<?php echo Yii::app()->createUrl('/pome/survey/getHospitalByPh'); ?>' + '&bws=' + this.value+'&ph='+ph.value,
            dataType: "json",
            success: function(data) {
//                var options = '';
//                options  = '<option value="">--</option>';
//                for(var i =0;i<data.lenght; i++)
//                {
//                     options += '<option value="' + data[i].outlet_id + '">' + data[i].outlet_code + '</option>';   
//                }
                        $("#hospital_survey").html(data);
            },
            error: function(data) {
                alert("Error occured: Please try again.");
            }
        });
    
});

$('#ph_survey').change(function() {
    
   var bws = document.getElementById('bws_survey');
        $.ajax({
            type: 'GET',
            url: '<?php echo Yii::app()->createUrl('/pome/survey/getHospitalByPh'); ?>' + '&ph=' + this.value+'&bws='+bws.value,
            dataType: "json",
            success: function(data) {
//                var options = '';
//                options  = '<option value="">--</option>';
//                for(var i =0;i<data.lenght; i++)
//                {
//                     options += '<option value="' + data[i].outlet_id + '">' + data[i].outlet_code + '</option>';   
//                }
                        $("#hospital_survey").html(data);
            },
            error: function(data) {
                alert("Error occured: Please try again.");
            }
        });
    
});
      });
//$('#bws_survey').change(function() {
//    
//        $.ajax({
//            type: 'GET',
//            url: '<?php echo Yii::app()->createUrl('/pome/survey/getBwsDetail'); ?>' + '&bws_id=' + this.value,
//            dataType: "json",
//            success: function(data) {
//                document.getElementById('name_bws').innerHTML = data.firstname+' '+data.lastname;
////                document.getElementById('S').innerHTML = data.firstname+' '+data.lastname;
//            },
//            error: function(data) {
//                alert("Error occured: Please try again.");
//            }
//        });
//    
//});


function acceptValidNumbersOnly(obj,e) {
			var key='';
			var strcheck = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()_+=-`{}[]:\";'\|/?,><\\23456789 ";
			var whichcode = (window.Event) ? e.which : e.keyCode;
			try{
			if(whichcode == 13 || whichcode == 8)return true;
			key = String.fromCharCode(whichcode);
			if(strcheck.indexOf(key) != -1)return false;
			return true;
			}catch(e){;}
            //onkeypress="javascript: return acceptValidNumbersOnly(this,event);"
}
</script>

