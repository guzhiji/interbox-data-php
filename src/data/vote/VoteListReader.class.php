<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.vote
 */
class VoteListReader extends DataList {

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "vot");
        $this->GetError()->SetSource(__CLASS__);
    }

    private function AddFields(&$sql) {
        $sql->AddField("votID", "ID");
        $sql->AddField("votCaption", "Caption");
        $sql->AddField("votStartTime", "StartTime");
        $sql->AddField("votEndTime", "EndTime");
        $sql->AddField("votMaxNumber", "MaxNumber");
        $sql->AddField("votMinNumber", "MinNumber");
        $sql->AddField("votContentID", "ContentID");
    }

    public function AddVote($ID) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $this->AddFields($sql);
        $sql->AddEqual("votID", $ID);

        $sql->Execute();
        $r = $sql->Fetch(1);
        $sql->CloseSTMT();
        if ($r) {
            $this->AddItem($r);
            return TRUE;
        }
        return FALSE;
    }

    public function OpenAll() {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddField("COUNT(votID)", "c");
        $this->GetCounts1($sql);
        $sql->ClearFields();
        $this->AddFields($sql);
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());
        $this->Clear();
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
        return TRUE;
    }

    public function OpenByTime($StartTime, $EndTime) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddField("COUNT(votID)", "c");
        $dfm = new DataFormatter();
        $dfm->SetData($StartTime, IBC1_DATATYPE_DATE);
        $StartTime = $dfm->GetData();
        $dfm->SetData($EndTime, IBC1_DATATYPE_DATE);
        $EndTime = $dfm->GetData();
        $sql->AddCondition("TO_DAYS(StartTime)>=TO_DAYS('$StartTime')");
        $sql->AddCondition("TO_DAYS(EndTime)<=TO_DAYS('$EndTime')", IBC1_LOGICAL_AND);
        $this->GetCounts1($sql);
        $sql->ClearFields();
        $this->AddFields($sql);
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());
        $this->Clear();
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
        return TRUE;
    }

    public function OpenByCaption($caption, $exact) {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_vote");
        $sql->AddField("COUNT(votID)", "c");
        if ($exact)
            $sql->AddEqual("Caption", $caption, IBC1_DATATYPE_PLAINTEXT);
        else
            $sql->AddLike("Caption", $caption, IBC1_DATATYPE_PLAINTEXT);
        $this->GetCounts1($sql);
        $sql->ClearFields();
        $this->AddFields($sql);
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());
        $this->Clear();
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
    }

}

?>
