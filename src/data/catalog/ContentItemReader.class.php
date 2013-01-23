<?php

LoadIBC1Class('PropertyList', 'util');

/**
 *
 * @version 0.7.20130123
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data.catalog
 */
class ContentItemReader extends PropertyList {

    protected $_service;
    protected $_fields;

    function __construct($service) {
        $this->_service = DataService::GetService($service, 'catalog');
        $this->_fields = array(
            'cntID' => 'ID',
            'cntName' => 'Name',
            'cntCatalogID' => 'CatalogID',
            'clgName' => 'CatalogName',
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
    }

    public function GetDataService() {
        return $this->_service;
    }

    /**
     * <code>
     * LoadIBC1Class('ContentItemReader', 'data.catalog');
     * $reader=new ContentItemReader('catalogtest');
     * if($reader->Open($id)){
     *      echo $reader->GetID()."\n";
     *      echo $reader->GetName()."\n";
     * }
     * </code>
     * @param int $ID
     * @return boolean 
     */
    public function Open($ID) {

        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateSelectSTMT($this->_service->GetDataTableName('content'));
        $sql->JoinTable($this->_service->GetDataTableName('catalog'), 'cntCatalogID=clgID');
        foreach ($this->_fields as $field => $alias)
            $sql->AddField($field, $alias);
        $sql->AddEqual('cntID', $ID);
        $sql->Execute();
        $row = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($row) {
            $this->SetValue('ID', $row->ID, IBC1_DATATYPE_INTEGER);
            $this->SetValue('Name', $row->Name, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('CatalogID', $row->CatalogID, IBC1_DATATYPE_INTEGER);
            $this->SetValue('CatalogName', $row->CatalogName, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('Author', $row->Author, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('Keywords', $row->Keywords, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeCreated', $row->TimeCreated, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeUpdated', $row->TimeUpdated, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeVisited', $row->TimeVisited, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('UID', $row->UID, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('VisitCount', $row->VisitCount, IBC1_DATATYPE_INTEGER);
            $this->SetValue('VisitLevel', $row->VisitLevel, IBC1_DATATYPE_INTEGER);
            $this->SetValue('AdminLevel', $row->AdminLevel, IBC1_DATATYPE_INTEGER);
            $this->SetValue('Worth', $row->Worth, IBC1_DATATYPE_INTEGER);
            $this->SetValue('Module', $row->Module, IBC1_DATATYPE_PLAINTEXT);
            return TRUE;
        }
        return FALSE;
    }

    public function GetID() {
        return $this->GetValue('ID');
    }

    public function GetName() {
        return $this->GetValue('Name');
    }

    public function GetCatalogID() {
        return $this->GetValue('CatalogID');
    }

    public function GetCatalogName() {
        return $this->GetValue('CatalogName');
    }

    public function GetAuthor() {
        return $this->GetValue('Author');
    }

    public function GetKeywords() {
        return $this->GetValue('Keywords');
    }

    public function GetTimeCreated() {
        return $this->GetValue('TimeCreated');
    }

    public function GetTimeUpdated() {
        return $this->GetValue('TimeUpdated');
    }

    public function GetTimeVisited() {
        return $this->GetValue('TimeVisited');
    }

    public function GetUID() {
        return $this->GetValue('UID');
    }

    public function GetVisitCount() {
        return $this->GetValue('VisitCount');
    }

    public function GetVisitLevel() {
        return $this->GetValue('VisitLevel');
    }

    public function GetAdminLevel() {
        return $this->GetValue('AdminLevel');
    }

    public function GetWorth() {
        return $this->GetValue('Worth');
    }

    public function GetModule() {
        return $this->GetValue('Module');
    }

    public function AddVisitCount() {
        $conn = $this->_service->GetDBConn();
        $sql = $conn->CreateUpdateSTMT($this->_service->GetDataTableName('content'));
        $sql->AddValue('cntTimeVisited', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
        $sql->AddValue('cntVisitCount', 'cntVisitCount+1', IBC1_DATATYPE_EXPRESSION);
        $sql->AddEqual('cntID', $this->GetValue('ID'));
        $r = $sql->Execute();
        $sql->CloseSTMT();
        return $r;
    }

}
