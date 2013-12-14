<?php

LoadIBC1Class('DataModel', 'data');

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class BinaryReader extends DataModel {

    private $data = NULL;

    function __construct($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::__construct();
        $this->OpenService($ServiceName, $BindingValue, $BindingType);
    }

    public function OpenService($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::OpenService($ServiceName, 'keyvalue');
        $this->bindingValue = $BindingValue;
        $this->bindingType = $BindingType;
    }

    public function Open($id) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('list'));
        $sql->AddField('*');
        $sql->AddEqual('kvID', $id, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $this->data = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (empty($this->data)) {
            throw new ServiceException('id not found');
        }
    }

    public function OpenWithKey($key) {
        if (empty($this->bindingValue)) {
            throw new ServiceException('no binding');
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('list'));
        $sql->AddField('*');
        $sql->AddEqual('kvKey', $key, IBC1_DATATYPE_PLAINTEXT);
        $sql->AddEqual('kvBindingValue', $this->bindingValue, $this->bindingType, IBC1_LOGICAL_AND);
        $sql->Execute();
        $this->data = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (empty($this->data)) {
            throw new ServiceException('key not found');
        }
    }

    public function GetID() {
        if (!$this->data)
            return NULL;
        return $this->data->kvID;
    }

    public function GetBindingValue() {
        if (!$this->data)
            return NULL;
        return $this->data->kvBindingValue;
    }

    public function GetKey() {
        if (!$this->data)
            return NULL;
        return $this->data->kvKey;
    }

    public function GetData() {
        if (!$this->data)
            return NULL;
        return $this->data->kvValue;
    }

//    public function ExportData($key, $mode = 0) {
//
//        if ($mode != 0) {
//            header('Content-Disposition: attachment; filename=' . urlencode($this->GetName() . '.' . $this->GetExtName()));
//        }
//        header('Content-Type: ' . $this->GetType());
//        echo($this->GetData($key));
//        $this->CloseService();
//        exit();
//    }
}
