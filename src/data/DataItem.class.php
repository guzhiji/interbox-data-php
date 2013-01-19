<?php

LoadIBC1Class('DataModel', 'datamodels');
LoadIBC1Class('PropertyList', 'util');

/**
 *
 * @version 0.8.20111205
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.dataservices
 */
abstract class DataItem extends DataModel {

    protected $_items;

    function __construct() {
        $this->_items = new PropertyList();
    }

    public function CloseService() {
        parent::CloseService();
        $this->Clear();
    }

    protected function SetValue($key, $value, $type = IBC1_DATATYPE_INTEGER) {
        $this->_items->SetValue($key, $value, $type);
    }

    protected function AppendValue($key, $value) {
        $this->_items->AppendValue($key, $value);
    }

    public function GetKey() {
        return $this->_items->GetKey();
    }

    public function GetValue($key = NULL, $mode = IBC1_VALUEMODE_VALUEONLY) {
        return $this->_items->GetValue($key, $mode);
    }

    public function GetEach() {
        return $this->_items->GetEach();
    }

    public function MoveFirst() {
        $this->_items->MoveFirst();
    }

    public function MoveNext() {
        $this->_items->MoveNext();
    }

    public function KeyExists($key) {
        return $this->_items->KeyExists($key);
    }

    public function ValueExists($value) {
        return $this->_items->ValueExists($value);
    }

    protected function Remove($key) {
        $this->_items->Remove($key);
    }

    protected function Clear() {
        $this->_items->Clear();
    }

    public function Count() {
        return $this->_items->Count();
    }

}
