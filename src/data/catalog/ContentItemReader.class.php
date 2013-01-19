<?php

LoadIBC1Class('DataItem', 'datamodels');

/**
 *
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class ContentItemReader extends DataItem {

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, 'catalog');
    }

    /**
     * <code>
     * LoadIBC1Class('ContentItemReader', 'datamodels.catalog');
     * $reader=new ContentItemReader('catalogtest');
     * if($reader->Open($id)){
     *      echo $reader->GetID()."\n";
     *      echo $reader->GetName()."\n";
     * }
     * $reader->CloseService();
     * </code>
     * @param int $ID
     * @return boolean 
     */
    public function Open($ID) {

        $conn = $this->GetDBConn();

        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('content'));
        $sql->JoinTable($this->GetDataTableName('catalog'), 'cntCatalogID=clgID');
        $sql->AddField('cntID');
        $sql->AddField('cntName');
        $sql->AddField('cntCatalogID');
        $sql->AddField('clgName AS CatalogName');
        $sql->AddField('cntAuthor');
        $sql->AddField('cntKeywords');
        $sql->AddField('DATE_FORMAT(cntTimeCreated,"%Y-%m-%d %H:%i:%s")', 'TimeCreated');
        $sql->AddField('DATE_FORMAT(cntTimeUpdated,"%Y-%m-%d %H:%i:%s")', 'TimeUpdated');
        $sql->AddField('DATE_FORMAT(cntTimeVisited,"%Y-%m-%d %H:%i:%s")', 'TimeVisited');
        $sql->AddField('cntUID');
        $sql->AddField('cntVisitCount');
        $sql->AddField('cntVisitLevel');
        $sql->AddField('cntAdminLevel');
        $sql->AddField('cntPointValue');
        $sql->AddEqual('cntID', $ID);
        $sql->Execute();
        $row = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($row) {
            $this->SetValue('ID', $row->cntID, IBC1_DATATYPE_INTEGER);
            $this->SetValue('Name', $row->cntName, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('CatalogID', $row->cntCatalogID, IBC1_DATATYPE_INTEGER);
            $this->SetValue('CatalogName', $row->CatalogName, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('Author', $row->cntAuthor, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('Keywords', $row->cntKeywords, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeCreated', $row->TimeCreated, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeUpdated', $row->TimeUpdated, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('TimeVisited', $row->TimeVisited, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('UID', $row->cntUID, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue('VisitCount', $row->cntVisitCount, IBC1_DATATYPE_INTEGER);
            $this->SetValue('VisitGrade', $row->cntVisitLevel, IBC1_DATATYPE_INTEGER);
            $this->SetValue('AdminGrade', $row->cntAdminLevel, IBC1_DATATYPE_INTEGER);
            $this->SetValue('PointValue', $row->cntPointValue, IBC1_DATATYPE_INTEGER);
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

    public function GetPointValue() {
        return $this->GetValue('PointValue');
    }

    public function AddVisitCount() {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT($this->GetDataTableName('content'));
        $sql->AddValue('cntTimeVisited', 'CURRENT_TIMESTAMP()', IBC1_DATATYPE_EXPRESSION);
        $sql->AddValue('cntVisitCount', 'cntVisitCount+1', IBC1_DATATYPE_EXPRESSION);
        $sql->AddEqual('cntID', $this->GetValue('ID'));
        $r = $sql->Execute();
        $sql->CloseSTMT();
        return $r;
    }

}
