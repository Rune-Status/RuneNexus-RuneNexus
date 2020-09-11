<?php
use Illuminate\Pagination\Paginator;
use Fox\Request;

class ProfileController extends Controller {

    public function index() {
        $servers = Servers::where('owner', $this->user->user_id)->get();

        
        $roles = implode(", ", json_decode($this->user->roles, true));
        
        $idArr = array_column($servers->toArray(), "id");
        $ids   = implode(",", $idArr);

        if (count($servers) > 0) {
            $votes = Votes::
                select("*")
                ->whereRaw("server_id IN (".$ids.")")
                ->leftJoin("servers", "servers.id", "=", "votes.server_id")
                ->orderByRaw("votes.voted_on DESC")
                ->get();
        }

        $votesArr = [];

        foreach ($idArr as $id) {
            $votesArr[$id] = [
                '1hour'    => 0,
                '1day'     => 0,
                '7days'    => 0,
                '30days'   => 0,
                '60days'   => 0,
                'lifetime' => 0,
            ];
        }

        if (count($servers) > 0) {
            foreach($votes as $vote) {
                $vote_time = $vote->voted_on;
                $timeDiff  = time() - $vote_time;

                if ($timeDiff <= 3600)
                    $votesArr[$vote->server_id]['1hour']++;
                if ($timeDiff <= 86400)
                    $votesArr[$vote->server_id]['1day']++;
                if ($timeDiff <= 604800)
                    $votesArr[$vote->server_id]['7days']++;
                if ($timeDiff <= 2592000)
                    $votesArr[$vote->server_id]['30days']++;
                if ($timeDiff <= 10368000)
                    $votesArr[$vote->server_id]['60days']++;
                    
                $votesArr[$vote->server_id]['lifetime']++;
            }
        }

        $this->set("roles", $roles);
        $this->set("servers", $servers);
        $this->set("voteData", $votesArr);
        return true;
    }

    public function payments($page = 1) {
        Paginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        $payments = Payments::where('user_id', $this->user->user_id)->paginate(15);
        $sum = Payments::where('user_id', $this->user->user_id)->sum("paid");
        $roles = implode(", ", json_decode($this->user->roles, true));

        $this->set("payments", $payments);
        $this->set("spent", $sum);
        $this->set("roles", $roles);
        return true;
    }

    public function add() {
        $client = new GuzzleHttp\Client();

        if ($this->request->isPost()) {
            $data = [
                'owner'         => $this->user->user_id,
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
                'date_created'  => time()
            ];
            
            $validation = Servers::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $data['meta_tags'] = json_encode($data['meta_tags'], JSON_UNESCAPED_SLASHES);
                $create = Servers::create($data);

                if ($create) {
                    if ($create['server_ip'] && $create['server_port']) {
                        $api_url  = "http://api.rune-nexus.com/ping";

                        $endpoint = $api_url."?address=".$data['server_ip']."&port=".$data['server_port'];
                        $res      = json_decode($client->request('GET', $endpoint)->getBody(), true);
                        $success  = $res['success'];
        
                        $create->is_online = $success;
                        $create->ping = $success ? $res['ping'] : -1;
                        $create->save();
                    }

                    $seo  = Functions::friendlyTitle($create->id.'-'.$create->title);
                    $link = "[{$data['title']}](https://rune-nexus.com/details/{$seo})";

                    (new DiscordMessage)
                        ->setChannel("607320502268330016")
                        ->setTitle("New Server")
                        ->setMessage("{$this->user->username} has listed a new server: $link")
                        ->send();
                    
                    $this->redirect("details/".$seo);
                    exit;
                }
            }
        }

        $revisions = Revisions::where('visible', 1)->get();
        $this->set("revisions", $revisions);
    	return true;
    }

    public function delete($id) {
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
            $server->delete();

            (new DiscordMessage)
                ->setChannel("610038623743639559")
                ->setTitle("Server Deleted")
                ->setMessage("{$this->user->username} has deleted a server: {$server->title}")
                ->send();

            $this->request->redirect("profile");
            exit;
        }


        $this->set("server", $server);
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

                    (new DiscordMessage)
                        ->setChannel("610038623743639559")
                        ->setTitle("Server Updated")
                        ->setMessage("{$this->user->username} has updated their listing for $link")
                        ->send();

                    $this->redirect("profile");
                    exit;
                }
            }
        }

        $revisions = Revisions::where('visible', 1)->get();

        $this->set("revisions", $revisions);
        $this->set("server", $server);

        $this->set("seo_link", Functions::friendlyTitle($server->id.'-'.$server->title));
        if ($server->meta_tags)
            $this->set("server_tags", implode(',',json_decode($server->meta_tags, true)));
        return true;
    }

    public function upload() {
        $file = $_FILES['image'];
        $dims = getimagesize($file['tmp_name']);

        if ($dims === false) {
            return [
                'success' => false,
                'message' => 'File must be an image.'
            ];
        }

        $mimes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];

        $type   = mime_content_type($file['tmp_name']);
        $ext    = pathinfo($file['name'])['extension'];
        $size   = $file['size'];

        $width  = $dims[0];
        $height = $dims[1];
        
        $maxDims = [468, 60];
        $maxSize = (1024 * 1024 * 5);

        if (!in_array($type, array_values($mimes))) {
            return [
                'success' => false,
                'message' => 'Invalid file mime type.'
            ];
        }

        if (!in_array($ext, array_keys($mimes))) {
            return [
                'success' => false,
                'message' => 'Invalid file extension. Allowed: '.implode(', ', array_keys($mimes))
            ];
        }

        if ($size > $maxSize) {
            return [
                'success' => false,
                'message' => "Image can not exceed ".(($maxSize/1024)/1024)."MB."
            ];
        }

        if ($width != $maxDims[0] && $height != $maxDims[1]) {
            return [
                'success' => false,
                'message' => "Image must be $maxDims[0]px x $maxDims[1]px."
            ];
        }

        $newname = md5($file['name'] . microtime()).'.'.$ext;

        if (!move_uploaded_file($file['tmp_name'], 'public/img/banners/'.$newname)) {
            return [
                'success' => false,
                'message' => 'Failed uploading file...'
            ];
        }

        return [
            'success' => true,
            'message' => $newname,
        ];
    }

    public function beforeExecute() {
        if ($this->getActionName() == "upload") {
            $this->request = Request::getInstance();
            $this->disableView(true);
            return true;
        }

        return parent::beforeExecute();
    }

}