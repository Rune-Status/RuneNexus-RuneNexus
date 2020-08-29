<?php
use Fox\Request;

class AdsController extends Controller {

    public function index() {
        $premium = Premium::get();
        $sponsor = SponsorPackages::where('visible', 1)->get();

        if ($this->user) {
            $servers = Servers::getServersByOwner($this->user->user_id);
            $this->set("servers", $servers);
        }

        $sponsors = Sponsors::where("expires", ">", time())->count();

        if ($sponsors == 3) {
            $nextSlot = Sponsors::select("expires")
                ->where("expires", ">", time())
                ->orderBy("expires", "ASC")
                ->first();

            $this->set("nextslot", $nextSlot);
        }

        $this->set("premium_packages", $premium);
        $this->set("sponsor_packages", $sponsor);
        $this->set("sponsors", $sponsors);
        return true;
    }

    public function button() {
        $type      = $this->request->getPost("type", "string");
        $packageId = $this->request->getPost("package", "int");
        $serverId  = $this->request->getPost("server", "int");

        if ($type == "sponsor") {
            $sponsors = Sponsors::where("expires", ">", time())->count();

            if ($sponsors == 3) {
                return [
                    'success' => false,
                    'message' => "There is currently no available slots. Please check back later."
                ];
            }
        }

        if ($type == "premium") {
            $package  = Premium::where('id', $packageId)->first();
        } else if ($type == "sponsor") {
            $package  = SponsorPackages::where('id', $packageId)->first();
        } else {
            return [
                'success' => false,
                'message' => "Invalid Package Type."
            ];
        }
        
        if (!$package) {
            return [
                'success' => false,
                'message' => "Package not found."
            ];
        }

        $server = Servers::where("owner", $this->user->user_id)
            ->where("id", $serverId)
            ->first();

        if (!$server) {
            return [
                'success' => false,
                'message' => "Server not found."
            ];
        }

        $this->set("packageType", $type == "premium" ? "Premium Package" : "Sponsor Package");

        return [
            "success" => true,
            "message" => $this->getViewContents("ads/button", [
                "packageType" => $type == "premium" ? "Premium" : "Sponsor", 
                "package" => $package,
                "server"  => $server
            ])
        ];
    }

