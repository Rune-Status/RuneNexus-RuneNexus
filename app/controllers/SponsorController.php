<?php
class SponsorController extends Controller {

    public function index() {
        $sponsors = Sponsors::select([
            'sponsors.id',
            'sponsors.expires',
            'servers.title',
            'servers.website',
            'servers.discord_link',
            'servers.banner_url',
            'users.username',
            'users.discriminator'
        ])    
        ->where('expires', '>', time())
        ->where('servers.banner_url', '!=', null)
        ->where('servers.website', '!=', null)
        ->leftJoin("servers", "servers.id", "=", "sponsors.server_id")
        ->leftJoin("users", "servers.owner", "=", "users.user_id")
        ->get();

        if ($this->request->hasQuery("revoke")) {
            $id      = $this->request->getQuery("revoke", "int");
            $sponsor = Sponsors::where("id", $id)->first();

            if ($sponsor) {
                $sponsor->delete();
            }

            $this->redirect("admin/sponsor");
            exit;
        }

        $this->set("sponsors", $sponsors);
        $this->set("packages", SponsorPackages::get());
        return true;
    }

    public function add() {
        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $data = [
                'title'    => $this->request->getPost("title", "string"),
                'price'    => $this->request->getPost("price", "float"),
                'duration' => $this->request->getPost("duration", "int"),
                'visible'  => $this->request->getPost("visible", "string") == "on" ? 1 : 0,
                'icon'     => $this->request->getPost("icon", "string"),
            ];

            $validation = SponsorPackages::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $package = (new SponsorPackages)->fill($data);

                if ($package->save()) {
                    $this->redirect("admin/sponsor");
                    exit;
                }
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }
    
    public function edit($id) {
        $package = SponsorPackages::where("id", $id)->first();

        if (!$package) {
            $this->setView("errors/show404");
            return false;
        }

        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $data = [
                'title'    => $this->request->getPost("title", "string"),
                'price'    => $this->request->getPost("price", "float"),
                'duration' => $this->request->getPost("duration", "int"),
                'visible'  => $this->request->getPost("visible", "string") == "on" ? 1 : 0,
                'icon'     => $this->request->getPost("icon", "string"),
            ];

            $validation = SponsorPackages::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $package->fill($data);

                if ($package->save()) {
                    $this->redirect("admin/sponsor");
                    exit;
                }
            }
        }

        $this->set("sponsor", $package);
        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function delete($id) {
        $package = SponsorPackages::where("id", $id)->first();

        if (!$package) {
            $this->setView("errors/show404");
            return false;
        }

        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            if ($package->delete()) {
                $this->redirect("admin/sponsor");
                exit;
            }
        }

        $this->set("sponsor", $package);
        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function beforeExecute() {
        return parent::beforeExecute();
    }

}