<?php

/**
 * 
 * @version 0.3.20130119
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data
 */
class Paginator {

    public $PageSize = 0;
    public $PageNumber = 0;
    public $PageCount = 0;
    public $TotalCount = 0;

    public function SetPageSize($s) {
        $s = intval($s);
        if ($s < 1)
            $s = 0;
        $this->PageSize = $s;
    }

    public function SetPageNumber($n) {
        $n = intval($n);
        if ($n < 1)
            $n = 1;
        $this->PageNumber = $n;
    }

    public function GetCounts1(DBSQLSTMT $sql) {
        if ($this->PageSize > 0) {
            $sql->ClearFields();
            $sql->AddField('COUNT(*)');
            $sql->Execute();
            $a = $sql->Fetch(2);
            $sql->CloseSTMT();
            $this->TotalCount = intval($a[0]);
            $b = $this->TotalCount / $this->PageSize;
            if ($b > intval($b))
                $b = 1 + intval($b);
            $this->PageCount = $b;
        } else {
            $this->PageCount = 0;
            $this->TotalCount = 0;
        }
    }

    public function GetCounts2($listsize) {
        if ($this->PageSize < 1 && $listsize > 0) {
            $this->TotalCount = $listsize;
            $this->PageCount = 1;
        }
    }

}