<?php
class PageMeta {

    private $controller;
    private $action;

    private static $meta = [
        'pages' => [
            'docs' => [
                'title' => 'Documentation',
                'meta'  => 'Integrate your website with our service, receive voting callback, and more!'
            ],
            'updates' => [
                'title' => 'Update Log',
                'meta'  => 'All updates that have been pushed for the toplist, and a list of contributors.'
            ],
            'stats' => [
                'title' => 'Stats',
                'meta'  => 'Global statistics showing votes, user, and server counts.'
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'meta'  => 'Our terms of service.'
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'meta'  => 'Our privacy policy.'
            ]
        ],
        'ads' => [
            'index' => [
                'title' => 'Store',
                'meta' => 'Purchase premium and sponsored ad spots to give your server a boost!'
            ]
        ],
        'tools' => [
            'itemdb' => [
                'title' => 'Osrs Item DB',
                'meta' => 'An easy to use oldschool runescape item db that\'s always up to date.'
            ]
        ],
        'profile' => [
            'index' => [
                'title' => 'Profile',
                 'meta'  => 'Edit and add a new server, view payment history, stats, and more.'
            ],
            'payments' => [
                'title'  => 'Payments',
                 'meta'  => 'View your payment history.'
            ],
            'add' => [
                'title'  => 'Add Server',
                 'meta'  => 'Add a new server to the toplist'
            ],
            'edit' => [
                'title'  => 'Edit Server',
                 'meta'  => 'Edit an existing server on the toplist'
            ]
        ]
    ];

    public function __construct($controller, $action) {
        $this->controller = $controller;
        $this->action     = $action;
    }

    public function getMeta() {
        if (in_array($this->controller, array_keys(self::$meta))) {
            $actions = self::$meta[$this->controller];
            if (in_array($this->action, array_keys($actions))) {
                return self::$meta[$this->controller][$this->action];
            }
        }

        return [
            'title' => 'Servers',
            'meta'  => 'The most modern runescape private server toplist built to-date. Come join your favorite RSPS, or add your server today to start advertising with us!'
        ];
    }

}