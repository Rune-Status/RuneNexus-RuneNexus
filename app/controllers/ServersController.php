<?php
use Fox\CSRF;
use Rakit\Validation\Validator;
use Fox\Request;

class ServersController extends Controller {

    public function index($page = 1) {
        if ($this->request->hasQuery("search")) {
            $search  = $this->request->getQuery("search", "string");
            $servers = Servers::searchServers($search, $page);

            $this->set("search", $search);
        } else {
            $servers = Servers::getAdminServers($page);
        }
        
        $this->set("servers", $servers);
        return true;
    }

    public function info($id) {
        $server = Servers::getServer($id);

        if (!$server) {
            $this->setView("errors/show404");
            return false;
        }

        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            if ($this->request->hasPost("premium_package")) {
                $pid = $this->request->getPost("premium_package");
                $pkg = Premium::where("id", $pid)->first();

                if (!$pkg) {
                    $this->redirect("admin/servers/info/".$server->id);
                    exit;
                }

                if ($pkg->level > $server->premium_level) {
                    $server->premium_level = $pkg->level;
                }
        
                if ($server->premium_expires > time()) {
                    $server->premium_expires = $server->premium_expires + $pkg->duration;
                } else {
                    $server->premium_expires = time() + $pkg->duration;
                }
        
                $server->save();
                $this->redirect("admin/servers/info/".$server->id);
                exit;
            }

            if ($this->request->hasPost("sponsor_package")) {
                $pid = $this->request->getPost("sponsor_package");
                $pkg = SponsorPackages::where('id', $pid)->first();
                
                if (!$pkg) {
                    
                    exit;
                }

                $sponsor = Sponsors::where("server_id", $server->id)->first();

                if ($sponsor) {
                    $sponsor->expires = $sponsor->expires + $pkg->duration;
                } else {
                    $sponsor = new Sponsors;
    
                    $sponsor->fill([
                        'server_id' => $server->id,
                        'started' => time(),
                        'expires' => time() + $pkg->duration
                    ]);
                }

                $sponsor->save();
                $this->redirect("admin/servers/info/".$server->id);
                exit;
            }
        }

        if ($this->request->hasQuery("revokePremium")) {
            $server->premium_level   = 0;
            $server->premium_expires = time() - 1;
            $server->update();
            $this->redirect("admin/servers/info/".$server->id);
            exit;
        }

        if ($this->request->hasQuery("revokeSponsor")) {
            $sponsor = Sponsors::where("server_id", $server->id)->first();

            if ($sponsor) {
                $sponsor->expires = time() - 1;
                $sponsor->update();
            }

            $this->redirect("admin/servers/info/".$server->id);
            exit;
        }


        $sponsor = Sponsors::where("server_id", $server->id)->first();

        if ($sponsor) {
            $this->set("sponsor", $sponsor);
        }

        $premium_packages = Premium::get();
        $sponsor_packages = SponsorPackages::get();

        $this->set("premium_packages", $premium_packages);
        $this->set("sponsor_packages", $sponsor_packages);
        $this->set("server", $server);
        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function edit($id) {
        $server = Servers::getServer($id);

        if (!$server) {
            $this->setView("errors/show404");
            return false;
        }

        if (!$this->user->isRole(["owner", "administrator"])) { 
            if ($server->owner != $this->user->user_id) {
                $this->setView("errors/show401");
                return false;
            }
        }
        
        if ($this->request->isPost()) {
            $data = [
                'revision'      => $this->request->getPost("revision", "string"),
                'title'         => $this->request->getPost("title", "string"),
                'server_port'   => $this->request->getPost("server_port", "int"),
                'server_ip'     => $this->request->getPost("server_ip", "string"),
                'website'       => $this->request->getPost("website", "url"),
                'callback_url'  => $this->request->getPost("callback_url", "url"),
                'discord_link'  => $this->request->getPost("discord_link", "url"),
                'banner_url'    => $this->request->getPost("banner_url", "string"),
                'meta_tags'     => explode(",", $this->request->getPost("meta_tags", 'string')),
                'meta_info'     => $this->request->getPost("meta_info", "string"),
                'description'   => $this->purify($this->request->getPost("info")),
            ];
            
            $validation = Servers::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $data['meta_tags'] = json_encode($data['meta_tags'], JSON_UNESCAPED_SLASHES);

                $server->fill($data);
                $saved = $server->save();

                if ($saved) {
                    $seo  = Functions::friendlyTitle($server->id.'-'.$server->title);
                    $link = "[{$data['title']}](https://rune-nexus.com/details/{$seo})";

                    (new DiscordMessage([
                        'channel_id' => '610038623743639559',
                        'title'      => 'Server Update',
                        'message'    => "{$this->user->username} has updated $link",
                    ]))->send();

                    $this->redirect("admin/servers");
                    exit;
                }
            }
        }

        $revisions = Revisions::where('visible', 1)->get();

        $this->set("revisions", $revisions);
        $this->set("server", $server);

        if ($server->meta_tags)
            $this->set("server_tags", implode(',',json_decode($server->meta_tags, true)));
        return true;
   }

   public function delete($id) {

   }

}