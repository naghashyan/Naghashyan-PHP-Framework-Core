<?php
// Entry point for the Social Dashboard example using NGS

require_once(__DIR__ . '/../../src/NGS.class.php');

NGS()->setDispatcher(new \ngs\Dispatcher());
NGS()->getDispatcher()->dispatch();
