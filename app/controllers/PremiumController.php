<?php
use Fox\Request;

class PremiumController extends Controller {

    public function index() {
        $this->set("packages", Premium::get());
        return true;
    }

    public function add() {
        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $data = [
                'title'    => $this->request->getPost("title", "string"),
                'price'    => $this->request->getPost("price", "float"),
                'duration' => $this->request->getPost("duration", "int"),
                'level'    => $this->request->getPost("level", "int"),
            ];

            $validation = Premium::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $package = (new Premium)->fill($data);

                if ($package->save()) {
                    $this->redirect("admin/premium");
                    exit;
                }
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function edit($id) {
        $package = Premium::where("id", $id)->first();

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
                'level'    => $this->request->getPost("level", "int"),
            ];

            $validation = Premium::validate($data);
            
            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $package->fill($data);

                if ($package->save()) {
                    $this->redirect("admin/premium");
                    exit;
                }
            }
        }

        $this->set("package", $package);
        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function delete($id) {
        $package = Premium::where("id", $id)->first();

        if (!$package) {
            $this->setView("errors/show404");
            return false;
        }

        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            if ($package->delete()) {
                $this->redirect("admin/premium");
                exit;
            }
        }

        $this->set("package", $package);
        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

}