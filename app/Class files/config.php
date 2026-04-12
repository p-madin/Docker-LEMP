<?php

include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/QueryBuilder.php");
include_once(__DIR__ . "/Security/SecurityValidation.php");
include_once(__DIR__ . "/session.php");
include_once(__DIR__ . "/dataGraph.php");
include_once(__DIR__ . "/Router/Request.php");
include_once(__DIR__ . "/Router/MiddlewareInterface.php");
include_once(__DIR__ . "/Router/Router.php");
include_once(__DIR__ . "/Router/ControllerInterface.php");

include_once(__DIR__ . "/errorHandler.php");
include_once(__DIR__ . "/SystemConfigController.php");
#include_once(__DIR__ . "/preinclude.php");
include_once(__DIR__ . "/hyperlink.php");
include_once(__DIR__ . "/xmlDom.php");
include_once(__DIR__ . "/xmlForm.php");
include_once(__DIR__ . "/DatabaseForm.php");
include_once(__DIR__ . "/formValidation.php");
include_once(__DIR__ . "/navbar.php");

include_once(__DIR__ . "/Security/RateLimiter.php");
include_once(__DIR__ . "/Security/DatabaseConfigMiddleware.php");
include_once(__DIR__ . "/Security/SessionMiddleware.php");
include_once(__DIR__ . "/Security/HttpActionMiddleware.php");
include_once(__DIR__ . "/Security/WafMiddleware.php");
include_once(__DIR__ . "/Security/CsrfMiddleware.php");
include_once(__DIR__ . "/Security/ExtranetMiddleware.php");
include_once(__DIR__ . "/Security/ViewDecorationMiddleware.php");


// Setup Router, Request, and global DOM
$router = new Router();
$request = new Request();
$dom = new xmlDom();
$formSchemas;

// Global Middlewares Pipeline (Execution Order)
// 1. Establish Database Connection & Fetch Config
$router->use(new DatabaseConfigMiddleware());

// 2. Establish Session Controller & Run PRG Handler
$router->use(new SessionMiddleware());

// 3. Security Checks (WAF, CSRF, etc.)
$router->use(new HttpActionMiddleware());
$router->use(new WafMiddleware());
$router->use(new ExtranetMiddleware());
$router->use(new CsrfMiddleware());

// 4. Final View Decoration (DOM/Navbar)
$router->use(new ViewDecorationMiddleware());