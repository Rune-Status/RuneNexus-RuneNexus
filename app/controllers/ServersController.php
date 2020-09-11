<?php
use Fox\CSRF;
use Rakit\Validation\Validator;
use Fox\Request;

class ServersController extends Controller {

    public function index($page = 1) {
        $servers = Servers::getAdminServers($page);


        $this->set("servers", $servers);
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