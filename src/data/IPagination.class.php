<?php

interface IPagination {

    public function GetPageSize();

    public function SetPageSize($s);

    public function GetPageNumber();

    public function SetPageNumber($n);

    public function GetPageCount();

    public function GetTotalCount();
}

?>
