<?php

LoadIBC1Class('DataItem', 'datamodels');

/**
 *
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.keyvalue
 */
class KeyValueEditor extends DataItem {

    private $ID;
    private $Key;
    private $IsNew;
    private $valueType;
    private $bindingValue;
    private $bindingType;

    function __construct($ServiceName, $ValueType, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::__construct();
        $this->OpenService($ServiceName, $ValueType, $BindingValue, $BindingType);
    }

    public function OpenService($ServiceName, $ValueType, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::OpenService($ServiceName, 'keyvalue');
        $this->valueType = $ValueType;
        $this->bindingValue = $BindingValue;
        $this->bindingType = $BindingType;
    }

    public function Create() {
        $this->IsNew = TRUE;
        $this->ID = 0;
    }

    public function OpenWithKey($key) {
        if (!$this->IsServiceOpen()) {
            throw new ServiceException('service is not open');
        }
        if (empty($this->bindingValue)) {
            throw new ServiceException('no binding');
        }
        $this->IsNew = FALSE;
        $this->Key = $key;
    }

    public function Open($id) {
        if (!$this->IsServiceOpen()) {
            throw new ServiceException('service is not open');
        }
        $this->IsNew = FALSE;
        $this->ID = intval($id);
    }

    public function GetID() {
        return $this->ID;
    }

    public function SetKey($key) {
        $this->SetValue('kvKey', $key, IBC1_DATATYPE_PLAINTEXT);
    }

    public function SetValue($value) {
        $this->SetValue('kvValue', $value, $this->valueType);
    }

    public function Save($isNameUnique = FALSE) {
        if (!$this->IsServiceOpen()) {
            throw new ServiceException('service is not open');
        }
        if ($this->IsNew) {
            $keyname = $this->GetValue('kvKey');
            if ($keyname == NULL) {
                throw new ServiceException('key is not set');
            }
            if ($this->bindingValue !== NULL) {
                $this->SetValue('kvBindingValue', $this->bindingValue, $this->bindingType);
            }
        } else {
            $keyname = $this->Key;
        }
        $conn = $this->GetDBConn();
        $continue = !$isNameUnique;
        if ($isNameUnique) {
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('list'));
            if ($this->TimeIncluded) {
                $sql->AddValue('kvTimeUpdated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            }
            if (!empty($keyname)) {
                $sql->AddEqual('kvKey', $keyname, IBC1_DATATYPE_PLAINTEXT);
                if ($this->bindingValue !== NULL)
                    $sql->AddEqual('kvBindingValue', $this->bindingValue, $this->bindingType, IBC1_LOGICAL_AND);
            } else {
                $sql->AddEqual('kvID', $this->ID);
            }
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->Execute();
            if ($sql->GetAffectedRowCount() == 0) {
                $continue = TRUE;
            }
            $sql->CloseSTMT();
        }
        if ($continue) {
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('list'));
            if ($this->TimeIncluded) {
                $sql->AddValue('setTimeCreated', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
            }
            while (list($key, $item) = $this->GetEach()) {
                $sql->AddValue($key, $item[0], $item[1]);
            }
            $sql->Execute();
            $sql->CloseSTMT();
            if ($this->IsNew) {
                $this->ID = $sql->GetLastInsertID();
                $this->IsNew = FALSE;
            }
        }
    }

    public function Delete($id = 0) {
        if (!$this->IsServiceOpen()) {
            throw new ServiceException('service is not open');
        }
        if ($id == 0)
            $id = $this->ID;
        $conn = $this->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('list'));
        $sql->AddEqual('kvID', $id);
        $sql->Execute();
        $sql->CloseSTMT();
    }

}
