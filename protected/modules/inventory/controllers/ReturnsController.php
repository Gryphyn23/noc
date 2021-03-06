<?php

class ReturnsController extends Controller {
    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    //public $layout='//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'view'),
                'users' => array('@'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update', 'data', 'loadReferenceDRNos', 'infraLoadDetailsByDRNo', 'queryInfraDetails', 'getDetailsByReturnInvID', 'createReturnable', 'infraLoadDetailsBySelectedDRNo',
                    'returnableData', 'getReturnableDetailsByReturnableID', 'attachmentPreview', 'returnableDelete', 'returnReceiptData', 'getReturnReceiptDetailsByReturnReceiptID', 'returnReceiptDelete', 'returnableView', 'getDetailsByReturnableID',
                    'returnReceiptView', 'getDetailsByReturnReceiptID', 'returnMdseData', 'getReturnMdseDetailsByReturnMdseID', 'returnMdseView', 'getDetailsByReturnMdseID', 'printReturnable', 'loadReturnablePDF', 'returnableViewPrint', 'returnReceiptViewPrint',
                    'loadReturnReceiptPDF', 'printReturnReceipt', 'returnMdseDelete', 'returnMdseViewPrint', 'loadReturnMdsePDF', 'printReturnMdse', 'uploadAttachment', 'downloadAttachment', 'loadAttachmentDownload', 'deleteAttachment', 'returnReceiptDetailDelete', 'returnableDetailDelete', 'returnMdseDetailDelete',
                    'createReturnMdse'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $this->layout = '//layouts/column1';
        $this->pageTitle = 'Returns';

        $returnable = new Returnable;
        $return_receipt = new ReturnReceipt;
        $return_receipt_detail = new ReturnReceiptDetail;
        $return_mdse = new ReturnMdse;
        $return_mdse_detail = new ReturnMdseDetail;
        $sku = new Sku;
        $attachment = new Attachment;

        $return_from_list = CHtml::listData(Returnable::model()->getListReturnFrom(), 'value', 'title');
        $return_to_list = CHtml::listData(ReturnMdse::model()->getListReturnTo(), 'value', 'title');
        $zone_list = CHtml::listData(Zone::model()->findAll(array("condition" => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'zone_name ASC')), 'zone_id', 'zone_name');
        $salesoffice_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');
        $warehouse_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '" AND distributor_id = ""', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');

        $c = new CDbCriteria;
        $c->select = new CDbExpression('t.*, CONCAT(t.first_name, " ", t.last_name) AS fullname');
        $c->condition = 'company_id = "' . Yii::app()->user->company_id . '"';
        $c->order = 'fullname ASC';
        $employee = CHtml::listData(Employee::model()->findAll($c), 'employee_id', 'fullname', 'employee_code');

        if (Yii::app()->request->isPostRequest && Yii::app()->request->isAjaxRequest) {

            $data = array();
            $data['success'] = false;
            $data["type"] = "success";

            if ($_POST['form'] != "details") {
                $data['form'] = $_POST['form'];

                if (isset($_POST['Returnable'])) {

                    $returnable->attributes = $_POST['Returnable'];
                    $returnable->company_id = Yii::app()->user->company_id;
                    $returnable->created_by = Yii::app()->user->name;
                    $returnable->date_returned = $returnable->transaction_date;
                    unset($returnable->created_date);

                    $validatedReturnable = CActiveForm::validate($returnable);

                    $data = $this->saveReturnables($returnable, $_POST, $validatedReturnable, $data);
                } else if (isset($_POST['ReturnReceipt'])) {

                    $return_receipt->attributes = $_POST['ReturnReceipt'];
                    $return_receipt->company_id = Yii::app()->user->company_id;
                    $return_receipt->created_by = Yii::app()->user->name;
                    $return_receipt->date_returned = $return_receipt->transaction_date;
                    unset($return_receipt->created_date);

                    $validatedReturnReceipt = CActiveForm::validate($return_receipt);

                    $data = $this->saveReturnReceipt($return_receipt, $_POST, $validatedReturnReceipt, $data);
                } else if (isset($_POST['ReturnMdse'])) {

                    $return_mdse->attributes = $_POST['ReturnMdse'];
                    $return_mdse->company_id = Yii::app()->user->company_id;
                    $return_mdse->created_by = Yii::app()->user->name;
                    $return_mdse->date_returned = $return_mdse->transaction_date;
                    unset($return_mdse->created_date);

                    $validatedReturnMdse = CActiveForm::validate($return_mdse);

                    $data = $this->saveReturnMdse($return_mdse, $_POST, $validatedReturnMdse, $data);
                }
            } else if ($_POST['form'] == "details") {
                $data['form'] = $_POST['form'];

                if (isset($_POST['ReturnReceiptDetail'])) {
                    $return_receipt_detail->attributes = $_POST['ReturnReceiptDetail'];
                    $return_receipt_detail->company_id = Yii::app()->user->company_id;
                    $return_receipt_detail->created_by = Yii::app()->user->name;
                    unset($return_receipt_detail->created_date);

                    $validatedReturnReceiptDetail = CActiveForm::validate($return_receipt_detail);

                    if ($validatedReturnReceiptDetail != '[]') {

                        $data['error'] = $validatedReturnReceiptDetail;
                        $data["type"] = "danger";
                        $data['message'] = 'Unable to process';
                    } else {

                        $c = new CDbCriteria;
                        $c->compare('t.company_id', Yii::app()->user->company_id);
                        $c->compare('t.sku_id', $return_receipt_detail->sku_id);
                        $c->with = array('brand', 'company', 'defaultUom', 'defaultZone');
                        $sku_details = Sku::model()->find($c);

                        $data['message'] = 'Added Item Successfully';
                        $data['success'] = true;

                        $data['details'] = array(
                            "sku_id" => isset($sku_details->sku_id) ? $sku_details->sku_id : null,
                            "sku_code" => isset($sku_details->sku_code) ? $sku_details->sku_code : null,
                            "sku_description" => isset($sku_details->description) ? $sku_details->description : null,
                            'brand_name' => isset($sku_details->brand->brand_name) ? $sku_details->brand->brand_name : null,
                            'sku_category' => isset($sku_details->type) ? $sku_details->type : null,
                            'sku_sub_category' => isset($sku_details->sub_type) ? $sku_details->sub_type : null,
                            'unit_price' => $return_receipt_detail->unit_price != "" ? number_format($return_receipt_detail->unit_price, 2, '.', '') : number_format(0, 2, '.', ''),
                            'batch_no' => isset($return_receipt_detail->batch_no) ? $return_receipt_detail->batch_no : null,
                            'expiration_date' => isset($return_receipt_detail->expiration_date) ? $return_receipt_detail->expiration_date : null,
                            'returned_quantity' => $return_receipt_detail->returned_quantity != "" ? $return_receipt_detail->returned_quantity : 0,
                            'uom_id' => isset($return_receipt_detail->uom->uom_id) ? $return_receipt_detail->uom->uom_id : null,
                            'uom_name' => isset($return_receipt_detail->uom->uom_name) ? $return_receipt_detail->uom->uom_name : null,
                            'sku_status_id' => isset($return_receipt_detail->skuStatus->sku_status_id) ? $return_receipt_detail->skuStatus->sku_status_id : null,
                            'sku_status_name' => isset($return_receipt_detail->skuStatus->status_name) ? $return_receipt_detail->skuStatus->status_name : null,
                            'amount' => $return_receipt_detail->amount != "" ? number_format($return_receipt_detail->amount, 2, '.', '') : number_format(0, 2, '.', ''),
                            'remarks' => isset($return_receipt_detail->remarks) ? $return_receipt_detail->remarks : null,
                        );
                    }
                } else if (isset($_POST['ReturnMdseDetail'])) {
                    $return_mdse_detail->attributes = $_POST['ReturnMdseDetail'];
                    $return_mdse_detail->company_id = Yii::app()->user->company_id;
                    $return_mdse_detail->created_by = Yii::app()->user->name;
                    unset($return_mdse_detail->created_date);

                    $validatedReturnMdseDetail = CActiveForm::validate($return_mdse_detail);

                    if ($validatedReturnMdseDetail != '[]') {

                        $data['error'] = $validatedReturnMdseDetail;
                        $data["type"] = "danger";
                        $data['message'] = 'Unable to process';
                    } else {

                        $c = new CDbCriteria;
                        $c->compare('t.company_id', Yii::app()->user->company_id);
                        $c->compare('t.sku_id', $return_mdse_detail->sku_id);
                        $c->with = array('brand', 'company', 'defaultUom', 'defaultZone');
                        $sku_details = Sku::model()->find($c);

                        $data['message'] = 'Added Item Successfully';
                        $data['success'] = true;

                        $data['details'] = array(
                            "sku_id" => isset($sku_details->sku_id) ? $sku_details->sku_id : null,
                            "sku_code" => isset($sku_details->sku_code) ? $sku_details->sku_code : null,
                            "sku_description" => isset($sku_details->description) ? $sku_details->description : null,
                            'brand_name' => isset($sku_details->brand->brand_name) ? $sku_details->brand->brand_name : null,
                            'unit_price' => $return_mdse_detail->unit_price != "" ? number_format($return_mdse_detail->unit_price, 2, '.', '') : number_format(0, 2, '.', ''),
                            'batch_no' => isset($return_mdse_detail->batch_no) ? $return_mdse_detail->batch_no : null,
                            'expiration_date' => isset($return_mdse_detail->expiration_date) ? $return_mdse_detail->expiration_date : null,
                            'returned_quantity' => $return_mdse_detail->returned_quantity != "" ? $return_mdse_detail->returned_quantity : 0,
                            'uom_id' => isset($return_mdse_detail->uom->uom_id) ? $return_mdse_detail->uom->uom_id : null,
                            'uom_name' => isset($return_mdse_detail->uom->uom_name) ? $return_mdse_detail->uom->uom_name : null,
                            'sku_status_id' => isset($return_mdse_detail->skuStatus->sku_status_id) ? $return_mdse_detail->skuStatus->sku_status_id : null,
                            'sku_status_name' => isset($return_mdse_detail->skuStatus->status_name) ? $return_mdse_detail->skuStatus->status_name : null,
                            'amount' => $return_mdse_detail->amount != "" ? number_format($return_mdse_detail->amount, 2, '.', '') : number_format(0, 2, '.', ''),
                            'remarks' => isset($return_mdse_detail->remarks) ? $return_mdse_detail->remarks : null,
                            "inventory_id" => isset($return_mdse_detail->inventory_id) ? $return_mdse_detail->inventory_id : null,
                            "source_zone_id" => isset($return_mdse_detail->source_zone_id) ? $return_mdse_detail->source_zone_id : null,
                        );
                    }
                }
            }

            echo json_encode($data);
            Yii::app()->end();
        }

        $uom = CHtml::listData(UOM::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'uom_name ASC')), 'uom_id', 'uom_name');
        $sku_status = CHtml::listData(SkuStatus::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'status_name ASC')), 'sku_status_id', 'status_name');

        $returnable->return_receipt_no = "R" . date("YmdHis");
        $return_receipt->return_receipt_no = "RR" . date("YmdHis");
        $return_mdse->return_mdse_no = "MDSE" . date("YmdHis");

        $this->render('create', array(
            'returnable' => $returnable,
            'return_from_list' => $return_from_list,
            'zone_list' => $zone_list,
            'salesoffice_list' => $salesoffice_list,
            'employee' => $employee,
            'return_receipt' => $return_receipt,
            'return_receipt_detail' => $return_receipt_detail,
            'uom' => $uom,
            'sku_status' => $sku_status,
            'isReturnable' => isset($_GET['param']['isReturnable']) && $_GET['param']['isReturnable'] != "" ? true : false,
            'isReturnReceipt' => isset($_GET['param']['isReturnReceipt']) && $_GET['param']['isReturnReceipt'] != "" ? true : false,
            'isReturnMdse' => isset($_GET['param']['isReturnMdse']) && $_GET['param']['isReturnMdse'] != "" ? true : false,
            'sku_id' => "",
            'return_mdse' => $return_mdse,
            'return_mdse_detail' => $return_mdse_detail,
            'return_to_list' => $return_to_list,
            'sku' => $sku,
            'warehouse_list' => $warehouse_list,
            'form' => isset($_GET['param']['returns_form']) ? $_GET['param']['returns_form'] : 0,
            'attachment' => $attachment,
            'detail_id' => "",
        ));
    }

    function saveReturnables($model, $post, $validatedModel, $data) {

        if ($post['Returnable']['receive_return_from'] != "") {
            $selected_source = $post['Returnable']['receive_return_from'];

            $returnable_label = str_replace(" ", "_", Returnable::RETURNABLE_LABEL) . "_";
            $source_id = Returnable::model()->validateReturnFrom($model, $selected_source, $returnable_label);
            $model->receive_return_from_id = $source_id;
        }

        $validatedModel_arr = (array) json_decode($validatedModel);
        $model_errors = json_encode(array_merge($validatedModel_arr, $model->getErrors()));

        if ($model_errors != '[]') {

            $data['error'] = $model_errors;
            $data['message'] = 'Unable to process';
            $data['success'] = false;
            $data["type"] = "danger";
        } else {

            if ($data['form'] == "print") {

                $data['print'] = $post;
                $data['success'] = true;
            } else {

                $transaction_details = isset($post['transaction_details']) ? $post['transaction_details'] : array();

                if ($model->createReturnable($transaction_details)) {
//                    $data['returns_id'] = Yii::app()->session['returns_id_create_session'];
//                    unset(Yii::app()->session['returns_id_create_session']);
                    $data['message'] = 'Successfully created';
                    $data['success'] = true;
                } else {
                    $data['message'] = 'Unable to process';
                    $data['success'] = false;
                    $data["type"] = "danger";
                }
            }
        }

        return $data;
    }

    public function saveReturnReceipt($model, $post, $validatedModel, $data) {

        if ($post['ReturnReceipt']['receive_return_from'] != "") {
            $selected_source = $post['ReturnReceipt']['receive_return_from'];

            $return_receipt_label = str_replace(" ", "_", ReturnReceipt::RETURN_RECEIPT_LABEL) . "_";
            $source_id = Returnable::model()->validateReturnFrom($model, $selected_source, $return_receipt_label);
            $model->receive_return_from_id = $source_id;
        }

        $validatedModel_arr = (array) json_decode($validatedModel);
        $model_errors = json_encode(array_merge($validatedModel_arr, $model->getErrors()));

        if ($model_errors != '[]') {

            $data['error'] = $model_errors;
            $data['message'] = 'Unable to process';
            $data['success'] = false;
            $data["type"] = "danger";
        } else {

            if ($data['form'] == "print_return_receipt") {

                $data['print'] = $post;
                $data['success'] = true;
            } else {

                $transaction_details = isset($post['transaction_details']) ? $post['transaction_details'] : array();

                $saved = $model->createReturnReceipt($transaction_details);

                if ($saved['success']) {
                    $data['return_receipt_id'] = $saved['header_data']->return_receipt_id;
                    $data['message'] = 'Successfully created';
                    $data['success'] = true;
                } else {
                    $data['message'] = 'Unable to process';
                    $data['success'] = false;
                    $data["type"] = "danger";
                }
            }
        }

        return $data;
    }

    public function saveReturnMdse($model, $post, $validatedModel, $data) {

        if ($post['ReturnMdse']['return_to'] != "") {
            $selected_destination = $post['ReturnMdse']['return_to'];

            $return_mdse_label = str_replace(" ", "_", ReturnMdse::RETURN_MDSE_LABEL) . "_";
            $destination_id = ReturnMdse::model()->validateReturnTo($model, $selected_destination, $return_mdse_label);
            $model->return_to_id = $destination_id;
        }

        $validatedModel_arr = (array) json_decode($validatedModel);
        $model_errors = json_encode(array_merge($validatedModel_arr, $model->getErrors()));

        if ($model_errors != '[]') {

            $data['error'] = $model_errors;
            $data['message'] = 'Unable to process';
            $data['success'] = false;
            $data["type"] = "danger";
        } else {

            if ($data['form'] == "print_return_mdse") {

                $data['print'] = $post;
                $data['success'] = true;
            } else {

                $transaction_details = isset($post['transaction_details']) ? $post['transaction_details'] : array();

                $saved = $model->createReturnMdse($transaction_details);

                if ($saved['success']) {
                    $data['return_mdse_id'] = $saved['header_data']->return_mdse_id;
                    $data['message'] = 'Successfully created';
                    $data['success'] = true;
                } else {
                    $data['message'] = 'Unable to process';
                    $data['success'] = false;
                    $data["type"] = "danger";
                }
            }
        }

        return $data;
    }

    public function actionGetDetailsByReturnInvID($returns_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND returns_id = '" . $returns_id . "'";
        $returns_details = ReturnableDetail::model()->findAll($c);

        $output = array();
        foreach ($returns_details as $key => $value) {
            $row = array();

            $status = Inventory::model()->status($value->status);

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['returns_detail_id'] = $value->returns_detail_id;
            $row['returns_id'] = $value->returns_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['quantity_issued'] = $value->quantity_issued;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['status'] = $status;
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $row['links'] = "";

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Returnable');

        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $this->layout = '//layouts/column1';
        $this->pageTitle = 'Manage Returns';

        $model = new Returnable('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Returnable']))
            $model->attributes = $_GET['Returnable'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'returns-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function queryInfraDetails($source, $dr_no = "") {

        $source_arr = Returnable::model()->getListReturnFrom();
        $data = array();

        $c = new CDbCriteria;

        if ($source == $source_arr[0]['value']) {

            if ($dr_no != "") {
                $dr_no = " AND outgoingInventory.dr_no = '" . $dr_no . "'";
            }

            $c->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND sku.type = '" . Sku::INFRA . "'" . $dr_no;
            $c->with = array("outgoingInventory", "sku");
            $data["details"] = OutgoingInventoryDetail::model()->findAll($c);
            $data["header"] = $source;
        } else if ($source == $source_arr[1]['value']) {

            if ($dr_no != "") {
                $dr_no = " AND outgoingInventory.dr_no = '" . $dr_no . "'";
            }

            $c2 = new CDbCriteria;
            $c2->condition = "t.company_id = '" . Yii::app()->user->company_id . "'";
            $c2->with = array("salesOffice", "zone");
            $employee = Employee::model()->findAll($c2);

            $zones = "'',";
            foreach ($employee as $k => $v) {
                $zones .= "'" . $v->zone->zone_id . "',";
            }

            $c->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND outgoingInventory.destination_zone_id IN (" . substr($zones, 0, -1) . ") AND sku.type = '" . Sku::INFRA . "'" . $dr_no;
            $c->with = array("outgoingInventory", "sku");
            $data["details"] = OutgoingInventoryDetail::model()->findAll($c);
            $data["header"] = $source;
        } else if ($source == $source_arr[2]['value']) {

            if ($dr_no != "") {
                $dr_no = " AND customerItem.dr_no = '" . $dr_no . "'";
            }

            $c->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND sku.type = '" . Sku::INFRA . "'" . $dr_no;
            $c->with = array("customerItem", "sku");
            $data['details'] = CustomerItemDetail::model()->findAll($c);
            $data["header"] = $source;
        } else {

            $data = array();
        }

        return $data;
    }

    public function actionLoadReferenceDRNos($source) {

        $data = $this->queryInfraDetails($source);

        $source_arr = Returnable::model()->getListReturnFrom();

        $return = array();
        if (count($data) > 0) {
            $row['id'] = "";
            $row['text'] = "--";
            $return[] = $row;

            $dr_nos = "";
            $dr_nos_arr = array();
            if ($data['header'] == $source_arr[0]['value'] || $data['header'] == $source_arr[1]['value']) {
                foreach ($data['details'] as $key => $val) {
                    $row = array();

                    if (!in_array($val->outgoingInventory->dr_no, $dr_nos_arr)) {
                        array_push($dr_nos_arr, $val->outgoingInventory->dr_no);
                        $row['id'] = $val->outgoingInventory->dr_no;
                        $row['text'] = $val->outgoingInventory->dr_no;

                        $return[] = $row;
                    }
                }
            } else if ($data['header'] == $source_arr[2]['value']) {
                foreach ($data['details'] as $key => $val) {
                    $row = array();

                    if (!in_array($val->customerItem->dr_no, $dr_nos_arr)) {
                        array_push($dr_nos_arr, $val->customerItem->dr_no);
                        $row['id'] = $val->customerItem->dr_no;
                        $row['text'] = $val->customerItem->dr_no;

                        $return[] = $row;
                    }
                }
            }
        }

        echo json_encode($return);
    }

    public function actionInfraLoadDetailsByDRNo($source, $dr_no) {
        $data = array();

        if ($dr_no != "") {
            $data = $this->queryInfraDetails($source, $dr_no);
        }

        $source_arr = Returnable::model()->getListReturnFrom();

        $return = array();
        $return["id"] = "";
        if (count($data) > 0) {

            if ($data['header'] == $source_arr[0]['value']) {
                foreach ($data['details'] as $key => $value) {
                    $row = array();

                    $return["id"] = $value->outgoingInventory->zone->salesOffice->sales_office_id;

                    $row['outgoing_inventory_detail_id'] = $value->outgoing_inventory_detail_id;
                    $row['outgoing_inventory_id'] = $value->outgoing_inventory_id;
                    $row['inventory_id'] = $value->inventory_id;
                    $row['batch_no'] = $value->batch_no;
                    $row['sku_id'] = $value->sku_id;
                    $row['source_zone_id'] = $value->source_zone_id;
                    $row['source_zone_name'] = isset($value->zone->zone_name) ? $value->zone->zone_name : null;
                    $row['unit_price'] = $value->unit_price;
                    $row['expiration_date'] = $value->expiration_date;
                    $row['planned_quantity'] = $value->quantity_issued;
                    $row['quantity_received'] = "0";
                    $row['amount'] = $value->amount;
                    $row['return_date'] = $value->return_date;
                    $row['remarks'] = $value->remarks;
                    $row['sku_id'] = isset($value->sku->sku_id) ? $value->sku->sku_id : null;
                    $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
                    $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
                    $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
                    $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
                    $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
                    $row['status'] = $value->status;
                    $row['uom_id'] = $value->uom_id;
                    $row['uom_name'] = $value->uom->uom_name;
                    $row['sku_status_id'] = $value->sku_status_id;
                    $row['po_no'] = $value->po_no;
                    $row['pr_no'] = $value->pr_no;
                    $row['pr_date'] = $value->pr_date;
                    $row['plan_arrival_date'] = $value->plan_arrival_date;

                    $return['transaction_details'][] = $row;
                }
            } else if ($data['header'] == $source_arr[1]['value']) {
                foreach ($data['details'] as $key => $value) {
                    $row = array();

                    $employee = Employee::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "default_zone_id" => $value->outgoingInventory->destination_zone_id));

                    $return["id"] = $employee->employee_id;

                    $row['outgoing_inventory_detail_id'] = $value->outgoing_inventory_detail_id;
                    $row['outgoing_inventory_id'] = $value->outgoing_inventory_id;
                    $row['inventory_id'] = $value->inventory_id;
                    $row['batch_no'] = $value->batch_no;
                    $row['sku_id'] = $value->sku_id;
                    $row['source_zone_id'] = $value->source_zone_id;
                    $row['source_zone_name'] = isset($value->zone->zone_name) ? $value->zone->zone_name : null;
                    $row['unit_price'] = $value->unit_price;
                    $row['expiration_date'] = $value->expiration_date;
                    $row['planned_quantity'] = $value->quantity_issued;
                    $row['quantity_received'] = "0";
                    $row['amount'] = $value->amount;
                    $row['return_date'] = $value->return_date;
                    $row['remarks'] = $value->remarks;
                    $row['sku_id'] = isset($value->sku->sku_id) ? $value->sku->sku_id : null;
                    $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
                    $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
                    $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
                    $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
                    $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
                    $row['status'] = $value->status;
                    $row['uom_id'] = $value->uom_id;
                    $row['uom_name'] = $value->uom->uom_name;
                    $row['sku_status_id'] = $value->sku_status_id;
                    $row['po_no'] = $value->po_no;
                    $row['pr_no'] = $value->pr_no;
                    $row['pr_date'] = $value->pr_date;
                    $row['plan_arrival_date'] = $value->plan_arrival_date;

                    $return['transaction_details'][] = $row;
                }
            } else if ($data['header'] == $source_arr[2]['value']) {
                foreach ($data['details'] as $key => $value) {
                    $row = array();

                    $return["id"] = $value->customerItem->poi->poi_id;

                    $row['customer_item_id'] = $value->customer_item_id;
                    $row['customer_item_id'] = $value->customer_item_id;
                    $row['inventory_id'] = $value->inventory_id;
                    $row['batch_no'] = $value->batch_no;
                    $row['sku_id'] = $value->sku_id;
                    $row['source_zone_id'] = $value->source_zone_id;
                    $row['source_zone_name'] = isset($value->zone->zone_name) ? $value->zone->zone_name : null;
                    $row['unit_price'] = $value->unit_price;
                    $row['expiration_date'] = $value->expiration_date;
                    $row['planned_quantity'] = $value->quantity_issued;
                    $row['quantity_received'] = "0";
                    $row['amount'] = $value->amount;
                    $row['remarks'] = $value->remarks;
                    $row['sku_id'] = isset($value->sku->sku_id) ? $value->sku->sku_id : null;
                    $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
                    $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
                    $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
                    $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
                    $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
                    $row['status'] = $value->status;
                    $row['uom_id'] = $value->uom_id;
                    $row['uom_name'] = $value->uom->uom_name;
                    $row['sku_status_id'] = $value->sku_status_id;
                    $row['po_no'] = $value->po_no;
                    $row['pr_no'] = $value->pr_no;
                    $row['pr_date'] = $value->pr_date;
                    $row['plan_arrival_date'] = $value->plan_arrival_date;

                    $return['transaction_details'][] = $row;
                }
            }
        }

        echo json_encode($return);
    }

    public function actionCreateReturnable($dr_no, $sku_id) {
        $this->layout = '//layouts/column1';
        $this->pageTitle = 'Returns';

        $exist_dr_no = Inventory::model()->checkIfReturnableDRNoIsExists(Yii::app()->user->company_id, $dr_no, $sku_id);

        if (!$exist_dr_no['success']) {
            throw new CHttpException(403, "You are not authorized to perform this action.");
        }

        $returnable = new Returnable;
        $return_receipt = new ReturnReceipt;
        $return_receipt_detail = new ReturnReceiptDetail;
        $return_mdse = new ReturnMdse;
        $return_mdse_detail = new ReturnMdseDetail;
        $sku = new Sku;
        $attachment = new Attachment;

        $returnable_header_data = Returnable::model()->getReturnableSource(Yii::app()->user->company_id, $dr_no, $exist_dr_no, $returnable);

        $return_from_list = CHtml::listData(Returnable::model()->getListReturnFrom(), 'value', 'title');
        $return_to_list = CHtml::listData(ReturnMdse::model()->getListReturnTo(), 'value', 'title');
        $zone_list = CHtml::listData(Zone::model()->findAll(array("condition" => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'zone_name ASC')), 'zone_id', 'zone_name');
        $salesoffice_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');
        $warehouse_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '" AND distributor_id = ""', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');

        $c = new CDbCriteria;
        $c->select = new CDbExpression('t.*, CONCAT(t.first_name, " ", t.last_name) AS fullname');
        $c->condition = 'company_id = "' . Yii::app()->user->company_id . '"';
        $c->order = 'fullname ASC';
        $employee = CHtml::listData(Employee::model()->findAll($c), 'employee_id', 'fullname', 'employee_code');

        if (Yii::app()->request->isPostRequest && Yii::app()->request->isAjaxRequest) {

            $data = array();
            $data['success'] = false;
            $data["type"] = "success";

            if ($_POST['form'] == "transaction" || $_POST['form'] == "print_returnable") {
                $data['form'] = $_POST['form'];

                if (isset($_POST['Returnable'])) {

                    $returnable->attributes = $_POST['Returnable'];
                    $returnable->company_id = Yii::app()->user->company_id;
                    $returnable->created_by = Yii::app()->user->name;
                    $returnable->date_returned = $returnable->transaction_date;
                    unset($returnable->created_date);

                    $validatedReturnable = CActiveForm::validate($returnable);

                    if ($_POST['Returnable']['receive_return_from'] != "") {
                        $selected_source = $_POST['Returnable']['receive_return_from'];

                        $returnable_label = str_replace(" ", "_", Returnable::RETURNABLE_LABEL) . "_";
                        $source_id = Returnable::model()->validateReturnFrom($returnable, $selected_source, $returnable_label);
                        $returnable->receive_return_from_id = $source_id;
                    }

                    $validatedModel_arr = (array) json_decode($validatedReturnable);
                    $model_errors = json_encode(array_merge($validatedModel_arr, $returnable->getErrors()));

                    if ($model_errors != '[]') {

                        $data['error'] = $model_errors;
                        $data['message'] = 'Unable to process';
                        $data['success'] = false;
                        $data["type"] = "danger";
                    } else {

                        if ($data['form'] == "print_returnable") {

                            $data['print'] = $_POST;
                            $data['success'] = true;
                        } else {

                            $transaction_details = isset($_POST['transaction_details']) ? $_POST['transaction_details'] : array();

                            $saved = $returnable->createReturnable($transaction_details);

                            if ($saved['success']) {
                                $data['returnable_id'] = $saved['header_data']->returnable_id;
                                $data['message'] = 'Successfully created';
                                $data['success'] = true;
                            } else {
                                $data['message'] = 'Unable to process';
                                $data['success'] = false;
                                $data["type"] = "danger";
                            }
                        }
                    }
                }
            }

            echo json_encode($data);
            Yii::app()->end();
        }

        $uom = CHtml::listData(UOM::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'uom_name ASC')), 'uom_id', 'uom_name');
        $sku_status = CHtml::listData(SkuStatus::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'status_name ASC')), 'sku_status_id', 'status_name');

        $returnable->reference_dr_no = $dr_no;
        $returnable->return_receipt_no = "R" . date("YmdHis");

        $this->render('create', array(
            'returnable' => $returnable,
            'return_from_list' => $return_from_list,
            'zone_list' => $zone_list,
            'salesoffice_list' => $salesoffice_list,
            'employee' => $employee,
            'return_receipt' => $return_receipt,
            'return_receipt_detail' => $return_receipt_detail,
            'uom' => $uom,
            'sku_status' => $sku_status,
            'isReturnable' => true,
            'isReturnReceipt' => false,
            'isReturnMdse' => false,
            'sku_id' => $sku_id,
            'return_mdse' => $return_mdse,
            'return_mdse_detail' => $return_mdse_detail,
            'return_to_list' => $return_to_list,
            'warehouse_list' => $warehouse_list,
            'sku' => $sku,
            'form' => 1,
            'attachment' => $attachment,            
            'detail_id' => "",
        ));
    }

    public function actionInfraLoadDetailsBySelectedDRNo($dr_no, $sku_id) {

        $data = array();
        $return = array();

        $infra_details = Returnable::model()->queryReturnInfraDetails(Yii::app()->user->company_id, $dr_no, $sku_id);

        if ($infra_details['source_header'] == IncomingInventory::INCOMING_LABEL) {

            foreach ($infra_details['source_details'] as $key => $value) {
                $row = array();

                $row['incoming_inventory_detail_id'] = $value['incoming_inventory_detail_id'];
                $row['incoming_inventory_id'] = $value['incoming_inventory_id'];
                $row['inventory_id'] = $value['inventory_id'];
                $row['batch_no'] = $value['batch_no'];
                $row['sku_id'] = $value['sku_id'];
                $row['source_zone_id'] = $value['source_zone_id'];
                $row['source_zone_name'] = isset($value['zone_name']) ? $value['zone_name'] : null;
                $row['unit_price'] = $value['unit_price'];
                $row['expiration_date'] = $value['expiration_date'];
                $row['quantity_received'] = $value['quantity_received'];
                $row['return_quantity'] = "0";
                $row['amount'] = $value['amount'];
                $row['return_date'] = $value['return_date'];
                $row['remarks'] = $value['remarks'];
                $row['sku_id'] = isset($value['sku_id']) ? $value['sku_id'] : null;
                $row['sku_code'] = isset($value['sku_code']) ? $value['sku_code'] : null;
                $row['sku_description'] = isset($value['description']) ? $value['description'] : null;
                $row['brand_name'] = isset($value['brand_name']) ? $value['brand_name'] : null;
                $row['sku_category'] = isset($value['type']) ? $value['type'] : null;
                $row['sku_sub_category'] = isset($value['sub_type']) ? $value['sub_type'] : null;
                $row['status'] = "<span></span>";
                $row['uom_id'] = $value['uom_id'];
                $row['uom_name'] = $value['uom_name'];
                $row['sku_status_id'] = $value['sku_status_id'];
                $row['po_no'] = $value['po_no'];
                $row['pr_no'] = $value['pr_no'];
                $row['pr_date'] = $value['pr_date'];
                $row['plan_arrival_date'] = $value['plan_arrival_date'];
                $row['remaining_qty'] = $value['remaining_qty'];

                $return['transaction_details'][] = $row;
            }
        } else {

            foreach ($infra_details['source_details'] as $key => $value) {
                $row = array();

                $row['customer_item_id'] = $value['customer_item_id'];
                $row['customer_item_id'] = $value['customer_item_id'];
                $row['inventory_id'] = $value['inventory_id'];
                $row['batch_no'] = $value['batch_no'];
                $row['sku_id'] = $value['sku_id'];
                $row['source_zone_id'] = $value['source_zone_id'];
                $row['source_zone_name'] = isset($value['zone_name']) ? $value['zone_name'] : null;
                $row['unit_price'] = $value['unit_price'];
                $row['expiration_date'] = $value['expiration_date'];
                $row['quantity_received'] = $value['quantity_issued'];
                $row['return_quantity'] = "0";
                $row['amount'] = $value['amount'];
                $row['remarks'] = $value['remarks'];
                $row['sku_id'] = isset($value['sku_id']) ? $value['sku_id'] : null;
                $row['sku_code'] = isset($value['sku_code']) ? $value['sku_code'] : null;
                $row['sku_description'] = isset($value['description']) ? $value['description'] : null;
                $row['brand_name'] = isset($value['brand_name']) ? $value['brand_name'] : null;
                $row['sku_category'] = isset($value['type']) ? $value['type'] : null;
                $row['sku_sub_category'] = isset($value['sub_type']) ? $value['sub_type'] : null;
                $row['status'] = "<span></span>";
                $row['uom_id'] = $value['uom_id'];
                $row['uom_name'] = $value['uom_name'];
                $row['sku_status_id'] = $value['sku_status_id'];
                $row['po_no'] = $value['po_no'];
                $row['pr_no'] = $value['pr_no'];
                $row['pr_date'] = $value['pr_date'];
                $row['plan_arrival_date'] = $value['plan_arrival_date'];
                $row['remaining_qty'] = $value['remaining_qty'];

                $return['transaction_details'][] = $row;
            }
        }

        echo json_encode($return);
    }

    public function actionReturnableData() {

        Returnable::model()->search_string = $_GET['search']['value'] != "" ? $_GET['search']['value'] : null;

        $dataProvider = Returnable::model()->data($_GET['order'][0]['column'], $_GET['order'][0]['dir'], $_GET['length'], $_GET['start'], $_GET['columns']);

        $count = Returnable::model()->countByAttributes(array('company_id' => Yii::app()->user->company_id));

        $output = array(
            "draw" => intval($_GET['draw']),
            "recordsTotal" => $count,
            "recordsFiltered" => $dataProvider->totalItemCount,
            "data" => array()
        );

        foreach ($dataProvider->getData() as $key => $value) {
            $row = array();

            $status = Inventory::model()->status($value->status);

            $row['returnable_id'] = $value->returnable_id;
            $row['return_receipt_no'] = $value->return_receipt_no;
            $row['reference_dr_no'] = $value->reference_dr_no;
            $row['receive_return_from'] = $value->receive_return_from;
            $row['receive_return_from_id'] = $value->receive_return_from_id;
            $row['transaction_date'] = $value->transaction_date;
            $row['date_returned'] = $value->date_returned;
            $row['destination_zone_id'] = $value->destination_zone_id;
            $row['remarks'] = $value->remarks;
            $row['total_amount'] = "&#x20B1;" . number_format($value->total_amount, 2, '.', ',');
            $row['created_date'] = $value->created_date;
            $row['created_by'] = $value->created_by;
            $row['updated_date'] = $value->updated_date;
            $row['updated_by'] = $value->updated_by;
            $row['status'] = $status;

            $source = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $value->receive_return_from, $value->receive_return_from_id);

            $row['source_name'] = $source['source_name'];
            $row['destination_zone_name'] = isset($value->zone->zone_name) ? $value->zone->zone_name : "";

            $row['links'] = '<a class="btn btn-sm btn-default view" title="View" href="' . $this->createUrl('/inventory/Returns/returnableView', array('id' => $value->returnable_id)) . '">
                                <i class="glyphicon glyphicon-eye-open"></i>
                            </a>
                            <a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnableDelete', array('id' => $value->returnable_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionGetReturnableDetailsByReturnableID($returnable_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND returnable_id = '" . $returnable_id . "'";
        $returnable_details = ReturnableDetail::model()->findAll($c);

        $output = array();
        foreach ($returnable_details as $key => $value) {
            $row = array();

            $status = Inventory::model()->status($value->status);

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['returnable_detail_id'] = $value->returnable_detail_id;
            $row['returnable_id'] = $value->returnable_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['quantity_issued'] = $value->quantity_issued;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['status'] = $status;
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $row['links'] = '<a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnableDetailDelete', array('returnable_detail_id' => $value->returnable_detail_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionAttachmentPreview($id) {
        $c = new CDbCriteria;
        $c->compare("company_id", Yii::app()->user->company_id);
        $c->compare("transaction_id", $id);
        $attachment = Attachment::model()->findAll($c);

        $output = array();
        foreach ($attachment as $key => $value) {
            $ext = new SplFileInfo($value->file_name);

            $icon = "";
            if ($ext->getExtension() == "jpg" || $ext->getExtension() == "jpeg" || $ext->getExtension() == "gif" || $ext->getExtension() == "png") {
                $icon = CHtml::image('images' . DIRECTORY_SEPARATOR . "icons" . DIRECTORY_SEPARATOR . "picture.png", "Image", array("width" => 30));
            } else if ($ext->getExtension() == "pdf") {
                $icon = CHtml::image('images' . DIRECTORY_SEPARATOR . "icons" . DIRECTORY_SEPARATOR . "pdf.png", "Image", array("width" => 30));
            } else if ($ext->getExtension() == "docx" || $ext->getExtension() == "doc") {
                $icon = CHtml::image('images' . DIRECTORY_SEPARATOR . "icons" . DIRECTORY_SEPARATOR . "doc.png", "Image", array("width" => 30));
            } else if ($ext->getExtension() == "xls" || $ext->getExtension() == "xlsx") {
                $icon = CHtml::image('images' . DIRECTORY_SEPARATOR . "icons" . DIRECTORY_SEPARATOR . "xls.png", "Image", array("width" => 30));
            }

            $row = array();
            $row['file_name'] = $icon . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $value->file_name;

            $row['links'] = '<a class="btn btn-sm btn-default download_attachment" title="Delete" href="' . $this->createUrl('/inventory/returns/downloadAttachment', array('id' => $value->attachment_id)) . '">
                                <i class="glyphicon glyphicon-download"></i>
                            </a>'
                    . '&nbsp;<a class="btn btn-sm btn-default delete_attachment" title="Delete" href="' . $this->createUrl('/inventory/returns/deleteAttachment', array('attachment_id' => $value->attachment_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function loadReturnableModel($id) {
        $model = Returnable::model()->findByAttributes(array('returnable_id' => $id, 'company_id' => Yii::app()->user->company_id));
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');

        return $model;
    }

    public function loadReturnReceiptModel($id) {
        $model = ReturnReceipt::model()->findByAttributes(array('return_receipt_id' => $id, 'company_id' => Yii::app()->user->company_id));
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');

        return $model;
    }

    public function loadReturnMdseModel($id) {
        $model = ReturnMdse::model()->findByAttributes(array('return_mdse_id' => $id, 'company_id' => Yii::app()->user->company_id));
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');

        return $model;
    }

    public function actionReturnableDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                // delete returnable details by returnable_id
                ReturnableDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND returnable_id = " . $id);
                // delete attachment by returnable_id as transaction_id
//                $this->deleteAttachmentByOutgoingInvID($id);
                // we only allow deletion via POST request
                $this->loadReturnableModel($id)->delete();

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    if (!isset($_GET['ajax'])) {
                        Yii::app()->user->setFlash('danger', "Unable to delete");
                        $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view', 'id' => $id));
                    } else {
                        echo "1451";
                        exit;
                    }
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionReturnReceiptData() {

        ReturnReceipt::model()->search_string = $_GET['search']['value'] != "" ? $_GET['search']['value'] : null;

        $dataProvider = ReturnReceipt::model()->data($_GET['order'][0]['column'], $_GET['order'][0]['dir'], $_GET['length'], $_GET['start'], $_GET['columns']);

        $count = ReturnReceipt::model()->countByAttributes(array('company_id' => Yii::app()->user->company_id));

        $output = array(
            "draw" => intval($_GET['draw']),
            "recordsTotal" => $count,
            "recordsFiltered" => $dataProvider->totalItemCount,
            "data" => array()
        );

        foreach ($dataProvider->getData() as $key => $value) {
            $row = array();
            $row['return_receipt_id'] = $value->return_receipt_id;
            $row['return_receipt_no'] = $value->return_receipt_no;
            $row['receive_return_from'] = $value->receive_return_from;
            $row['receive_return_from_id'] = $value->receive_return_from_id;
            $row['reference_dr_no'] = $value->reference_dr_no;
            $row['transaction_date'] = $value->transaction_date;
            $row['date_returned'] = $value->date_returned;
            $row['destination_zone_id'] = $value->destination_zone_id;
            $row['remarks'] = $value->remarks;
            $row['total_amount'] = $value->total_amount;
            $row['created_date'] = $value->created_date;
            $row['created_by'] = $value->created_by;
            $row['updated_date'] = $value->updated_date;
            $row['updated_by'] = $value->updated_by;

            $source = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $value->receive_return_from, $value->receive_return_from_id);

            $row['source_name'] = $source['source_name'];
            $row['destination_zone_name'] = isset($value->zone->zone_name) ? $value->zone->zone_name : "";

            $row['links'] = '<a class="btn btn-sm btn-default view" title="View" href="' . $this->createUrl('/inventory/Returns/returnReceiptView', array('id' => $value->return_receipt_id)) . '">
                                <i class="glyphicon glyphicon-eye-open"></i>
                            </a>
                            <a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnReceiptDelete', array('id' => $value->return_receipt_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionGetReturnReceiptDetailsByReturnReceiptID($return_receipt_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND return_receipt_id = '" . $return_receipt_id . "'";
        $return_receipt_details = ReturnReceiptDetail::model()->findAll($c);

        $output = array();
        foreach ($return_receipt_details as $key => $value) {
            $row = array();

            $status = Inventory::model()->status($value->status);

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['return_receipt_detail_id'] = $value->return_receipt_detail_id;
            $row['return_receipt_id'] = $value->return_receipt_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['status'] = $status;
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $row['links'] = '<a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnReceiptDetailDelete', array('return_receipt_detail_id' => $value->return_receipt_detail_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionReturnReceiptDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                // delete return receipt details by returnable_id
                ReturnReceiptDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND return_receipt_id = " . $id);
                // delete attachment by return_receipt_id as transaction_id
                $this->deleteAttachmentByTransactionID($id, ReturnReceipt::RETURN_RECEIPT_LABEL);
                // we only allow deletion via POST request
                $this->loadReturnReceiptModel($id)->delete();

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    if (!isset($_GET['ajax'])) {
                        Yii::app()->user->setFlash('danger', "Unable to delete");
                        $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view', 'id' => $id));
                    } else {
                        echo "1451";
                        exit;
                    }
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionReturnableView($id) {
        $model = $this->loadReturnableModel($id);

        $this->pageTitle = "View Returnable";
        $this->menu = array(
            array('label' => "Delete Returnable", 'url' => '#', 'linkOptions' => array('submit' => array('returnableDelete', 'id' => $model->returnable_id), 'confirm' => 'Are you sure you want to delete this item?')),
            array('label' => "Manage Returns", 'url' => array('admin')),
        );

        $returnable_detail = ReturnableDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "returnable_id" => $model->returnable_id));

        $pr_nos = "";
        $pr_no_arr = array();
        $po_nos = "";
        $po_no_arr = array();
        foreach ($returnable_detail as $key => $val) {

            if ($val->pr_no != "") {
                if (!in_array($val->pr_no, $pr_no_arr)) {
                    array_push($pr_no_arr, $val->pr_no);
                    $pr_nos .= $val->pr_no . ",";
                }
            }

            if ($val->po_no != "") {
                if (!in_array($val->po_no, $po_no_arr)) {
                    array_push($po_no_arr, $val->po_no);
                    $po_nos .= $val->po_no . ",";
                }
            }
        }

        $nos = array();
        $nos['pr_no'] = substr($pr_nos, 0, -1);
        $nos['po_no'] = substr($po_nos, 0, -1);

        $source = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $model->receive_return_from, $model->receive_return_from_id);

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND zones.zone_id = '" . $model->destination_zone_id . "'";
        $c1->with = array("zones");
        $destination_sales_office = SalesOffice::model()->find($c1);

        $destination = array();
        $destination['zone_name'] = $model->zone->zone_name;
        $destination['destination_sales_office_name'] = isset($destination_sales_office->sales_office_name) ? $destination_sales_office->sales_office_name : "";
        $destination['contact_person'] = "";
        $destination['contact_no'] = "";
        $destination['address'] = isset($destination_sales_office->address1) ? $destination_sales_office->address1 : "";


        $this->render('_view_returnable', array(
            'model' => $model,
            'destination' => $destination,
            'source' => $source,
            'nos' => $nos,
        ));
    }

    public function actionGetDetailsByReturnableID($returnable_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND returnable_id = '" . $returnable_id . "'";
        $returnable_details = ReturnableDetail::model()->findAll($c);

        $output = array();
        foreach ($returnable_details as $key => $value) {
            $row = array();

            $status = Inventory::model()->status($value->status);

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['returnable_detail_id'] = $value->returnable_detail_id;
            $row['returnable_id'] = $value->returnable_id;
            $row['quantity_issued'] = $value->quantity_issued;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['quantity_issued'] = $value->quantity_issued;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['status'] = $status;
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionReturnReceiptView($id) {
        $model = $this->loadReturnReceiptModel($id);

        $this->pageTitle = "View Return Receipt";
        $this->menu = array(
            array('label' => "Create Return Receipt", 'url' => array('create', 'param' => array(
                        'returns_form' => 2,
                        'isReturnable' => false,
                        'isReturnReceipt' => true,
                        'isReturnMdse' => false,
                    ))),
            array('label' => "Delete Return Receipt", 'url' => '#', 'linkOptions' => array('submit' => array('returnReceiptDelete', 'id' => $model->return_receipt_id), 'confirm' => 'Are you sure you want to delete this item?')),
            array('label' => "Manage Returns", 'url' => array('admin')),
        );

        $return_receipt_detail = ReturnReceiptDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "return_receipt_id" => $model->return_receipt_id));

        $pr_nos = "";
        $pr_no_arr = array();
        $po_nos = "";
        $po_no_arr = array();
        foreach ($return_receipt_detail as $key => $val) {

            if ($val->pr_no != "") {
                if (!in_array($val->pr_no, $pr_no_arr)) {
                    array_push($pr_no_arr, $val->pr_no);
                    $pr_nos .= $val->pr_no . ",";
                }
            }

            if ($val->po_no != "") {
                if (!in_array($val->po_no, $po_no_arr)) {
                    array_push($po_no_arr, $val->po_no);
                    $po_nos .= $val->po_no . ",";
                }
            }
        }

        $nos = array();
        $nos['pr_no'] = substr($pr_nos, 0, -1);
        $nos['po_no'] = substr($po_nos, 0, -1);

        $source = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $model->receive_return_from, $model->receive_return_from_id);

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND zones.zone_id = '" . $model->destination_zone_id . "'";
        $c1->with = array("zones");
        $destination_sales_office = SalesOffice::model()->find($c1);

        $destination = array();
        $destination['zone_name'] = $model->zone->zone_name;
        $destination['destination_sales_office_name'] = isset($destination_sales_office->sales_office_name) ? $destination_sales_office->sales_office_name : "";
        $destination['contact_person'] = "";
        $destination['contact_no'] = "";
        $destination['address'] = isset($destination_sales_office->address1) ? $destination_sales_office->address1 : "";


        $this->render('_view_return_receipt', array(
            'model' => $model,
            'destination' => $destination,
            'source' => $source,
            'nos' => $nos,
        ));
    }

    public function actionGetDetailsByReturnReceiptID($return_receipt_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND return_receipt_id = '" . $return_receipt_id . "'";
        $return_receipt_details = ReturnReceiptDetail::model()->findAll($c);

        $output = array();
        foreach ($return_receipt_details as $key => $value) {
            $row = array();

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['return_receipt_detail_id'] = $value->return_receipt_detail_id;
            $row['return_receipt_id'] = $value->return_receipt_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionReturnMdseData() {

        ReturnMdse::model()->search_string = $_GET['search']['value'] != "" ? $_GET['search']['value'] : null;

        $dataProvider = ReturnMdse::model()->data($_GET['order'][0]['column'], $_GET['order'][0]['dir'], $_GET['length'], $_GET['start'], $_GET['columns']);

        $count = ReturnMdse::model()->countByAttributes(array('company_id' => Yii::app()->user->company_id));

        $output = array(
            "draw" => intval($_GET['draw']),
            "recordsTotal" => $count,
            "recordsFiltered" => $dataProvider->totalItemCount,
            "data" => array()
        );

        foreach ($dataProvider->getData() as $key => $value) {
            $row = array();
            $row['return_mdse_id'] = $value->return_mdse_id;
            $row['return_mdse_no'] = $value->return_mdse_no;
            $row['reference_dr_no'] = $value->reference_dr_no;
            $row['return_to'] = $value->return_to;
            $row['return_to_id'] = $value->return_to_id;
            $row['transaction_date'] = $value->transaction_date;
            $row['date_returned'] = $value->date_returned;
            $row['remarks'] = $value->remarks;
            $row['total_amount'] = $value->total_amount;
            $row['created_date'] = $value->created_date;
            $row['created_by'] = $value->created_by;
            $row['updated_date'] = $value->updated_date;
            $row['updated_by'] = $value->updated_by;

            $destination = ReturnMdse::model()->getReturnToDetails(Yii::app()->user->company_id, $value->return_to, $value->return_to_id);

            $row['destination_name'] = $destination['destination_name'];

            $row['links'] = '<a class="btn btn-sm btn-default view" title="View" href="' . $this->createUrl('/inventory/Returns/returnMdseView', array('id' => $value->return_mdse_id)) . '">
                                <i class="glyphicon glyphicon-eye-open"></i>
                            </a>
                            <a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnMdseDelete', array('id' => $value->return_mdse_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionGetReturnMdseDetailsByReturnMdseID($return_mdse_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND return_mdse_id = '" . $return_mdse_id . "'";
        $return_mdse_details = ReturnMdseDetail::model()->findAll($c);

        $output = array();
        foreach ($return_mdse_details as $key => $value) {
            $row = array();

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['return_mdse_detail_id'] = $value->return_mdse_detail_id;
            $row['return_mdse_id'] = $value->return_mdse_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['sku_sub_category'] = isset($value->sku->sub_type) ? $value->sku->sub_type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $row['links'] = '<a class="btn btn-sm btn-default delete" title="Delete" href="' . $this->createUrl('/inventory/Returns/returnMdseDetailDelete', array('return_mdse_detail_id' => $value->return_mdse_detail_id)) . '">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionReturnMdseView($id) {
        $model = $this->loadReturnMdseModel($id);

        $this->pageTitle = "View Return Mdse";
        $this->menu = array(
            array('label' => "Create Return Mdse", 'url' => array('create', 'param' => array(
                        'returns_form' => 3,
                        'isReturnable' => false,
                        'isReturnReceipt' => false,
                        'isReturnMdse' => true,
                    ))),
            array('label' => "Delete Return Mdse", 'url' => '#', 'linkOptions' => array('submit' => array('returnableDelete', 'id' => $model->return_mdse_id), 'confirm' => 'Are you sure you want to delete this item?')),
            array('label' => "Manage Returns", 'url' => array('admin')),
        );

        $return_mdse_detail = ReturnMdseDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "return_mdse_id" => $model->return_mdse_id));

        $pr_nos = "";
        $pr_no_arr = array();
        $po_nos = "";
        $po_no_arr = array();
        $source_zones = "";
        $source_zones_arr = array();
        $source_address = "";
        $source_contact_person = "";
        $source_contact_no = "";
        $i = $x = $y = $z = 1;
        foreach ($return_mdse_detail as $key => $val) {

            if ($val->pr_no != "") {
                if (!in_array($val->pr_no, $pr_no_arr)) {
                    array_push($pr_no_arr, $val->pr_no);
                    $pr_nos .= $val->pr_no . ",";
                }
            }

            if ($val->po_no != "") {
                if (!in_array($val->po_no, $po_no_arr)) {
                    array_push($po_no_arr, $val->po_no);
                    $po_nos .= $val->po_no . ",";
                }
            }

            $source_zone_id = $val->source_zone_id;

            if (!in_array($source_zone_id, $source_zones_arr)) {
                array_push($source_zones_arr, $source_zone_id);

                $out_source_zone = Zone::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "zone_id" => $source_zone_id));
                if ($out_source_zone) {
                    $source_zones .= "<sup>" . $i++ . ".</sup> " . $out_source_zone->zone_name . " <i class='text-muted'>(" . $out_source_zone->salesOffice->sales_office_name . ")</i><br/>";
                    $source_address .= isset($out_source_zone->salesOffice->address1) ? "<sup>" . $x++ . ".</sup> " . $out_source_zone->salesOffice->address1 . "<br/>" : "";
                }

                $c3 = new CDbCriteria;
                $c3->select = new CDbExpression('t.*, CONCAT(t.first_name, " ",t.last_name) AS fullname');
                $c3->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND t.default_zone_id = '" . $source_zone_id . "'";
                $source_employee = Employee::model()->find($c3);
                $source_contact_person .= isset($source_employee) ? "<sup>" . $y++ . ".</sup> " . $source_employee->fullname . "<br/>" : "";
                $source_contact_no .= isset($source_employee) ? "<sup>" . $z++ . ".</sup> " . $source_employee->work_phone_number . "<br/>" : "";
            }
        }

        $nos = array();
        $nos['pr_no'] = substr($pr_nos, 0, -1);
        $nos['po_no'] = substr($po_nos, 0, -1);

        $source = array();
        $source['source_zone_name'] = $source_zones;
        $source['contact_person'] = $source_contact_person;
        $source['contact_no'] = $source_contact_no;
        $source['address'] = $source_address;

        $destination = ReturnMdse::model()->getReturnToDetails(Yii::app()->user->company_id, $model->return_to, $model->return_to_id);

        $this->render('_view_return_mdse', array(
            'model' => $model,
            'destination' => $destination,
            'source' => $source,
            'nos' => $nos,
        ));
    }

    public function actionGetDetailsByReturnMdseID($return_mdse_id) {

        $c = new CDbCriteria;
        $c->condition = "company_id = '" . Yii::app()->user->company_id . "' AND return_mdse_id = '" . $return_mdse_id . "'";
        $return_mdse_details = ReturnMdseDetail::model()->findAll($c);

        $output = array();
        foreach ($return_mdse_details as $key => $value) {
            $row = array();

            $uom = Uom::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $value->uom_id));

            $row['return_mdse_detail_id'] = $value->return_mdse_detail_id;
            $row['return_mdse_id'] = $value->return_mdse_id;
            $row['batch_no'] = $value->batch_no;
            $row['sku_code'] = isset($value->sku->sku_code) ? $value->sku->sku_code : null;
            $row['sku_name'] = isset($value->sku->sku_name) ? $value->sku->sku_name : null;
            $row['sku_description'] = isset($value->sku->description) ? $value->sku->description : null;
            $row['sku_category'] = isset($value->sku->type) ? $value->sku->type : null;
            $row['brand_name'] = isset($value->sku->brand->brand_name) ? $value->sku->brand->brand_name : null;
            $row['unit_price'] = $value->unit_price;
            $row['expiration_date'] = $value->expiration_date;
            $row['returned_quantity'] = $value->returned_quantity;
            $row['amount'] = "&#x20B1;" . number_format($value->amount, 2, '.', ',');
            $row['remarks'] = $value->remarks;
            $row['po_no'] = $value->po_no;
            $row['pr_no'] = $value->pr_no;
            $row['uom_name'] = $uom->uom_name;

            $output['data'][] = $row;
        }

        echo json_encode($output);
    }

    public function actionPrintReturnable() {

        $data = Yii::app()->request->getParam('post_data');

        $returnable = $data['Returnable'];
        $returnable_detail = $data['transaction_details'];

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        foreach ($returnable_detail as $key => $val) {
            $row = array();

            $row['sku_id'] = $val['sku_id'];
            $row['uom_id'] = $val['uom_id'];
            $row['sku_status_id'] = $val['sku_status_id'];
            $row['quantity_issued'] = $val['quantity_issued'];
            $row['returned_quantity'] = $val['returned_quantity'];
            $row['unit_price'] = $val['unit_price'];
            $row['status'] = $val['status'];
            $row['amount'] = $val['amount'];
            $row['remarks'] = $val['remarks'];

            $details[] = $row;
        }

        $source_data = array();
        if ($returnable['receive_return_from'] != "") {
            $selected_source = $returnable['receive_return_from'];

            $returnable_label = str_replace(" ", "_", Returnable::RETURNABLE_LABEL) . "_";
            $source_data = Returnable::model()->getReturnFromID($returnable, $selected_source, $returnable_label, $data);
        }

        $source_details = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $returnable['receive_return_from'], $source_data['id']);

        $source["source_name"] = $source_details['source_name'];
        $source["source_address"] = $source_details['address'];

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND t.zone_id = '" . $returnable['destination_zone_id'] . "'";
        $c1->with = array("salesOffice");
        $zone = Zone::model()->find($c1);

        $destination['zone_name'] = $zone->zone_name;
        $destination['sales_office_name'] = $zone->salesOffice->sales_office_name;
        $destination['address'] = $zone->salesOffice->address1;

        $headers['return_receipt_no'] = $returnable['return_receipt_no'];
        $headers['reference_dr_no'] = $returnable['reference_dr_no'];
        $headers['date_returned'] = $returnable['transaction_date'];
        $headers['remarks'] = $returnable['remarks'];
        $headers['total_amount'] = $returnable['total_amount'];

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        sleep(1);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionLoadReturnablePDF($id) {

        $data = Yii::app()->session[$id];

        if ($data == "") {
            echo "Error: Please close and try again.";
            return false;
        }

        ob_start();

        $headers = $data['headers'];
        $source = $data['source'];
        $destination = $data['destination'];
        $details = $data['details'];

        $pdf = Globals::pdf();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('DejaVu Sans', '#000', 10);

        $pdf->AddPage();

        $html = '
        <style type="text/css">
            .text-center { text-align: center; }
            .title { font-size: 12px; }
            .sub-title { font-size: 10px; }
            .title-report { font-size: 15px; font-weight: bold; }
            .table_main { font-size: 8px; }
            .table_details { font-size: 8px; width: 100%; }
            .table_footer { font-size: 8px; width: 100%; }
            .border-bottom { border-bottom: 1px solid #333; font-size: 8px; }
            .row_label { width: 120px; }
            .row_content_sm { width: 100px; }
            .row_content_lg { width: 300px; }
            .align-right { text-align: right; }
        </style>
        
        <div id="header" class="text-center">
            <span class="title">ASIA BREWERY INCORPORATED</span><br/>
            <span class="sub-title">16th FLOOR ALLIED BANK CENTER, AYALA AVENUE, MAKATI CITY</span><br/>
            <span class="title-report">RETURNABLE RECEIPT</span>
        </div><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="font-weight: bold; width: 100px;">WAREHOUSE NAME</td>
                <td class="border-bottom" style="width: 370px;">' . $destination['sales_office_name'] . '</td>
                <td style="font-weight: bold; width: 90px;">DATE RETURNED</td>
                <td class="border-bottom" style="width: 100px;">' . $headers['date_returned'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">ADDRESS</td>
                <td class="border-bottom">' . $destination['address'] . '</td>
                <td></td>
            </tr>
        </table><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="width: 100px;"></td>
                <td style="width: 130px;"></td>
                <td style="width: 50px;"></td>
                <td style="width: 80px;"></td>
                <td style="width: 300px;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">RR NO.</td>
                <td class="border-bottom">' . $headers['return_receipt_no'] . '</td>
                <td colspan="2" style="font-weight: bold;">CUSTOMER / SALESOFFICE</td>
                <td class="border-bottom">' . $source['source_name'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">REFERENCE DR NO.</td>
                <td class="border-bottom">' . $headers['reference_dr_no'] . '</td>
                <td style="font-weight: bold;">ADDRESS</td>
                <td colspan="2" class="border-bottom">' . $source['source_address'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">DESTINATION ZONE</td>
                <td colspan="4" class="border-bottom">' . $destination['zone_name'] . '</td>
            </tr>
        </table><br/><br/><br/>
        
        <table class="table_details" border="1">
            <tr>
                <td style="font-weight: bold;">MM CODE</td>
                <td style="font-weight: bold; width: 100px;">MM DESCRIPTION</td>
                <td style="font-weight: bold;">BRAND</td>
                <td style="font-weight: bold;">MM CATEGORY</td>
                <td style="font-weight: bold;">MM SUB-CATEGORY</td>
                <td style="font-weight: bold; width: 60px;">QUANTITY ISSUED</td>
                <td style="font-weight: bold; width: 60px;">RETURNED QUANTITY</td>
                <td style="font-weight: bold; width: 40px;">UOM</td>
                <td style="font-weight: bold;">UNIT PRICE</td>
                <td style="font-weight: bold;">STATUS</td>
                <td style="font-weight: bold;">REMARKS</td>
            </tr>';

        $qty_issued = 0;
        $returned_qty = 0;
        $total_unit_price = 0;
        foreach ($details as $key => $val) {
            $sku = Sku::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "sku_id" => $val['sku_id']));
            $uom = UOM::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $val['uom_id']));

            $html .= '<tr>
                        <td>' . $sku->sku_code . '</td>
                        <td>' . $sku->description . '</td>
                        <td>' . $sku->brand->brand_name . '</td>
                        <td>' . $sku->type . '</td>
                        <td>' . $sku->sub_type . '</td>
                        <td>' . $val['quantity_issued'] . '</td>
                        <td>' . $val['returned_quantity'] . '</td>
                        <td>' . $uom->uom_name . '</td>
                        <td class="align-right">&#x20B1; ' . number_format($val['unit_price'], 2, '.', ',') . '</td>
                        <td>' . $val['status'] . '</td>
                        <td>' . $val['remarks'] . '</td>
                    </tr>';

            $qty_issued += $val['quantity_issued'];
            $returned_qty += $val['returned_quantity'];
            $total_unit_price += $val['unit_price'];
        }

        $html .= '<tr>
                    <td colspan="11"></td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">GRAND TOTAL</td>
                    <td>' . $qty_issued . '</td>
                    <td>' . $returned_qty . '</td>
                    <td></td>
                    <td class="align-right">&#x20B1; ' . number_format($total_unit_price, 2, '.', ',') . '</td>
                    <td colspan="2"></td>
                </tr>';

        $html .= '</table><br/><br/><br/>

                <table class="table_footer">
                    <tr>
                        <td style="width: 180px; border-top: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; font-weight: bold;">REMARKS:</td>
                        <td style="width: 100px;"></td>
                        <td style="width: 150px; font-weight: bold;">RETURNED BY:</td>
                        <td style="width: 100px;"></td>
                        <td style="width: 150px; font-weight: bold;">RECEIVED BY:</td>
                    </tr>
                    <tr>
                        <td style="border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; min-height: 50px; height: 50px;"><br/>' . $headers['remarks'] . '<br/></td>
                        <td></td>
                        <td class="border-bottom"></td>
                        <td></td>
                        <td class="border-bottom"></td>
                    </tr>
                </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('inbound.pdf', 'I');
    }

    public function actionReturnableViewPrint($returnable_id) {

        $returnable = $this->loadReturnableModel($returnable_id);

        $returnable_detail = ReturnableDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "returnable_id" => $returnable_id));

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        foreach ($returnable_detail as $key => $val) {
            $row = array();

            $row['sku_id'] = $val->sku_id;
            $row['uom_id'] = $val->uom_id;
            $row['sku_status_id'] = $val->sku_status_id;
            $row['quantity_issued'] = $val->quantity_issued;
            $row['returned_quantity'] = $val->returned_quantity;
            $row['status'] = $val->status;
            $row['unit_price'] = $val->unit_price;
            $row['amount'] = $val->amount;
            $row['remarks'] = $val->remarks;

            $details[] = $row;
        }

        $source_details = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $returnable->receive_return_from, $returnable->receive_return_from_id);

        $source["source_name"] = $source_details['source_name'];
        $source["source_address"] = $source_details['address'];

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND t.zone_id = '" . $returnable->destination_zone_id . "'";
        $c1->with = array("salesOffice");
        $zone = Zone::model()->find($c1);

        $destination['zone_name'] = $zone->zone_name;
        $destination['sales_office_name'] = $zone->salesOffice->sales_office_name;
        $destination['address'] = $zone->salesOffice->address1;

        $headers['return_receipt_no'] = $returnable->return_receipt_no;
        $headers['reference_dr_no'] = $returnable->reference_dr_no;
        $headers['date_returned'] = $returnable->date_returned;
        $headers['remarks'] = $returnable->remarks;
        $headers['total_amount'] = $returnable->total_amount;

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionReturnReceiptViewPrint($return_receipt_id) {

        $return_receipt = $this->loadReturnReceiptModel($return_receipt_id);

        $return_receipt_detail = ReturnReceiptDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "return_receipt_id" => $return_receipt_id));

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        foreach ($return_receipt_detail as $key => $val) {
            $row = array();

            $row['sku_id'] = $val->sku_id;
            $row['uom_id'] = $val->uom_id;
            $row['sku_status_id'] = $val->sku_status_id;
            $row['returned_quantity'] = $val->returned_quantity;
            $row['status'] = $val->status;
            $row['unit_price'] = $val->unit_price;
            $row['amount'] = $val->amount;
            $row['remarks'] = $val->remarks;

            $details[] = $row;
        }

        $source_details = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $return_receipt->receive_return_from, $return_receipt->receive_return_from_id);

        $source["source_name"] = $source_details['source_name'];
        $source["source_address"] = $source_details['address'];

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND t.zone_id = '" . $return_receipt->destination_zone_id . "'";
        $c1->with = array("salesOffice");
        $zone = Zone::model()->find($c1);

        $destination['zone_name'] = $zone->zone_name;
        $destination['sales_office_name'] = $zone->salesOffice->sales_office_name;
        $destination['address'] = $zone->salesOffice->address1;

        $headers['return_receipt_no'] = $return_receipt->return_receipt_no;
        $headers['reference_dr_no'] = $return_receipt->reference_dr_no;
        $headers['date_returned'] = $return_receipt->date_returned;
        $headers['remarks'] = $return_receipt->remarks;
        $headers['total_amount'] = $return_receipt->total_amount;

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionLoadReturnReceiptPDF($id) {

        $data = Yii::app()->session[$id];

        if ($data == "") {
            echo "Error: Please close and try again.";
            return false;
        }

        ob_start();

        $headers = $data['headers'];
        $source = $data['source'];
        $destination = $data['destination'];
        $details = $data['details'];

        $pdf = Globals::pdf();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('DejaVu Sans', '#000', 10);

        $pdf->AddPage();

        $html = '
        <style type="text/css">
            .text-center { text-align: center; }
            .title { font-size: 12px; }
            .sub-title { font-size: 10px; }
            .title-report { font-size: 15px; font-weight: bold; }
            .table_main { font-size: 8px; }
            .table_details { font-size: 8px; width: 100%; }
            .table_footer { font-size: 8px; width: 100%; }
            .border-bottom { border-bottom: 1px solid #333; font-size: 8px; }
            .row_label { width: 120px; }
            .row_content_sm { width: 100px; }
            .row_content_lg { width: 300px; }
            .align-right { text-align: right; }
        </style>
        
        <div id="header" class="text-center">
            <span class="title">ASIA BREWERY INCORPORATED</span><br/>
            <span class="sub-title">16th FLOOR ALLIED BANK CENTER, AYALA AVENUE, MAKATI CITY</span><br/>
            <span class="title-report">RETURN RECEIPT</span>
        </div><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="font-weight: bold; width: 100px;">WAREHOUSE NAME</td>
                <td class="border-bottom" style="width: 370px;">' . $destination['sales_office_name'] . '</td>
                <td style="font-weight: bold; width: 90px;">DATE RETURNED</td>
                <td class="border-bottom" style="width: 100px;">' . $headers['date_returned'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">ADDRESS</td>
                <td class="border-bottom">' . $destination['address'] . '</td>
                <td></td>
            </tr>
        </table><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="width: 100px;"></td>
                <td style="width: 130px;"></td>
                <td style="width: 50px;"></td>
                <td style="width: 80px;"></td>
                <td style="width: 300px;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">RR NO.</td>
                <td class="border-bottom">' . $headers['return_receipt_no'] . '</td>
                <td colspan="2" style="font-weight: bold;">CUSTOMER / SALESOFFICE</td>
                <td class="border-bottom">' . $source['source_name'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">REFERENCE DR NO.</td>
                <td class="border-bottom">' . $headers['reference_dr_no'] . '</td>
                <td style="font-weight: bold;">ADDRESS</td>
                <td colspan="2" class="border-bottom">' . $source['source_address'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">DESTINATION ZONE</td>
                <td colspan="4" class="border-bottom">' . $destination['zone_name'] . '</td>
            </tr>
        </table><br/><br/><br/>
        
        <table class="table_details" border="1">
            <tr>
                <td style="font-weight: bold;">MM CODE</td>
                <td style="font-weight: bold; width: 100px;">MM DESCRIPTION</td>
                <td style="font-weight: bold;">BRAND</td>
                <td style="font-weight: bold;">MM CATEGORY</td>
                <td style="font-weight: bold;">MM SUB-CATEGORY</td>
                <td style="font-weight: bold; width: 60px;">RETURNED QUANTITY</td>
                <td style="font-weight: bold; width: 40px;">UOM</td>
                <td style="font-weight: bold;">UNIT PRICE</td>
                <td style="font-weight: bold; width: 100px;">REMARKS</td>
            </tr>';

        $returned_qty = 0;
        $total_unit_price = 0;
        foreach ($details as $key => $val) {
            $sku = Sku::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "sku_id" => $val['sku_id']));
            $uom = UOM::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $val['uom_id']));

            $html .= '<tr>
                        <td>' . $sku->sku_code . '</td>
                        <td>' . $sku->description . '</td>
                        <td>' . $sku->brand->brand_name . '</td>
                        <td>' . $sku->type . '</td>
                        <td>' . $sku->sub_type . '</td>
                        <td>' . $val['returned_quantity'] . '</td>
                        <td>' . $uom->uom_name . '</td>
                        <td class="align-right">&#x20B1; ' . number_format($val['unit_price'], 2, '.', ',') . '</td>
                        <td>' . $val['remarks'] . '</td>
                    </tr>';

            $returned_qty += $val['returned_quantity'];
            $total_unit_price += $val['unit_price'];
        }

        $html .= '<tr>
                    <td colspan="9"></td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">GRAND TOTAL</td>
                    <td>' . $returned_qty . '</td>
                    <td></td>
                    <td class="align-right">&#x20B1; ' . number_format($total_unit_price, 2, '.', ',') . '</td>
                    <td></td>
                </tr>';

        $html .= '</table><br/><br/><br/>

                <table class="table_footer">
                    <tr>
                        <td style="width: 180px; border-top: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; font-weight: bold;">REMARKS:</td>
                        <td style="width: 100px;"></td>
                        <td style="width: 150px; font-weight: bold;">RETURNED BY:</td>
                        <td style="width: 100px;"></td>
                        <td style="width: 150px; font-weight: bold;">RECEIVED BY:</td>
                    </tr>
                    <tr>
                        <td style="border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; min-height: 50px; height: 50px;"><br/>' . $headers['remarks'] . '<br/></td>
                        <td></td>
                        <td class="border-bottom"></td>
                        <td></td>
                        <td class="border-bottom"></td>
                    </tr>
                </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('inbound.pdf', 'I');
    }

    public function actionPrintReturnReceipt() {

        $data = Yii::app()->request->getParam('post_data');

        $return_receipt = $data['ReturnReceipt'];
        $return_receipt_detail = $data['transaction_details'];

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        foreach ($return_receipt_detail as $key => $val) {
            $row = array();

            $row['sku_id'] = $val['sku_id'];
            $row['uom_id'] = $val['uom_id'];
            $row['sku_status_id'] = $val['sku_status_id'];
            $row['returned_quantity'] = $val['returned_quantity'];
            $row['unit_price'] = $val['unit_price'];
            $row['amount'] = $val['amount'];
            $row['remarks'] = $val['remarks'];

            $details[] = $row;
        }

        $source_data = array();
        if ($return_receipt['receive_return_from'] != "") {
            $selected_source = $return_receipt['receive_return_from'];

            $return_receipt_label = str_replace(" ", "_", ReturnReceipt::RETURN_RECEIPT_LABEL) . "_";
            $source_data = Returnable::model()->getReturnFromID($return_receipt, $selected_source, $return_receipt_label, $data);
        }

        $source_details = Returnable::model()->getReturnFormDetails(Yii::app()->user->company_id, $return_receipt['receive_return_from'], $source_data['id']);

        $source["source_name"] = $source_details['source_name'];
        $source["source_address"] = $source_details['address'];

        $c1 = new CDbCriteria;
        $c1->condition = "t.company_id = '" . Yii::app()->user->company_id . "' AND t.zone_id = '" . $return_receipt['destination_zone_id'] . "'";
        $c1->with = array("salesOffice");
        $zone = Zone::model()->find($c1);

        $destination['zone_name'] = $zone->zone_name;
        $destination['sales_office_name'] = $zone->salesOffice->sales_office_name;
        $destination['address'] = $zone->salesOffice->address1;

        $headers['return_receipt_no'] = $return_receipt['return_receipt_no'];
        $headers['reference_dr_no'] = $return_receipt['reference_dr_no'];
        $headers['date_returned'] = $return_receipt['transaction_date'];
        $headers['remarks'] = $return_receipt['remarks'];
        $headers['total_amount'] = $return_receipt['total_amount'];

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        sleep(1);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionReturnMdseDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                // delete return mdse details by return_able_mdse_id
                ReturnMdseDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND return_mdse_id = " . $id);
                // delete attachment by returnable_id as transaction_id
//                $this->deleteAttachmentByOutgoingInvID($id);
                // we only allow deletion via POST request
                $this->loadReturnMdseModel($id)->delete();

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    if (!isset($_GET['ajax'])) {
                        Yii::app()->user->setFlash('danger', "Unable to delete");
                        $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('view', 'id' => $id));
                    } else {
                        echo "1451";
                        exit;
                    }
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionReturnMdseViewPrint($return_mdse_id) {

        $return_mdse = $this->loadReturnMdseModel($return_mdse_id);

        $return_mdse_detail = ReturnMdseDetail::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "return_mdse_id" => $return_mdse_id));

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        $source_zones = "";
        $source_zones_arr = array();
        $source_address = "";
        $source_contact_person = "";
        $i = $x = 1;
        foreach ($return_mdse_detail as $key => $val) {
            $row = array();

            $source_zone_id = $val->source_zone_id;

            if (!in_array($source_zone_id, $source_zones_arr)) {
                array_push($source_zones_arr, $source_zone_id);

                $inc_source_zone = Zone::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "zone_id" => $source_zone_id));
                $source_zones .= isset($inc_source_zone->salesOffice->sales_office_name) ? "<sup>" . $i++ . ".</sup> " . $inc_source_zone->salesOffice->sales_office_name . "<br/>" : "";
                $source_address .= isset($inc_source_zone->salesOffice->address1) ? "<sup>" . $x++ . ".</sup> " . $inc_source_zone->salesOffice->address1 . "<br/>" : "";
            }

            $row['sku_id'] = $val->sku_id;
            $row['uom_id'] = $val->uom_id;
            $row['sku_status_id'] = $val->sku_status_id;
            $row['returned_quantity'] = $val->returned_quantity;
            $row['unit_price'] = $val->unit_price;
            $row['amount'] = $val->amount;
            $row['remarks'] = $val->remarks;

            $details[] = $row;
        }

        $source['source_so_name'] = rtrim($source_zones, "<br/>");
        $source['source_address'] = rtrim($source_address, "<br/>");

        $destination_details = ReturnMdse::model()->getReturnToDetails(Yii::app()->user->company_id, $return_mdse->return_to, $return_mdse->return_to_id);

        $destination["destination_name"] = $destination_details['destination_name'];
        $destination["destination_address"] = $destination_details['address'];

        $headers['return_mdse_no'] = $return_mdse->return_mdse_no;
        $headers['reference_dr_no'] = $return_mdse->reference_dr_no;
        $headers['date_returned'] = $return_mdse->date_returned;
        $headers['remarks'] = $return_mdse->remarks;
        $headers['total_amount'] = $return_mdse->total_amount;

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionLoadReturnMdsePDF($id) {

        $data = Yii::app()->session[$id];

        if ($data == "") {
            echo "Error: Please close and try again.";
            return false;
        }

        ob_start();

        $headers = $data['headers'];
        $source = $data['source'];
        $destination = $data['destination'];
        $details = $data['details'];

        $pdf = Globals::pdf();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('DejaVu Sans', '#000', 10);

        $pdf->AddPage();

        $html = '
        <style type="text/css">
            .text-center { text-align: center; }
            .title { font-size: 12px; }
            .sub-title { font-size: 10px; }
            .title-report { font-size: 15px; font-weight: bold; }
            .table_main { font-size: 8px; }
            .table_details { font-size: 8px; width: 100%; }
            .table_footer { font-size: 8px; width: 100%; }
            .border-bottom { border-bottom: 1px solid #333; font-size: 8px; }
            .row_label { width: 120px; }
            .row_content_sm { width: 100px; }
            .row_content_lg { width: 300px; }
            .align-right { text-align: right; }
        </style>
        
        <div id="header" class="text-center">
            <span class="title">ASIA BREWERY INCORPORATED</span><br/>
            <span class="sub-title">16th FLOOR ALLIED BANK CENTER, AYALA AVENUE, MAKATI CITY</span><br/>
            <span class="title-report">RETURN MERCHANDISE</span>
        </div><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="font-weight: bold; width: 100px;">WAREHOUSE NAME</td>
                <td class="border-bottom" style="width: 370px;">' . $source["source_so_name"] . '</td>
                <td style="font-weight: bold; width: 90px;">DATE RETURNED</td>
                <td class="border-bottom" style="width: 100px;">' . $headers['date_returned'] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">ADDRESS</td>
                <td class="border-bottom">' . $source["source_address"] . '</td>
                <td></td>
            </tr>
        </table><br/><br/>
        
        <table class="table_main">
            <tr>
                <td style="width: 100px;"></td>
                <td style="width: 130px;"></td>
                <td style="width: 50px;"></td>
                <td style="width: 80px;"></td>
                <td style="width: 300px;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">RR NO.</td>
                <td class="border-bottom">' . $headers['return_mdse_no'] . '</td>
                <td colspan="2" style="font-weight: bold;">SUPPLIER / SALESOFFICE</td>
                <td class="border-bottom">' . $destination["destination_name"] . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">REFERENCE DR NO.</td>
                <td class="border-bottom">' . $headers['reference_dr_no'] . '</td>
                <td style="font-weight: bold;">ADDRESS</td>
                <td colspan="2" class="border-bottom">' . $destination["destination_address"] . '</td>
            </tr>
        </table><br/><br/><br/>
        
        <table class="table_details" border="1">
            <tr>
                <td style="font-weight: bold;">MM CODE</td>
                <td style="font-weight: bold; width: 100px;">MM DESCRIPTION</td>
                <td style="font-weight: bold;">BRAND</td>
                <td style="font-weight: bold;">MM CATEGORY</td>
                <td style="font-weight: bold;">MM SUB-CATEGORY</td>
                <td style="font-weight: bold; width: 60px;">RETURNED QUANTITY</td>
                <td style="font-weight: bold; width: 40px;">UOM</td>
                <td style="font-weight: bold;">UNIT PRICE</td>
                <td style="font-weight: bold; width: 100px;">REMARKS</td>
            </tr>';

        $returned_qty = 0;
        $total_unit_price = 0;
        foreach ($details as $key => $val) {
            $sku = Sku::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "sku_id" => $val['sku_id']));
            $uom = UOM::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "uom_id" => $val['uom_id']));

            $html .= '<tr>
                        <td>' . $sku->sku_code . '</td>
                        <td>' . $sku->description . '</td>
                        <td>' . $sku->brand->brand_name . '</td>
                        <td>' . $sku->type . '</td>
                        <td>' . $sku->sub_type . '</td>
                        <td>' . $val['returned_quantity'] . '</td>
                        <td>' . $uom->uom_name . '</td>
                        <td class="align-right">&#x20B1; ' . number_format($val['unit_price'], 2, '.', ',') . '</td>
                        <td>' . $val['remarks'] . '</td>
                    </tr>';

            $returned_qty += $val['returned_quantity'];
            $total_unit_price += $val['unit_price'];
        }

        $html .= '<tr>
                    <td colspan="9"></td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">GRAND TOTAL</td>
                    <td>' . $returned_qty . '</td>
                    <td></td>
                    <td class="align-right">&#x20B1; ' . number_format($total_unit_price, 2, '.', ',') . '</td>
                    <td></td>
                </tr>';

        $html .= '</table><br/><br/><br/>
            
            <table class="table_footer">
                <tr>
                    <td style="width: 180px; border-top: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; font-weight: bold;">REMARKS:</td>
                    <td style="width: 20px;"></td>
                    <td style="width: 90px; font-weight: bold;">SHIPPED VIA:</td>
                    <td class="border-bottom" style="width: 120px;"></td>
                    <td style="width: 20px;"></td>
                    <td style="width: 110px; font-weight: bold;">TRUCK NO.:</td>
                    <td class="border-bottom" style="width: 130px;"></td>
                </tr>
                <tr>
                    <td rowspan="5" style="border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000;"><br/><br/>' . $headers['remarks'] . '</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="font-weight: bold;">CHECKED BY:</td>
                    <td class="border-bottom"></td>
                    <td></td>
                    <td style="font-weight: bold;">DRIVER' . "'" . 'S NAME:</td>
                    <td class="border-bottom"></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td></td>
                    <td style="font-weight: bold;">AUTHORIZED BY:</td>
                    <td class="border-bottom"></td>
                    <td></td>
                    <td style="font-weight: bold;">DRIVER' . "'" . 'S SIGNATURE:</td>
                    <td class="border-bottom"></td>
                </tr>
                <tr><td colspan="6"></td></tr>
                <tr><td colspan="6"></td></tr>
                <tr><td colspan="6"></td></tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2" style="font-weight: bold;">RETURNED BY:</td>
                    <td></td>
                    <td colspan="2" style="font-weight: bold;">RECEIVED BY:</td>
                </tr>
                <tr><td colspan="6"></td></tr>
                <tr><td colspan="6"></td></tr>
                <tr><td colspan="6"></td></tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2" class="border-bottom"></td>
                    <td></td>
                    <td colspan="2" class="border-bottom"></td>
                </tr>
            </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('inbound.pdf', 'I');
    }

    public function actionPrintReturnMdse() {

        $data = Yii::app()->request->getParam('post_data');

        $return_mdse = $data['ReturnMdse'];
        $return_mdse_detail = $data['transaction_details'];

        $return = array();

        $details = array();
        $source = array();
        $destination = array();
        $headers = array();

        $source_zones = "";
        $source_zones_arr = array();
        $source_address = "";
        $i = $x = 1;
        foreach ($return_mdse_detail as $key => $val) {
            $row = array();

            $source_zone_id = $val['source_zone_id'];

            if (!in_array($source_zone_id, $source_zones_arr)) {
                array_push($source_zones_arr, $source_zone_id);

                $inc_source_zone = Zone::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "zone_id" => $source_zone_id));
                $source_zones .= isset($inc_source_zone->salesOffice->sales_office_name) ? "<sup>" . $i++ . ".</sup> " . $inc_source_zone->salesOffice->sales_office_name . "<br/>" : "";
                $source_address .= isset($inc_source_zone->salesOffice->address1) ? "<sup>" . $x++ . ".</sup> " . $inc_source_zone->salesOffice->address1 . "<br/>" : "";
            }

            $row['sku_id'] = $val['sku_id'];
            $row['uom_id'] = $val['uom_id'];
            $row['sku_status_id'] = $val['sku_status_id'];
            $row['returned_quantity'] = $val['returned_quantity'];
            $row['unit_price'] = $val['unit_price'];
            $row['amount'] = $val['amount'];
            $row['remarks'] = $val['remarks'];

            $details[] = $row;
        }

        $source['source_so_name'] = rtrim($source_zones, "<br/>");
        $source['source_address'] = rtrim($source_address, "<br/>");

        $destination_data = array();
        if ($return_mdse['return_to'] != "") {
            $selected_destination = $return_mdse['return_to'];

            $return_mdse_label = str_replace(" ", "_", ReturnMdse::RETURN_MDSE_LABEL) . "_";
            $destination_data = ReturnMdse::model()->getReturnToID($selected_destination, $return_mdse_label, $data);
        }

        $destination_details = ReturnMdse::model()->getReturnToDetails(Yii::app()->user->company_id, $return_mdse['return_to'], $destination_data['id']);

        $destination["destination_name"] = $destination_details['destination_name'];
        $destination["destination_address"] = $destination_details['address'];

        $headers['return_mdse_no'] = $return_mdse['return_mdse_no'];
        $headers['reference_dr_no'] = $return_mdse['reference_dr_no'];
        $headers['date_returned'] = $return_mdse['transaction_date'];
        $headers['remarks'] = $return_mdse['remarks'];
        $headers['total_amount'] = $return_mdse['total_amount'];

        $return['headers'] = $headers;
        $return['source'] = $source;
        $return['destination'] = $destination;
        $return['details'] = $details;

        unset(Yii::app()->session["post_pdf_data_id"]);

        sleep(1);

        Yii::app()->session["post_pdf_data_id"] = 'post-pdf-data-' . Globals::generateV4UUID();
        Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] = $return;

        $output = array();
        if (Yii::app()->session[Yii::app()->session["post_pdf_data_id"]] == "") {
            $output["success"] = false;
            return false;
        }

        $output["success"] = true;
        $output["id"] = Yii::app()->session["post_pdf_data_id"];

        echo json_encode($output);
        Yii::app()->end();
    }

    public function actionUploadAttachment() {

        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
                (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }

        $data = array();
        $model = new Attachment;

        $transaction_type = Yii::app()->request->getPost('saved_returns_transaction_type', '');
        $returns_transaction_id = Yii::app()->request->getPost('saved_returns_transaction_id', '');

        $returns_transaction_type = str_replace(" ", "_", $transaction_type);

        if (isset($_FILES['Attachment']['name']) && $_FILES['Attachment']['name'] != "") {

            $file = CUploadedFile::getInstance($model, 'file');
            $dir = dirname(Yii::app()->getBasePath()) . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . Yii::app()->user->company_id . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . $returns_transaction_type . DIRECTORY_SEPARATOR . $returns_transaction_id;

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $file_name = str_replace(' ', '_', strtolower($file->name));
            $url = Yii::app()->getBaseUrl(true) . '/protected/uploads/' . Yii::app()->user->company_id . '/attachments/' . $returns_transaction_type . "/" . $returns_transaction_id . "/" . $file_name;

            if (@fopen($url, "r")) {
                //existing
                throw new CHttpException(409, "Could not upload file. File already exist " . CHtml::errorSummary($model));
            } else {
                //not existing            

                $file->saveAs($dir . DIRECTORY_SEPARATOR . $file_name);

                $model->attachment_id = Globals::generateV4UUID();
                $model->company_id = Yii::app()->user->company_id;
                $model->file_name = $file_name;
                $model->url = $url;
                $model->transaction_id = $returns_transaction_id;
                $model->transaction_type = $returns_transaction_type;
                $model->created_by = Yii::app()->user->name;

                if ($model->save()) {

                    $data[] = array(
                        'name' => $file->name,
                        'type' => $file->type,
                        'size' => $file->size,
                        'url' => $dir . DIRECTORY_SEPARATOR . $file_name,
                        'thumbnail_url' => $dir . DIRECTORY_SEPARATOR . $file_name,
                    );
                } else {

                    if ($model->hasErrors()) {

                        $data[] = array('error', $model->getErrors());
                    }
                }
            }
        } else {

            throw new CHttpException(500, "Could not upload file " . CHtml::errorSummary($model));
        }


        echo json_encode($data);
    }

    public function actionDownloadAttachment($id) {

        $attachment = Attachment::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "attachment_id" => $id));

        $url = $attachment->url;
        $name = $attachment->file_name;

        $base = Yii::app()->getBaseUrl(true);
        $arr = explode("/", $base);
        $base = $arr[count($arr) - 1];
        $url = str_replace(Yii::app()->getBaseUrl(true), "", $url);
        $src = '../' . $base . $url;

        $data = array();
        $data['success'] = false;
        $data['type'] = "success";

        if (file_exists($src)) {

            $data['name'] = $name;
            $data['src'] = $src;
            $data['success'] = true;
            $data['message'] = "Successfully downloaded";
        } else {

            $data['type'] = "danger";
            $data['message'] = "Could not download file";
        }

        echo json_encode($data);
    }

    public function actionLoadAttachmentDownload($name, $src) {
        ob_clean();
        Yii::app()->getRequest()->sendFile($name, file_get_contents($src));
    }

    public function actionDeleteAttachment($attachment_id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                $attachment = Attachment::model()->findByAttributes(array("company_id" => Yii::app()->user->company_id, "attachment_id" => $attachment_id));

                if ($attachment) {
                    Attachment::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND attachment_id = '" . $attachment->attachment_id . "'");

                    $base = Yii::app()->getBaseUrl(true);
                    $arr = explode("/", $base);
                    $base = $arr[count($arr) - 1];
                    $url = str_replace(Yii::app()->getBaseUrl(true), "", $attachment->url);
                    $delete_link = '../' . $base . $url;

                    if (file_exists($delete_link)) {
                        unlink($delete_link);
                    }
                } else {
                    throw new CHttpException(404, 'The requested page does not exist.');
                }

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    echo "1451";
                    exit;
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function deleteAttachmentByTransactionID($transaction_id, $transaction_type) {

        $type = str_replace(" ", "_", $transaction_type);

        $attachment = Attachment::model()->findAllByAttributes(array("company_id" => Yii::app()->user->company_id, "transaction_type" => $type, "transaction_id" => $transaction_id));

        if (count($attachment) > 0) {
            $base = Yii::app()->getBaseUrl(true);
            $arr = explode("/", $base);
            $base = $arr[count($arr) - 1];

            Attachment::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND transaction_type = '" . $type . "' AND transaction_id = " . $transaction_id);
            $this->delete_directory('../' . $base . "/protected/uploads/" . Yii::app()->user->company_id . "/attachments/" . $type . "/" . $transaction_id);
        } else {
            return false;
        }
    }

    function delete_directory($dirname) {
        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        } else {
            return false;
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file))
                    unlink($dirname . "/" . $file);
                else
                    delete_directory($dirname . '/' . $file);
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
        return true;
    }

    public function actionReturnReceiptDetailDelete($return_receipt_detail_id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                ReturnReceiptDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND return_receipt_detail_id = " . $return_receipt_detail_id);

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    echo "1451";
                    exit;
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionReturnableDetailDelete($returnable_detail_id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                ReturnableDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND returnable_detail_id = " . $returnable_detail_id);

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    echo "1451";
                    exit;
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionReturnMdseDetailDelete($return_mdse_detail_id) {
        if (Yii::app()->request->isPostRequest) {
            try {

                ReturnMdseDetail::model()->deleteAll("company_id = '" . Yii::app()->user->company_id . "' AND return_mdse_detail_id = " . $return_mdse_detail_id);

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                if (!isset($_GET['ajax'])) {
                    Yii::app()->user->setFlash('success', "Successfully deleted");
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
                } else {

                    echo "Successfully deleted";
                    exit;
                }
            } catch (CDbException $e) {
                if ($e->errorInfo[1] == 1451) {
                    echo "1451";
                    exit;
                }
            }
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionCreateReturnMdse($dr_no, $detail_id) {
        $this->layout = '//layouts/column1';
        $this->pageTitle = 'Returns';

        $exist_return_mdse = ReturnMdse::model()->checkIfReturnMdseDetailIsExists(Yii::app()->user->company_id, $dr_no, $detail_id);

        if (!$exist_return_mdse['success']) {
            throw new CHttpException(403, "You are not authorized to perform this action.");
        }

        $returnable = new Returnable;
        $return_receipt = new ReturnReceipt;
        $return_receipt_detail = new ReturnReceiptDetail;
        $return_mdse = new ReturnMdse;
        $return_mdse_detail = new ReturnMdseDetail;
        $sku = new Sku;
        $attachment = new Attachment;

        $return_mdse_header_data = ReturnMdse::model()->getReturnMdseSource(Yii::app()->user->company_id, $dr_no, $exist_return_mdse, $return_mdse);
        
        $return_from_list = CHtml::listData(Returnable::model()->getListReturnFrom(), 'value', 'title');
        $return_to_list = CHtml::listData(ReturnMdse::model()->getListReturnTo(), 'value', 'title');
        $zone_list = CHtml::listData(Zone::model()->findAll(array("condition" => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'zone_name ASC')), 'zone_id', 'zone_name');
        $salesoffice_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');
        $warehouse_list = CHtml::listData(Salesoffice::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '" AND distributor_id = ""', 'order' => 'sales_office_name ASC')), 'sales_office_id', 'sales_office_name');

        $c = new CDbCriteria;
        $c->select = new CDbExpression('t.*, CONCAT(t.first_name, " ", t.last_name) AS fullname');
        $c->condition = 'company_id = "' . Yii::app()->user->company_id . '"';
        $c->order = 'fullname ASC';
        $employee = CHtml::listData(Employee::model()->findAll($c), 'employee_id', 'fullname', 'employee_code');

        if (Yii::app()->request->isPostRequest && Yii::app()->request->isAjaxRequest) {

            $data = array();
            $data['success'] = false;
            $data["type"] = "success";

            if ($_POST['form'] == "transaction" || $_POST['form'] == "print_return_mdse") {
                $data['form'] = $_POST['form'];

                if (isset($_POST['ReturnMdse'])) {
                    
                    $return_mdse->attributes = $_POST['ReturnMdse'];
                    $return_mdse->company_id = Yii::app()->user->company_id;
                    $return_mdse->created_by = Yii::app()->user->name;
                    $return_mdse->date_returned = $return_mdse->transaction_date;
                    unset($return_mdse->created_date);

                    $validatedReturnMdse = CActiveForm::validate($return_mdse);

                    $data = $this->saveReturnMdse($return_mdse, $_POST, $validatedReturnMdse, $data);
                }
            }

            echo json_encode($data);
            Yii::app()->end();
        }

        $uom = CHtml::listData(UOM::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'uom_name ASC')), 'uom_id', 'uom_name');
        $sku_status = CHtml::listData(SkuStatus::model()->findAll(array('condition' => 'company_id = "' . Yii::app()->user->company_id . '"', 'order' => 'status_name ASC')), 'sku_status_id', 'status_name');

        $return_mdse->reference_dr_no = $dr_no;
        $return_mdse->return_mdse_no = "R" . date("YmdHis");

        $this->render('create', array(
            'returnable' => $returnable,
            'return_from_list' => $return_from_list,
            'zone_list' => $zone_list,
            'salesoffice_list' => $salesoffice_list,
            'employee' => $employee,
            'return_receipt' => $return_receipt,
            'return_receipt_detail' => $return_receipt_detail,
            'uom' => $uom,
            'sku_status' => $sku_status,
            'isReturnable' => false,
            'isReturnReceipt' => false,
            'isReturnMdse' => true,
            'sku_id' => "",
            'return_mdse' => $return_mdse,
            'return_mdse_detail' => $return_mdse_detail,
            'return_to_list' => $return_to_list,
            'warehouse_list' => $warehouse_list,
            'sku' => $sku,
            'form' => 3,
            'attachment' => $attachment,
            'detail_id' => $detail_id,
        ));
    }

}
