<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.vote
 */
class EntryListEditor extends DataList {

    private $vid;

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "vot");
        $this->GetError()->SetSource(__CLASS__);
    }

    public function AddEntry($text, $value=0) {
        $item = new stdClass();
        $item->ID = 0;
        $item->Text = $text;
        $item->Value = $value;
        $this->AddItem($item);
    }

    public function SetEntry($id, $text, $value) {
        for ($i = 0; $i < $this->Count(); $i++) {
            $item = $this->GetItem($i);
            if ($item->ID == $id) {
                $item->Text = $text;
                $item->Value = $value;
                break;
            }
        }
    }

    public function OpenVote($id) {
        if (!$this->IsServiceOpen()) {
            $this->GetError()->AddItem(1, "service is not open");
            return FALSE;
        }
        $conn = $this->GetDBConn();
        $sql = $conn->CreateSelectSTMT("ibc1_vot" . $this->GetServiceName() . "_entry");
        $sql->AddField("COUNT(*)", "c");
        $sql->AddEqual("entVoteID", $id);
        $this->GetCounts1($sql);
        $sql->ClearFields();
        $sql->AddField("entID", "ID");
        $sql->AddField("entText", "Text");
        $sql->AddField("entValue", "Value");
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());
        $this->Clear();
        $sql->Execute();
        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }
        $this->GetCounts2();
        $sql->CloseSTMT();
        $this->vid = $id;
        return TRUE;
    }

    public function SaveAsVote($caption, $startTime, $endTime) {
        LoadIBC1Class("VoteItemEditor", "vote");
        $vie = new VoteItemEditor($this->GetDBConnProvider(), $this->GetServiceName(), $this->GetError());
        $vie->Create();
        $vie->SetCaption($caption);
        $vie->SetTime($startTime, $endTime);
        $vie->SetNumber(1, -1);
        $vie->Save();
        $vid = $vie->GetID();
        $vie->CloseService();
        $conn = $this->GetDBConn();
        $sql = $conn->CreateInsertSTMT("ibc1_vot" . $this->GetServiceName() . "_entry");
        $this->MoveFirst();
        while ($item = $this->GetEach()) {
            $sql->ClearValues();
            $sql->AddValue("entVoteID", $this->vid, IBC1_DATATYPE_INTEGER);
            $sql->AddValue("entText", $item->Text, IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("entValue", $this->Value, IBC1_DATATYPE_INTEGER);
            $sql->Execute();
            $item->ID = $sql->GetLastInsertID();
            $sql->CloseSTMT();
        }
    }

    public function SaveChanges() {
        $conn = $this->GetDBConn();
        $sql = $conn->CreateUpdateSTMT("ibc1_vot" . $this->GetServiceName() . "_entry");
        $this->MoveFirst();
        while ($item = $this->GetEach()) {
            $sql->ClearConditions();
            $sql->AddEqual("entID", $item->ID, IBC1_DATATYPE_INTEGER);
            $sql->ClearValues();
            $sql->AddValue("entText", $item->Text, IBC1_DATATYPE_PLAINTEXT);
            $sql->AddValue("entValue", $this->Value, IBC1_DATATYPE_INTEGER);
            $sql->Execute();
            $sql->CloseSTMT();
        }
    }

}

?>
