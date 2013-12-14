<?php

LoadIBC1Class('ItemList', 'data');
LoadIBC1Class('DataService', 'data');

/**
 * a reader for key-value pairs, allowing multiple values of the same key.
 * 
 * @version 0.7.20130309
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.keyvalue
 */
class MultiKeyValueReader extends ItemList {

    /**
     * the data service
     * @var DataService 
     */
    private $_service;

    /**
     * a value the reader is bound to
     * @var mixed 
     */
    private $_bindingValue;

    /**
     * data type of the binding value, or NULL if the service does not 
     * support binding
     * @var int|null 
     */
    private $_bindingType;
    private $_key;
    private $_keyExact;

    function __construct($service, $bindingvalue = NULL) {
        $this->_service = DataService::GetService($service, 'keyvalue');
        $args = $this->_service->GetExtraArgs();
        $this->_bindingType = isset($args['binding_type']) ? $args['binding_type'] : NULL;
        $this->_bindingValue = $bindingvalue;
    }

    /**
     * gets the data service object
     * 
     * @return DataService 
     */
    public function GetDataService() {
        return $this->_service;
    }

    public function SetBindingValue($value) {
        $this->_bindingValue = $value;
    }

    /**
     * gets the value the reader is bound to.
     * 
     * @return mixed 
     */
    public function GetBindingValue() {
        return $this->_bindingValue;
    }

    /**
     * gets the data type of the binding value.
     * 
     * @return int|null     returns nulll when the service does not support binding 
     */
    public function GetBindingType() {
        return $this->_bindingType;
    }

    public function SetKey($name, $exact = TRUE) {
        $this->_key = $name;
        $this->_keyExact = $exact;
    }

    public function LoadList() {

        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('kv'));

        if ($this->_bindingType !== NULL)
            $sql->AddEqual('kvBindingValue', $this->_bindingValue, $this->_bindingType, IBC1_LOGICAL_AND);

        if (!empty($this->_key)) {
            if ($this->_keyExact)
                $sql->AddEqual('kvKey', $this->_key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $sql->AddLike('kvKey', $this->_key, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }

        $this->_service->ListRecords($this, NULL, $sql);
    }

}
