<?php

/**
 * 
 * @version 0.1.20130119
 * @author Zhiji Gu <gu_zhiji@163.com>
 * @copyright &copy; 2010-2013 InterBox Core 1.2 for PHP, GuZhiji Studio
 * @package interbox.core.data
 */
interface IPagination {

    public function GetPageSize();

    public function SetPageSize($s);

    public function GetPageNumber();

    public function SetPageNumber($n);

    public function GetPageCount();

    public function GetTotalCount();
}
