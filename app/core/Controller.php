<?php
use Fox\Request;
use Fox\Cookies;
use Fox\CSRF;
use Fox\Session;
use EasyCSRF\EasyCSRF;
use EasyCSRF\NativeSessionProvider;

class Controller {

	protected $view;
    protected $viewVars = array();
    protected $actionName;

    private $disableView;
    private $json_output;

    /** @var HTMLPurifier purifier */
    private $purifier;

    /** @var PageRouter */
    protected $router;

	/** @var Request $request */
	protected $request;

	/** @var Cookies $cookies */
    protected $cookies;
    
    /** @var Session $session */
    protected $session;

    /** @var EasyCSRF $csrf */
    protected $csrf;

    /** @var User $user */
    public $user;

    protected $access = [
        'login_required' => false,
        'roles'  => []
    ];
    
    public function beforeExecute() {
        $this->request  = Request::getInstance();
        $this->cookies  = Cookies::getInstance();
        $this->csrf     = new EasyCSRF(new NativeSessionProvider());
        $this->session  = Session::getInstance();

        $token = $this->cookies->get("access_token");
        $roles = ["Guest"];

        if ($token) {
            try {
                $discord = new Discord($this->filter($token));

                $discord->setEndpoint("/users/@me"); 
                $me = $discord->get();

                if (!$me || isset($me['code'])) {
                    $this->cookies->delete("access_token");
                    $this->redirect("");
                    exit;
                }

                $user = Users::where('user_id', $me['id'])->first();

                if (!$user) {
                    $this->cookies->delete("access_token");
                    $this->redirect("");
                    return;
                }

                $this->set("user", $user);
                $this->user = $user;
                $roles = json_decode($user->roles, true);
            } catch (Exception $e) {
                $this->cookies->delete("access_token");
                $this->redirect("");
                exit;
            }
        }

        $controller = $this->router->getController();
        $action     = $this->getActionName();
        $canAccess  = Security::canAccess($controller, $action, $roles);
        
        $themes     = ['dark', 'light'];
        $theme      = $this->cookies->get("theme");

        if ($theme && in_array($theme, $themes)) {
            $this->set("theme", $theme);
        } else {
            $this->set("theme", "light");
        }

        if (!$canAccess) {
            $this->setView("errors/show401");
            return false;
        }

        $http_host = $_SERVER['HTTP_HOST'];
        $sub_dom   = explode('.', $http_host); 
        $is_mobile = array_shift($sub_dom) == "mobile";

        $pm   = new PageMeta($controller, $action);
        $meta = $pm->getMeta();

        $this->set("page_title", $meta['title']);
        $this->set("meta_info", $meta['meta']);
        $this->set("is_mobile", $is_mobile);

        $this->set("controller", $controller);
        $this->set("action", $action);
        $this->set("route", $this->router->getCanonical());
        $this->set("request_ip", $this->request->getAddress());
        return true;
    }

    /**
     * Displays the necessary template using Twig
     */
	public function show() {
	    if ($this->disableView) {
	        return;
        }

	    $loader = new Template('app/views');
        $loader->setCacheEnabled(false);

        if (!file_exists($loader->path.'/'.$this->view.".twig")) {
            $this->view = "errors/missing";
        }

	    try {
            $template = $loader->load($this->view);
            echo $template->render($this->viewVars);
        } catch (Exception $e) {
            
        }
    }
    
    /**
     * Gets the name of the action
     * @return mixed
     */
	public function getActionName() {
		return $this->actionName;
	}

    /**
     * Sets the action to be used.
     * @param $name
     */
	public function setActionName($name) {
		$this->actionName = $name;
	}

    /**
     * Sets a specific variable for the view with a value
     * @param $variableName
     * @param $value
     */
	public function set($variableName, $value) {
		$this->viewVars[$variableName] = $value;
	}

    /**
     * Sets variables to be used in the view
     * @param $params
     */
	public function setVars($params) {
		$this->viewVars = $params;
	}

    /**
     * Sets which view to use.
     * @param $view
     */
	public function setView($view) {
		$this->view = $view;
    }
    
    public function getView() {
        return $this->view;
    }

    /**
     * @param $router PageRouter
     */
	public function setRouter(PageRouter $router) {
	    $this->router = $router;
    }

    /**
     * Filters a string.
     * @param $str
     * @return mixed
     */
    public function filter($str) {
        return filter_var($str, FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    /**
     * Filters an integer.
     * @param $int
     * @return mixed
     */
    public function filterInt($int) {
        return filter_var($int, FILTER_SANITIZE_NUMBER_INT,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    public static function debug($array) {
        echo "<pre>".htmlspecialchars(json_encode($array, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))."</pre>";
    }

    public static function printStr($str) {
        echo "<pre>".$str."</pre>";
    }

    public function disableView($is_json = false) {
        $this->disableView = true;
        $this->json_output = $is_json;
    }

    public function isJson() {
        return $this->json_output;
    }

    public function isViewDisabled() {
        return $this->disableView;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function getRequest() {
        return $this->request;
    }

    public function getRouter() {
        return $this->router;
    }

    public function getCsrf() {
        return $this->csrf;
    }

    public function redirect($location, $internal = true) {
        $this->request->redirect($location, $internal);
    }

    public function delayedRedirect($url, $time, $internal = false) {
        $this->request->delayedRedirect($url, $time, $internal);
    }

    public function getViewContents($view, $vars = []) {
        $loader = new Template('app/views');
        $loader->setCacheEnabled(false);

        try {
            $template = $loader->load($view);
            return $template->render($vars);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getPurifier() {
        
        $allowed_html = [
            'div[class]',
            'span[style]',
            'a[href|class|target]',
            'img[src|class|data-src]',
            'h1','h2','h3',
            'p[class]',
            'strong','em',
            'ul','u','ol','li',
            'table[class]','tr','td','th','thead','tbody'
        ];

        if (!$this->purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set("Core.Encoding", 'utf-8');
            $config->set('AutoFormat.RemoveEmpty', true);
            $config->set("HTML.Allowed", implode(',', $allowed_html));
            $config->set('HTML.AllowedAttributes', 'src, height, width, alt, href, class, style, data-src');

            $def = $config->getHTMLDefinition(true);
            $def->addAttribute('img', 'data-src', 'Text');

            $this->purifier = new HTMLPurifier($config);
        }

        return $this->purifier;
    }

    public function purify($text) {
        $text  = $this->getPurifier()->purify($text);
        $text  = preg_replace( "/\r|\n/", "", $text);
        $text  = preg_replace('/[^\00-\255]+/u', '', $text);
        return $text;
    }
}
