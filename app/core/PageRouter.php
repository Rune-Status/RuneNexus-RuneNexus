<?php

use Router\Router;

class PageRouter extends Router {

    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new PageRouter(web_root);
        }
        return self::$instance;
    }

    private $controller;
    private $method;
    private $params;

    public $route_paths = [];

    public function initRoutes() {

        $this->all('', function() {
            return $this->setRoute('index', 'index');
        });

        $this->all('out/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('index', 'out', ['id' => $id]);
        });

        /**
         * Voting
         */
        $this->post('vote/add', function() {
            return $this->setRoute('vote', 'addvote');
        });

        $this->all('vote/([0-9]+)/([A-Za-z0-9\-_]+)', function($serverId, $incentive) {
            return $this->setRoute('vote', 'index', ['serverId' => $serverId, 'incentive' => $incentive]);
        });

        $this->all('vote/([0-9]+)-([A-Za-z0-9\-]+)/([A-Za-z0-9\-_]+)', function($serverId, $title, $incentive) {
            return $this->setRoute('vote', 'index', ['serverId' => $serverId, 'incentive' => $incentive]);
        });


        /**
         * Pages
         */
        $this->all('stats', function() {
            return $this->setRoute('pages', 'stats');
        });
        
        $this->all('stats/([A-Za-z0-9\-]+)', function($rate) {
            return $this->setRoute('pages', 'stats', ['rate' => $rate]);
        });

        $this->all('docs', function() {
            return $this->setRoute('pages', 'docs');
        });

        $this->all('updates', function() {
            return $this->setRoute('pages', 'updates');
        });

        $this->post('commits', function() {
            return $this->setRoute('pages', 'commits');
        });

        $this->post('contributors', function() {
            return $this->setRoute('pages', 'contributors');
        });

        /**
         * Premium
         */
        $this->all('ads', function() {
            return $this->setRoute('ads', 'index');
        });

        $this->all('ads/button', function() {
            return $this->setRoute('ads', 'button');
        });

        $this->all('ads/process', function() {
            return $this->setRoute('ads', 'process');
        });
        
        $this->all('ads/verify', function() {
            return $this->setRoute('ads', 'verify');
        });

        /**
         * Admin Sponsors
         */
        $this->all('admin/sponsor', function() {
            return $this->setRoute('sponsor', 'index');
        });

        $this->all('admin/sponsor/add', function() {
            return $this->setRoute('sponsor', 'add');
        });

        $this->all('admin/sponsor/edit/([0-9]+)', function($id) {
            return $this->setRoute('sponsor', 'edit', ['id' => $id]);
        });
        
        $this->all('admin/sponsor/delete/([0-9]+)', function($id) {
            return $this->setRoute('sponsor', 'delete', ['id' => $id]);
        });

        /**
         * Admin Premium
         */
        $this->all('admin/premium', function() {
            return $this->setRoute('premium', 'index');
        });

        $this->all('admin/premium/add', function() {
            return $this->setRoute('premium', 'add');
        });

        $this->all('admin/premium/edit/([0-9]+)', function($id) {
            return $this->setRoute('premium', 'edit', ['id' => $id]);
        });
        
        $this->all('admin/premium/delete/([0-9]+)', function($id) {
            return $this->setRoute('premium', 'delete', ['id' => $id]);
        });

        /**
         * Admin Users
         */
        $this->all('admin/users', function() {
            return $this->setRoute('users', 'index');
        });
        $this->all('admin/users/([0-9]+)', function($page) {
            return $this->setRoute('users', 'index', ['page' => $page]);
        });
        $this->all('admin/users/banned', function() {
            return $this->setRoute('users', 'banned');
        });
        $this->all('admin/users/banned/([0-9]+)', function($page) {
            return $this->setRoute('users', 'banned', ['page' => $page]);
        });
        
        /**
         * Admin Reports
         */
        $this->all('admin/reports', function() {
            return $this->setRoute('admin', 'reports');
        });
        $this->all('admin/reports/([0-9]+)', function($page) {
            return $this->setRoute('admin', 'reports', ['page' => $page]);
        });
        $this->all('admin/reports/view/([0-9]+)', function($id) {
            return $this->setRoute('admin', 'viewreport', ['id' => $id]);
        });

        /**
         * Report
         */
        $this->all('report/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('report', 'index', ['id' => $id]);
        });

        /**
         * Discord OAuth
         */
        $this->all('discord', function() {
            return $this->setRoute('login', 'discord');
        });

        $this->all('login/token', function() {
            return $this->setRoute('login', 'token');
        });

        $this->get('discord/auth', function() {
            return $this->setRoute('login', 'index');
        });

        $this->post('discord/auth', function() {
            return $this->setRoute('login', 'token');
        });

        $this->get("logout", function() {
            return $this->setRoute('index', 'logout');
        });

        /**
         * Main List
         */
        $this->all('rev-([A-Za-z0-9]+)', function($revision) {
            return $this->setRoute('index', 'index', ['revision' => $revision, 'page' => 1]);
        });

        $this->all('rev-([A-Za-z0-9]+)/([0-9]+)', function($revision, $page) {
            return $this->setRoute('index', 'index', ['revision' => $revision, 'page' => $page]);
        });

        $this->all('([0-9]+)', function($page) {
            return $this->setRoute('index', 'index', ['revision' => null, 'page' => $page]);
        });

        /**
         * Server Details
         */
        $this->all('details/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('index', 'details', ['serverId' => $id, 'page' => 1]);
        });

        $this->all('details/([0-9]+)', function($id) {
            return $this->setRoute('index', 'details', ['serverId' => $id, 'page' => 1]);
        });

        $this->all('details/([0-9]+)-([A-Za-z0-9\-]+)/([A-Za-z0-9\-]+)', function($id, $title, $rate) {
            return $this->setRoute('index', 'details', ['serverId' => $id, 'rate' => $rate]);
        });

        $this->all('servers/view/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('index', 'details',  ['serverId' => $id, 'page' => 1]);
        });

        /**
         * Profile
         */
        $this->get("profile", function() {
            return $this->setRoute('profile', 'index');
        });

        $this->get("profile/stats", function() {
            return $this->setRoute('profile', 'stats');
        });

        $this->get("profile/payments", function() {
            return $this->setRoute('profile', 'payments');
        });

        $this->get("profile/payments/([0-9]+)", function($page = 1) {
            return $this->setRoute('profile', 'payments', [ 'page' => $page ]);
        });


        /**
         * Server Management
         */
        $this->all('profile/add', function() {
            return $this->setRoute('profile', 'add');
        });
        
        $this->all('profile/edit/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('profile', 'edit', ['id' => $id]);
        });

        $this->all('profile/delete/([0-9]+)-([A-Za-z0-9\-]+)', function($id, $title) {
            return $this->setRoute('profile', 'delete', ['id' => $id]);
        });

        $this->post('upload', function() {
            return $this->setRoute('profile', 'upload');
        });

        /**
         * Pages
         */
        $this->get("terms", function() {
            return $this->setRoute('pages', 'terms');
        });

        $this->get("privacy", function() {
            return $this->setRoute('pages', 'privacy');
        });

        $this->get("nyan", function() {
            return $this->setRoute('pages', 'nyan');
        });

        /**
         * Sponsor Spots
         */
        $this->get("sponsor", function() {
            return $this->setRoute('sponsor', 'index');
        });
        $this->all('sponsor/button', function() {
            return $this->setRoute('sponsor', 'button');
        });

        $this->all('sponsor/process', function() {
            return $this->setRoute('sponsor', 'process');
        });
        
        $this->all('sponsor/verify', function() {
            return $this->setRoute('sponsor', 'verify');
        });

        /**
         * Tools
         */
        $this->all('itemdb', function() {
            return $this->setRoute('tools', 'itemdb');
        });
        $this->all('tools/itemdb', function() {
            return $this->setRoute('tools', 'itemdb');
        });
        
        $this->all('tools/itemdb/search', function() {
            return $this->setRoute('tools', 'search');
        });

        /**
         * Admin
         */
        $this->all('admin', function() {
            return $this->setRoute('admin', 'index');
        });

        /**
         * Admin Payments
         */
        $this->all('admin/payments', function() {
            return $this->setRoute('payments', 'index', [ 'search' => null, 'page' => 1 ]);
        });

        $this->all('admin/payments/([0-9]+)', function($page) {
            return $this->setRoute('payments', 'index', [ 'search' => null, 'page' => $page]);
        });

        $this->all('admin/payments/([A-Za-z0-9\-_]+)', function($search) {
            return $this->setRoute('payments', 'index', ['search' => $search, 'page' => 1]);
        });

        $this->all('admin/payments/([A-Za-z0-9\-_]+)/([0-9]+)', function($search, $page) {
            return $this->setRoute('payments', 'index', ['search' => $search, 'page' => $page]);
        });
        
        /**
         * Admin Payments
         */
        $this->all('admin/servers', function() {
            return $this->setRoute('servers', 'index');
        });

        $this->all('admin/servers/([0-9]+)', function($page) {
            return $this->setRoute('servers', 'index', [ 'page' => $page ]);
        });

        $this->all('admin/servers/edit/([0-9]+)', function($sid) {
            return $this->setRoute('servers', 'edit', [ 'sid' => $sid ]);
        });


    }

    public function setRoute($controller, $method, $params = []) {
        $this->controller = $controller;
        $this->method = $method;
        $this->params = $params;

        return [$controller, $method, $params];
    }

    public function getController($formatted = false) {
        return $formatted ? ucfirst($this->controller).'Controller' : $this->controller;
    }

    public function getViewPath() {
        return $this->getController().'/'.$this->getMethod();
    }

    public function getMethod() {
        return $this->method;
    }

    public function getParams() {
        return $this->params;
    }

    public function getFullPath() {
        return $this->getUrl();
    }

    public function isSecure() {
        return
          (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    public function getUrl() {
        $baseUrl =  'http'.($this->isSecure() ? 's' : '').'://' . $_SERVER['HTTP_HOST'];
        return $baseUrl.web_root;
    }

    public function getCanonical() {
        $actual_link = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        return $actual_link;
    }
}