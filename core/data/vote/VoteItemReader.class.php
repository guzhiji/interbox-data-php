<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.vote
 */
class VoteItemReader extends DataItem {

    private $id;

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        $this->OpenService($Conns, $ServiceName, "vot");
        $this->GetError()->SetSource(__CLASS__);
    }

    public function GetCaption() {
        return $this->GetValue("Caption");
    }

    public function GetStartTime() {
        return $this->GetValue("StartTime");
    }

    public function GetEndTime() {
        return $this->GetValue("EndTime");
    }

    public function GetMinNumber() {
        return $this->GetValue("MinNumber");
    }

    public function GetMaxNumber() {
        return $this->GetValue("MaxNumber");
    }

    public function GetContentID() {
        return $this->GetValue("ContentID");
    }

    public function GetEntryList() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        LoadIBC1Class("EntryListReader", "vote");
        $elr = new EntryListReader($this->GetDBConnProvider(), $this->GetServiceName(), $this->GetError());
        $elr->OpenVote($this->id);
        return $elr;
    }

    public function Open($id) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddField("votCaption", "Caption");
        $sql->AddField("votStartTime", "StartTime");
        $sql->AddField("votEndTime", "EndTime");
        $sql->AddField("votMaxNumber", "MaxNumber");
        $sql->AddField("votMinNumber", "MinNumber");
        $sql->AddField("votContentID", "ContentID");
        $sql->AddField("votCatalogService", "CatalogService");
        $sql->AddEqual("votID", $id);
        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $this->id = $id;
            $this->SetValue("Caption", $r->Caption, IBC1_DATATYPE_PLAINTEXT);
            $this->SetValue("StartTime", $r->StartTime, IBC1_DATATYPE_DATETIME);
            $this->SetValue("EndTime", $r->EndTime, IBC1_DATATYPE_DATETIME);
            $this->SetValue("MaxNumber", $r->MaxNumber, IBC1_DATATYPE_INTEGER);
            $this->SetValue("MinNumber", $r->MinNumber, IBC1_DATATYPE_INTEGER);
            $this->SetValue("ContentID", $r->ContentID, IBC1_DATATYPE_INTEGER);
            $this->SetValue("CatalogService", $r->CatalogService, IBC1_DATATYPE_PLAINTEXT);
            return TRUE;
        }
        return FALSE;
    }

    public function DoVote($entryidlist) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $c = count($entryidlist);
        $max = intval($this->GetValue("MaxNumber")); //=-1 means unlimited
        $min = intval($this->GetValue("MinNumber"));
        if ($c >= $min && $c > 0 && ($max < 0 || $max >= $c)) {
            $this->GetError()->AddItem(1, "number out of range");
            return FALSE;
        }
        $s = strtotime($this->GetValue("StartTime"));
        $e = strtotime($this->GetValue("EndTime")); //=False or =-1 means unlimited
        $n = time();
        if ($e < $n && $e > 0 || $s > $n) {
            $this->GetError()->AddItem(1, "time out of range");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT("ibc1_vot" . $this->GetServiceName() . "_entry");
        $sql->AddValue("entValue", "entValue+1", IBC1_DATATYPE_EXPRESSION);
        $sql->AddEqual("entVoteID", $this->id, IBC1_LOGICAL_AND);
        //$sql->AddCondition("TO_DAYS(StartTime)>=TO_DAYS('" . $this->GetValue("StartTime") . "')", IBC1_LOGICAL_AND);
        //$sql->AddCondition("TO_DAYS(EndTime)<=TO_DAYS('" . $this->GetValue("EndTime") . "')", IBC1_LOGICAL_AND);
        foreach ($entryidlist as $id) {
            //non-repeat
            $sql->AddEqual("entID", $id, IBC1_LOGICAL_OR);
        }
        $r = $sql->Execute();
        $sql->CloseSTMT();
        if (!$r) {
            $this->GetError()->AddItem(1, "err in execution");
            return FALSE;
        }
        return TRUE;
    }

}

?>
