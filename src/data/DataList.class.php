<?php

LoadIBC1Class('DataModel', 'datamodels');
LoadIBC1Class('ItemList', 'util');

/**
 *
 * @version 0.8.20111205
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.dataservices
 */
abstract class DataList extends DataModel {

    private $_PageSize = 0;
    private $_PageNumber = 0;
    private $_PageCount = 0;
    private $_TotalCount = 0;
    private $_items;

    function __construct() {
        $this->_items = new ItemList();
    }

    public function CloseService() {
        parent::CloseService();
        $this->Clear();
        $this->_PageSize = 0;
        $this->_PageNumber = 0;
    }

    public function GetPageSize() {
        return $this->_PageSize;
    }

    public function SetPageSize($s) {
        $s = intval($s);
        if ($s < 1)
            $s = 0;
        $this->_PageSize = $s;
    }

    public function GetPageNumber() {
        return $this->_PageNumber;
    }

    public function SetPageNumber($n) {
        $n = intval($n);
        if ($n < 1)
            $n = 1;
        $this->_PageNumber = $n;
    }

    public function GetPageCount() {
        return $this->_PageCount;
    }

    public function GetTotalCount() {
        return $this->_TotalCount;
    }

    protected function GetCounts1(DBSQLSTMT $sql) {
        //$conn = $this->GetDBConn();
        $this->_TotalCount = intval($this->_TotalCount);
        $this->_PageSize = intval($this->_PageSize);
        if ($this->_PageSize > 0) {
            $sql->Execute();
            $a = $sql->Fetch(2);
            $sql->CloseSTMT();
            $this->_TotalCount = intval($a[0]);
            $b = $this->_TotalCount / $this->_PageSize;
            if ($b > intval($b))
                $b = 1 + intval($b);
            $this->_PageCount = $b;
        }
        else {
            $this->_PageCount = 0;
            $this->_TotalCount = 0;
        }
    }

    protected function GetCounts2() {
        if ($this->_PageSize < 1 && count($this->_items) > 0) {
            $this->_TotalCount = count($this->_items);
            $this->_PageCount = 1;
        }
    }

    abstract function LoadList();

    public function GetItem($index = -1) {
        return $this->_items->GetItem($index);
    }

    public function GetIndex() {
        return $this->_items->GetIndex();
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

    public function ItemExists($index) {
        return $this->_items->ItemExists($index);
    }

    public function ValueExists($value) {
        return $this->_items->ValueExists($value);
    }

    protected function AddItem($item) {
        $this->_items->AddItem($item);
    }

    protected function Remove($index) {
        $this->_items->Remove($index);
    }

    public function Count() {
        return $this->_items->Count();
    }

    protected function Clear() {
        $this->_items->Clear();
    }

}