    public function verify() {
        $order_info = $this->request->getPost("orderDetails");
        $server_id  = $this->request->getPost("server_id", "int");

        if (empty($order_info)) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "No data was received."
                ])
            ];
        }

        $buyer     = $order_info['payer'];
        $firstName = $buyer['name']['given_name'];
        $lastName  = $buyer['name']['surname'];

        $item    = $order_info['purchase_units'][0]['items'][0];
        $name    = $this->filter($item['name'], 'string');
        $sku     = strtolower($this->filter($item['sku'], 'string'));
        $amount  = $this->filter($item['quantity'], 'int');
        $value   = $this->filter($item['unit_amount']['value'], 'float');

        $status  = $order_info['status'];

        if ($status != "APPROVED") {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Payment was not approved."
                ])
            ];
        }

        $sku_split    = explode("-", $sku);
        $package_type = strtolower($sku_split[0]);
        $package_id   = $sku_split[1];

        if ($package_type == "premium") {
            $package  = Premium::where('id', $package_id)->first();
        } else if ($package_type == "sponsor") {
            $package  = SponsorPackages::where('id', $package_id)->first();
        } else {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Invalid Package Type."
                ])
            ];
        }
        
        if (!$package) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Package could not be loaded."
                ])
            ];
        }

        if ($package->price != $value) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Invalid purchase price!"
                ])
            ];
        }

        $server = Servers::where("owner", $this->user->user_id)
            ->where("id", $server_id)
            ->first();

        if (!$server) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Could not find your server!"
                ])
            ];
        }
        
        $token = Functions::generateString(15);
        $this->session->set("pp_key", $token);

        return [
            'success' => true,
            'message' => $this->request->getPost(),
            'token'   => $this->session->get("pp_key"),
            'pkg_type'    => $package_type
        ];
    }

    public function process() {
        $pp_key   = $this->request->getPost("pp_key", "string");
        $sess_key = $this->session->get("pp_key");
        $type     = $this->request->getPost("pkg_type", "string");

        if (!$pp_key || !$sess_key || $pp_key != $sess_key) {
            return [
                'success' => false,
                'message' => "Invalid Request."
            ];
        }

        $this->session->delete("pp_key");
        
        $order_info = $this->request->getPost("orderDetails");
        $server_id  = $this->request->getPost("server_id", "int");

        if (empty($order_info)) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "No data was received"
                ])
            ];
        }

        $buyer     = $order_info['payer'];
        $firstName = $buyer['name']['given_name'];
        $lastName  = $buyer['name']['surname'];
        $email     = $buyer['email_address'];

        $item    = $order_info['purchase_units'][0]['items'][0];
        $name    = $this->filter($item['name'], 'string');
        $sku     = $this->filter($item['sku'], 'int');
        $amount  = $this->filter($item['quantity'], 'int');

        $capture = $order_info['purchase_units'][0]['payments']['captures'][0];
        $paid    = $this->filter($capture['amount']['value'], 'float');
        $cap_id  = $order_info['id'];
        $transId = $capture['id'];
        $status  = $order_info['status'];

        if ($status != "COMPLETED") {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Payment was not complete."
                ])
            ];
        }

        $sku_split    = explode("-", $sku);
        $package_type = strtolower($sku_split[0]);
        $package_id   = $sku_split[1];

        if ($package_type == "premium") {
            $package  = Premium::where('id', $package_id)->first();
        } else if ($package_type == "sponsor") {
            $package  = SponsorPackages::where('id', $package_id)->first();
        } else {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Invalid Package Type"
                ])
            ];
        }

        $server = Servers::where("owner", $this->user->user_id)
            ->where("id", $server_id)
            ->first();

        if (!$server) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Could not find your server!"
                ])
            ];
        }

        if ($package->price != $paid) {
            return [
                'success' => false,
                'message' => $this->getViewContents("ads/error", [
                    "message" => "Invalid purchase price"
                ])
            ];
        }

        $payment = new Payments;

        $payment->fill([
            'user_id'    => $this->user->user_id,
            'username'   => $this->user->username,
            'ip_address' => $this->request->getAddress(),
            'sku'        => $package->id,
            'item_name'  => $package->title.' '.$package_type,
            'email'      => $email,
            'status'     => $status,
            'paid'       => $paid,
            'quantity'   => $amount,
            'currency'   => 'USD',
            'transaction_id' => $transId,
            'date_paid'  => time(),
        ])->save();

        if ($package_type == "premium") {
            if ($package->level > $server->premium_level) {
                $server->premium_level = $package->level;
            }
    
            if ($server->premium_expires > time()) {
                $server->premium_expires = $server->premium_expires + $package->duration;
            } else {
                $server->premium_expires = time() + $package->duration;
            }
    
            if (!$server->save()) {
                return [
                    'success' => false,
                    'message' => $this->getViewContents("ads/error", [
                        "message" => "Could not update server."
                    ])
                ];
            }
        } else if ($package_type == "sponsor") {
            $sponsor = Sponsors::where("server_id", $server->id)->first();

            if ($sponsor) {
                $sponsor->expires = $sponsor->expires + $package->duration;
            } else {
                $sponsor = new Sponsors;

                $sponsor->fill([
                    'server_id' => $server_id,
                    'started' => time(),
                    'expires' => time() + $package->duration
                ]);
            }

            if (!$sponsor->save()) {
                return [
                    'success' => false,
                    'message' => $this->getViewContents("ads/error", [
                        "message" => "Could not save sponsor."
                    ])
                ];
            }
        }

        return [
            'success' => true,
            'message' => $this->getViewContents("ads/success", [
                "package" => $package,
                "type"    => $package_type,
                "server"  => $server
            ])
        ];
    }

    public function beforeExecute() {
        if ($this->getActionName() == "button" || $this->getActionName() == "process" || $this->getActionName() == "verify") {
            $this->disableView(true);
        }
        return parent::beforeExecute();
    }

}