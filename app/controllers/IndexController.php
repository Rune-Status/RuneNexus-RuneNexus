<?php
use Fox\CSRF;
use Fox\Paginator;
use Fox\Request;
use Fox\Cookies;

use Illuminate\Database\Capsule\Manager as DB;

class IndexController extends Controller {

    public function index($rev = null, $page = 1) {
        $revisions = Revisions::where('visible', 1)->get();

        if ($rev != null) {
            $revision = Revisions::where('revision', $rev)->first();

            if (!$revision) {
                $this->setView("errors/show404");
                return false;
            }

            $servers = Servers::getByRevision($revision, $page);

            $this->set("page_title", "{$revision->revision} Servers");
            $this->set("meta_info", "{$revision->revision} Runescape private servers.");
            $this->set("revision", $revision);
        } else {
            $servers = Servers::getAll($page);
        }

        $sponsors = Sponsors::select([
                'sponsors.id',
                'servers.title', 
                'servers.website', 
                'servers.discord_link', 
                'servers.banner_url'
            ])    
            ->where('expires', '>', time())
            ->where('servers.banner_url', '!=', null)
            ->where('servers.website', '!=', null)
            ->leftJoin("servers", "servers.id", "=", "sponsors.server_id")
            ->get();

        $this->set("servers", $servers);
        $this->set("revisions", $revisions);
        $this->set("sponsors", $sponsors);
        $this->set("server_count", $servers->total());
    	return true;
    }

    public function details($id) {
        $server   = Servers::getServer($id);

        if (!$server) {
            $this->setView("errors/show404");
            return false;
        }

        $seo = Functions::friendlyTitle($server->id.'-'.$server->title);

        $this->set("server", $server);
        $this->set("purifier", $this->getPurifier());
        $this->set("page_title", $server->title);

        $seo = Functions::friendlyTitle($server->id.'-'.$server->title);

        if ($server->meta_tags)
            $this->set("meta_tags", implode(',',json_decode($server->meta_tags, true)));

        $this->set("meta_info", $this->filter($server->meta_info));
        $this->set("seo_link", $seo);

        $body = str_replace("<img src", "<img data-src", $server->description);
        $body = str_replace("\"img-fluid\"", "\"lazy img-fluid\"", $body);
        $this->set("description", $body);
        return true;
    }

    public function out($serverId) {
        $server = Servers::getServer($serverId);

        if (!$server) {
            $this->setView("errors/show401");
            return false;
        }

        $website = $server->website;

        if (!$website) {
            $this->redirect("");
            exit;
        }

        $http_code = $this->getHttpCode($website);

        if ($http_code != 200 && $http_code != 301 && $http_code != 302 && $http_code != 303 && $http_code != 307) {
            $this->setView("errors/show401");
            return false;
        }

        $out = Outbound::where("server_id", $server->id)
            ->where("ip_address", $this->request->getAddress())
            ->first();

        if (!$out) {
            (new Outbound)->fill([
                'server_id'  => $server->id,
                'ip_address' => $this->request->getAddress(),
                'clicks'     => 1,
                'click_date' => time()
            ])->save();
        } else {
            $out->clicks += 1;
            $out->click_date = time();
            $out->update();
        }

        $this->redirect($website, false);
        exit;
    }

    public function getHttpCode($website) {
        $ch = curl_init($website);
        curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode;
    }

    public function logout() {
        $token = $this->cookies->get("access_token");

        try {
            $discord = new Discord($token);
            $discord->revokeAccess();
        } catch (Exception $e) {

        }

        $this->cookies->delete("access_token");
        $this->redirect("");
        exit;
    }

    public $access =  [
        'login_required' => false,
        'roles'  => []
    ];

    public function beforeExecute() {
        if ($this->getActionName() == "logout") {
            $this->request = Request::getInstance();
            $this->cookies = Cookies::getInstance();
            $this->disableView(false);
            return true;
        }

        return parent::beforeExecute();
    }

}