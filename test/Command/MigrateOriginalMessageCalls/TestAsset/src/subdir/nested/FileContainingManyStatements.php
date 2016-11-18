<?php
/**
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

$originalPath = $request->getOriginalRequest()->getUri()->getPath();

$middleware = function ($req, $res, $next) {
    $original = $req->getOriginalRequest();
    return $res;
};

$originalScheme = $request->getOriginalUri()->getScheme();

$middleware = function ($req, $res, $next) {
    $originalUri = $req->getOriginalUri();
    $originalRequest = $req->getOriginalRequest();
    $response = $res->getOriginalResponse();
    return $response;
};
