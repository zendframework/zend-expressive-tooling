<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-tooling for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-tooling/blob/master/LICENSE.md New BSD License
 */

$originalPath = $request->getAttribute('originalRequest', $request)->getUri()->getPath();

$middleware = function ($req, $res, $next) {
    $original = $req->getAttribute('originalRequest', $req);
    return $res;
};

$originalScheme = $request->getAttribute('originalUri', $request->getUri())->getScheme();

$middleware = function ($req, $res, $next) {
    $originalUri = $req->getAttribute('originalUri', $req->getUri());
    $originalRequest = $req->getAttribute('originalRequest', $req);
    $response = $res->getOriginalResponse();
    return $response;
};
