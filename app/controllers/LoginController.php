<?php
use GuzzleHttp\Client;

class LoginController extends Controller {

    public function index() {
        $code = $this->request->getQuery("code", "string");

        if (!$code) {
            $this->request->redirect("");
            exit;
        }

        $discord  = new Discord;
        $response = $discord->getAccessToken($code);

        if (!$response || isset($response->error)) {
            $this->request->redirect("");
            return false;
        }

        $access_token  = $response->access_token;
        $token_expires = $response->expires_in;

        $discord->setToken($access_token);
        $discord->setEndpoint("/users/@me"); 

        $userData = $discord->get();

        if (!$userData || isset($userData->code)) {
            return [
                'success' => false,
                'message' => $userData ? $userData->message : 'No user data.'
            ];
        }

        $user = Users::firstOrCreate(
            ['user_id' => $userData['id']],
            [
                'discriminator' => $userData['discriminator'], 
                'username'      => $userData['username'],
                'email'         => $userData['email'],
                'avatar'        => $userData['avatar'],
                'roles'         => json_encode(['Member']),
                'join_date'     => time()
            ]
        );
    
        if (!$user->wasRecentlyCreated) {
            $user->username      = $userData['username'];
            $user->discriminator = $userData['discriminator'];
            $user->email         = $userData['email'];
            $user->avatar        = $userData['avatar'];
        }
    
        if ($userData['avatar'] != $user->avatar) {
            $user->avatar = $userData['avatar'];
        }
        
        $user->update();

        $tokens = [
            Functions::generateString(6),
            Functions::generateString(10),
            Functions::generateString(4),
        ];
        
        $sess_token = implode("-", $tokens);

        (new Sessions)->fill([
            'token'         => $sess_token,
            'user_id'       => $user['user_id'],
            'ip_address'    => $this->request->getAddress(),
            'started'       => time(),
            'expires'       => time() + $token_expires,
            'discord_token' => $access_token
        ])->save();
        
        $this->cookies->set("session_token", $sess_token, $token_expires);
        $this->request->redirect("");
        exit;
    }

    public function getServerRoles($discord, $user) {
        try {
            $discord->setEndpoint('/guilds/'.discord['guild_id'].'/members/'.$user['user_id']); 
            $discord->setIsBot(true);
            $userInfo = $discord->get();
            
            // if user is not in guild, just return default
            if (!$userInfo || isset($userInfo['code'])) {
                return ["Member"];
            }

            $discord->setEndpoint('/guilds/'.discord['guild_id']); 
            $discord->setIsBot(true);
            $server = $discord->get();

            $server_roles = $server['roles'];
            $roles = ['Member'];

            foreach ($server_roles as $sr) {
                if (in_array($sr['id'], $userInfo['roles'])) {
                    $roles[] = $sr['name'];
                }
            }

            return $roles;
        } catch (Exception $e) {
            return ["Member"];
        }
    }

    public function discord() {
        $params = array(
            'client_id'     => discord['client_id'],
            'redirect_uri'  => discord['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'identify guilds email'
        );

        return [
            'success' => true,
            'message' => 'https://discordapp.com/api/oauth2/authorize?'.http_build_query($params)
        ];
    }

}