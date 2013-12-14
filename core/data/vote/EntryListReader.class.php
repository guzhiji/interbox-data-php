<?php

/**
 *
 * @version 0.1
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.vote
 */
class EntryListReader extends DataList {

    function __construct(DBConnProvider $Conns, $ServiceName, ErrorList $EL=NULL) {
        parent::__construct($EL);
        parent::OpenService($Conns, $ServiceName, "vot");
        $this->GetError()->SetSource(__CLASS__);
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
        $sql->AddField("entVoteID", "VoteID");
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
        return TRUE;
    }

}

?>
