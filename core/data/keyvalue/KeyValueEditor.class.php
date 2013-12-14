<?php

LoadIBC1Class('DataItem', 'data');

/**
 *
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class KeyValueEditor extends DataItem {

    private $ID;
    private $Key;
    private $IsNew;
    private $valueType;
    private $bindingValue;
    private $bindingType;
    private $timeIncluded;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'keyvalue');
        $args = $this->_service->GetExtraArgs();
        $this->bindingType = isset($args['binding_type']) ? $args['binding_type'] : NULL;
        $this->valueType = isset($args['value_type']) ? $args['value_type'] : NULL;
        $this->timeIncluded = isset($args['time_included']) && $args['time_included'];
        $this->bindingValue = NULL;
    }

    public function GetDataService() {
        return $this->_service;
    }

    public function SetBindingValue($value) {
        $this->bindingValue = $value;
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
        $conn = $this->_service->GetDBConn();
        $continue = !$isNameUnique;
        if ($isNameUnique) {
            $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('kv'));
            if ($this->timeIncluded) {
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
            $sql = $conn->CreateInsertSTMT($this->GetDataTableName('kv'));
            if ($this->timeIncluded) {
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
        if (empty($id))
            $id = $this->ID;
        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateDeleteSTMT($this->GetDataTableName('kv'));
        $sql->AddEqual('kvID', $id);
        $sql->Execute();
        $sql->CloseSTMT();
    }

}
