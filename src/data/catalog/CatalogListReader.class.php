<?php

LoadIBC1Class('ItemList', 'util');
LoadIBC1Class('IPagination', 'data');
LoadIBC1Class('Paginator', 'data');

/**
 * a read-only list of catalogs.
 * 
 * @version 0.8.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class CatalogListReader extends ItemList implements IPagination {

    protected $_sql = NULL;
    protected $_service;
    protected $_fields;
    protected $_paginator;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'catalog');
        $conn = $this->_service->GetDBConn();
        $this->_sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('catalog'));
        $this->_fields = array(
            'clgID' => 'ID',
            'clgName' => 'Name',
            'clgOrdinal' => 'Ordinal',
            'clgUID' => 'UID',
            'clgParentID' => 'ParentID',
            'clgVisitLevel' => 'VisitLevel',
            'clgAdminLevel' => 'AdminLevel'
        );
        $this->_paginator = new Paginator();
    }

    public function GetDataService() {
        return $this->_service;
    }

    /**
     * get basic attributes of a catalog
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','data.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * try{
     *      var_dump($reader->GetCatalog(1));
     * } catch (Exception $ex) {
     *      echo 'not found';
     * }
     * </code>
     * 
     * @param int $id catalog id
     * @return object
     */
    public function GetCatalog($id) {
        $r = $this->_service->ReadRecord('catalog', 'clgID', $id, $this->_fields);
        if (!$r) {
            throw new ServiceException("not exist:$id");
        }
        return $r;
    }

    /**
     * get a catalog and add it into the list as an item
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','data.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * $reader->LoadCatalog(2);
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *      var_dump($item);
     *      echo "<hr />\n";
     * }
     * </code>
     * only support single-page list,it is a single-page list when PageSize=0
     * @param int $id  catalog id
     */
    public function LoadCatalog($id) {

        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $this->AddItem($this->GetCatalog($id));
    }

    /**
     * load catalogs in the path  from the given catalog to 
     * the leaf node of the hierarchy.
     * 
     * @param int $id  catalog id of the root
     */
    public function LoadPath($id) {

        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list');
        }

        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('catalog'));
        foreach ($this->_fields as $f => $alias)
            $sql->AddField($f, $alias);
        $sql->AddEqual('clgID', $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if (!$r)
            throw new ServiceException("not exist:{$id}");
        $this->AddItem($r);
        while ($r->ParentID > 0) {
            $sql->ClearConditions();
            $sql->AddEqual('clgID', $r->ParentID);
            $sql->Execute();
            $r = $sql->Fetch(1);
            $sql->CloseSTMT();
            if (!$r) {
                throw new ServiceException("not exist:{$r->ParentID}");
            }
            $this->AddItem($r);
        }
    }

    /**
     * set name as criteria for loading the list.
     * 
     * @param string $name  catalog name, a proposed condition on the Name field
     * @param bool $exact optional, the default value is FALSE 
     * such that an exact match is NOT enforced
     */
    public function SetName($name, $exact = FALSE) {
        if ($name != '') {
            if ($exact)
                $this->_sql->AddEqual('clgName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $this->_sql->AddLike('clgName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
    }

    /**
     * set parent catalog as criteria for loading the list.
     * 
     * @param int $id catalog id, a proposed condition on parent catalog
     */
    public function SetParentCatalog($id) {
        if ($id >= 0) {
            $this->_sql->AddEqual('clgParentID', $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        }
    }

    /**
     * set a minimal user level for visitors as criteria for loading the list.
     * 
     * <ul>
     * <li>level=0 - visible to everyone</li>
     * <li>level>0 - visible to users with or with higher levels</li>
     * <li>level=-1 - invisible to anyone except the author</li>
     * </ul>
     * @param int $level
     */
    public function SetVisitLevel($level) {
        $level = intval($level);
        if ($level < 0)
            $this->_sql->AddCondition('clgVisitLevel<0');
        else
            $this->_sql->AddCondition('clgVisitLevel>=' . $level);
    }

    /**
     * set a minimal user level for admins as criteria for loading the list.
     *
     * <ul>
     * <li>level>0 - admin by users with or with higher levels</li>
     * <li>level=-1 - only the author can admin</li>
     * </ul>
     * @param int $level
     */
    public function SetAdminLevel($level) {
        $level = intval($level);
        if ($level != 0) {
            if ($level < 0)
                $this->_sql->AddCondition('clgAdminLevel<0');
            else
                $this->_sql->AddCondition('clgAdminLevel>=' . $level);
        }
    }

    public function OrderBy($fieldname, $order) {
        if ($order != IBC1_ORDER_ASC)
            $order = IBC1_ORDER_DESC;
        switch ($fieldname) {
            case 'name':
                $this->_sql->OrderBy('clgName', $order);
                break;
            case 'ordinal':
                $this->_sql->OrderBy('clgOrdinal', $order);
                break;

            default:
                throw new ServiceException('not supported');
        }
    }

    /**
     * load the list.
     * 
     * <code>
     * LoadIBC1Class('CatalogListReader','data.catalog');
     * $reader=new CatalogListReader('catalogtest');
     * $reader->LoadList();
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *      var_dump($item);
     *      echo "<hr />\n";
     * }
     * </code>
     */
    public function LoadList() {
        $this->_paginator->GetCounts1($this->_sql);
        $this->_service->ListRecords($this, $this->_paginator, $this->_sql, $this->_fields);
        $this->_paginator->GetCounts2($this->Count());
    }

    public function OpenSubCatalog($ID) {
        $this->SetParentCatalog($ID);
        $this->LoadList();
    }

    public function GetPageCount() {
        return $this->_paginator->PageCount;
    }

    public function GetPageNumber() {
        return $this->_paginator->PageNumber;
    }

    public function GetPageSize() {
        return $this->_paginator->PageSize;
    }

    public function GetTotalCount() {
        return $this->_paginator->TotalCount;
    }

    public function SetPageNumber($n) {
        $this->_paginator->SetPageNumber($n);
    }

    public function SetPageSize($s) {
        $this->_paginator->SetPageSize($s);
    }

}
