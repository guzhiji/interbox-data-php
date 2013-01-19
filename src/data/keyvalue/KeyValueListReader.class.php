<?php

LoadIBC1Class('DataList', 'datamodels');

/**
 *
 * @version 0.6
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2012 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.datamodels.keyvalue
 */
class KeyValueListReader extends DataList {

    private $bindingValue;
    private $bindingType;
    private $keyName;
    private $keyNameExact;

    function __construct($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::__construct();
        $this->OpenService($ServiceName, $BindingValue, $BindingType);
    }

    public function OpenService($ServiceName, $BindingValue = NULL, $BindingType = IBC1_DATATYPE_INTEGER) {
        parent::OpenService($ServiceName, 'keyvalue');
        $this->bindingValue = $BindingValue;
        $this->bindingType = $BindingType;
    }

    public function SetKeyName($name, $exact = TRUE) {
        $this->keyName = $name;
        $this->keyNameExact = $exact;
    }

    public function LoadList($includeValue = FALSE) {
        if (!$this->IsServiceOpen()) {
            throw new ServiceException('service is not open');
        }
        $conn = $this->GetDBConn();

        $sql = $conn->CreateSelectSTMT($this->GetDataTableName('list'));
        if (!empty($this->bindingValue))
            $sql->AddEqual('kvBindingValue', $this->bindingValue, $this->bindingType, IBC1_LOGICAL_AND);
        if (!empty($this->keyName)) {
            if ($this->keyNameExact)
                $sql->AddEqual('kvKey', $this->keyName, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
            else
                $sql->AddLike('kvKey', $this->keyName, IBC1_DATATYPE_PLAINTEXT, IBC1_LOGICAL_AND);
        }

        $sql->AddField('COUNT(kvID)');

        $this->GetCounts1($sql);

        $sql->ClearFields();
        $sql->AddField('kvID');
        $sql->AddField('kvKey');
        if ($includeValue)
            $sql->AddField('kvValue');
        $sql->SetLimit($this->GetPageSize(), $this->GetPageNumber());

        $sql->Execute();

        $this->Clear();

        while ($r = $sql->Fetch(1)) {
            $this->AddItem($r);
        }

        $this->GetCounts2();
        $sql->CloseSTMT();
    }

}
