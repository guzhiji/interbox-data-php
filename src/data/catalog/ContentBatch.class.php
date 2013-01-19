<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 * TODO batch
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.catalog
 */
class ContentBatch extends DataList {//ContentListReader

    private $keywords = NULL;
    private $cid = 0;
    private $uid = "";
    private $name = "";
    private $nameExact = FALSE;

    function __construct($ServiceName) {
        parent::__construct();
        $this->OpenService($ServiceName);
    }

    public function OpenService($ServiceName) {
        parent::OpenService($ServiceName, "catalog");
    }

    public function AddID($id) {
        $this->AddItem($id);
    }

    public function SetName($name, $exact = FALSE) {
        $this->name = $name;
        $this->nameExact = $exact;
    }

    public function SetKeywords($KeyText) {
        LoadIBC1Class("WordList", "util");
        $this->keywords = new WordList($KeyText);
    }

    public function SetCatalog($ID) {
        $this->cid = intval($ID);
    }

    public function SetAdmin($UID) {
        $this->uid = $UID;
    }

    private function SetSQLObject(IFieldExpList $sql) {
        $sql->ClearConditions();
        if ($this->Count() > 0) {
            while ($id = $this->GetEach()) {
                $sql->AddEqual("cntID", $id, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_OR);
            }
        } else if ($this->cid != 0) {
            $sql->AddEqual("cntCatalogID", $this->cid, IBC1_DATATYPE_INTEGER, IBC1_LOGICAL_AND);
        } else if ($this->uid != "") {
            $sql->AddEqual("cntUID", $this->uid, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        } else if ($this->name != "") {
            if ($this->nameExact) {
                $sql->AddEqual("cntName", $this->name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            } else {
                $sql->AddLike("cntName", $this->name, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            }
        } else if ($this->keywords != NULL) {
            while ($item = $this->keywords->GetEach()) {
                if ($item == "")
                    continue;
                $sql->AddLike("cntKeywords", $item, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            }
        }
    }

    public function Delete() {

        $conn = $this->GetDBConn();
        $sql = $this->SetSQLObject($conn->CreateDeleteSTMT($this->GetDataTableName("content")));
        $r = $sql->Execute();
        $sql->CloseSTMT();
        return $r;
    }

    public function MoveTo($CatalogID) {

        $conn = $this->GetDBConn();
        $sql = $conn->CreateSeleteSTMT($this->GetDataTableName("catalog"));
        $sql->AddField("clgID");
        $sql->AddEqual("clgID", $CatalogID, IBC1_DATATYPE_INTEGER);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $sql = $this->SetSQLObject($conn->CreateUpdateSTMT($this->GetDataTableName("content")));
            if ($sql->ConditionCount() > 0) {
                if (!$sql->Execute()) {
                    $sql->CloseSTMT();

                    return FALSE;
                }
                return TRUE;
            }
        }

        return FALSE;
    }

    public function LoadList() {
        
    }

}
