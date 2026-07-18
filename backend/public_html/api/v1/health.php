<?php
declare(strict_types=1);
require dirname(dirname(dirname(__DIR__))) . '/hoteltv/bootstrap/app.php';
hoteltv_json_response(array('status'=>'ok','version'=>$config['version']),200);
