<?php

LoadIBC1Class('ItemList', 'util');
LoadIBC1Class('IPagination', 'data');
LoadIBC1Class('Paginator', 'data');

/**
 * a read-only list of contents.
 * 
 * @version 0.8.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class ContentListReader extends ItemList implements IPagination {

    protected $_sql = NULL;
    protected $_service;
    protected $_fields;
    protected $_paginator;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'catalog');
        $conn = $this->_service->GetDBConn();
        $this->_sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('content'));
        $this->_fields = array(
            'cntID' => 'ID',
            'cntName' => 'Name',
            'cntCatalogID' => 'CatalogID',
            'cntAuthor' => 'Author',
            'cntKeywords' => 'Keywords',
            'cntTimeCreated' => 'TimeCreated',
            'cntTimeUpdated' => 'TimeUpdated',
            'cntTimeVisited' => 'TimeVisited',
            'cntUID' => 'UID',
            'cntVisitCount' => 'VisitCount',
            'cntVisitLevel' => 'VisitLevel',
            'cntAdminLevel' => 'AdminLevel',
            'cntWorth' => 'Worth',
            'cntModule' => 'Module'
        );
        $this->_paginator = new Paginator();
    }

    public function GetDataService() {
        return $this->_service;
    }

    /**
     * get a content object.
     * 
     * @param int $id   content id
     * @return object
     */
    public function GetContent($id) {
        $r = $this->_service->ReadRecord('content', 'cntID', $id, $this->_fields);
        if (!$r) {
            throw new ServiceException("not exist: $id");
        }
        return $r;
    }

    /**
     * get content using ID and add it into the list.
     * 
     * only support single-page list
     * (it is a single-page list when PageSize=0)
     * @param int $id   content id
     */
    public function LoadContent($id) {
        if ($this->GetPageSize() != 0 || $this->GetPageNumber() > 1) {
            throw new ServiceException('only support single-page list,it is a single-page list when PageSize=0');
        }

        $r = $this->GetContent($id);
        if ($r) {
            $this->AddItem($r);
        }
    }

    /**
     * set name as criteria for loading the list.
     * 
     * @param string $name  content name, a proposed condition on the Name field
     * @param bool $exact optional, the default value is FALSE 
     * such that an exact match is NOT enforced
     */
    public function SetName($name, $exact = FALSE) {
        $sql = $this->_sql;
        if ($exact)
            $sql->AddEqual('cntName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        else
            $sql->AddLike('cntName', $name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
    }

    /**
     * set catalog id as criteria for loading the list.
     * 
     * @param int $id  catalog id
     */
    public function SetCatalog($id) {
        if ($id != 0)
            $this->_sql->AddEqual('cntCatalogID', $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND); //or/and
    }

    /**
     * set keywords as criteria for loading the list.
     * 
     * @param string $keywords
     */
    public function SetKeywords($keywords) {
        $sql = $this->_sql;
        LoadIBC1Class('WordList', 'util');
        $wl = new WordList($keywords);
        while ($item = $wl->GetEach()) {
            if ($item != '')
                $sql->AddLike('cntKeywords', $item, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
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
            $this->_sql->AddCondition('cntVisitLevel<0');
        else
            $this->_sql->AddCondition('cntVisitLevel>=' . $level);
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
                $this->_sql->AddCondition('cntAdminLevel<0');
            else
                $this->_sql->AddCondition('cntAdminLevel>=' . $level);
        }
    }

    public function SetUID($UID) {
        if (!empty($UID)) {
            $this->_sql->AddEqual('cntUID', $UID, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
    }

    public function SetModule($module) {
        if (!empty($module)) {
            $this->_sql->AddEqual('cntModule', $module, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }
    }

    public function OrderBy($fieldname, $order = IBC1_ORDER_ASC) {
        $sql = $this->_sql;
        if ($order != IBC1_ORDER_ASC)
            $order = IBC1_ORDER_DESC;
        switch ($fieldname) {
            case 'name':
                $sql->OrderBy('cntName', $order);
                break;
            case 'ordinal':
                $sql->OrderBy('cntOrdinal', $order);
                break;
            case 'author':
                $sql->OrderBy('cntAuthor', $order);
                break;
            case 'ctime':
                $sql->OrderBy('cntTimeCreated', $order);
                break;
            case 'utime':
                $sql->OrderBy('cntTimeUpdated', $order);
                break;
            case 'vtime':
                $sql->OrderBy('cntTimeVisited', $order);
                break;
            case 'worth':
                $sql->OrderBy('cntWorth', $order);
                break;

            default:
                throw new ServiceException('not supported');
        }
    }

    /**
     * <code>
     * LoadIBC1Class('ContentListReader', 'data.catalog');
     * $reader=new ContentListReader('catalogtest');
     * $reader->SetCatalog(1);
     * $reader->LoadList();
     * $reader->MoveFirst();
     * while($item=$reader->GetEach()){
     *     var_dump($item);
     *     echo "<hr />\n";
     * }
     * </code>
     * @throws Exception 
     */
    public function LoadList() {
        $this->_paginator->GetCounts1($this->_sql);
        $this->_service->ListRecords($this, $this->_paginator, $this->_sql, $this->_fields);
        $this->_paginator->GetCounts2($this->Count());
    }

    public function OpenCatalog($ID) {
        $this->SetCatalog($ID);
        $this->LoadList();
    }

    public function OpenWithKeys($KeyText) {
        $this->SetKeywords($KeyText);
        $this->LoadList();
    }

    public function OpenWithUID($UID) {
        $this->SetUID($UID);
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
