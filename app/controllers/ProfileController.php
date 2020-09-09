<?php

class ProfileController extends Controller {

    public function index() {
        $servers = Servers::where('owner', $this->user->user_id)->get();
        $roles = implode(", ", json_decode($this->user->roles, true));
        
        $idArr = array_column($servers->toArray(), "id");
        $ids   = implode(",", $idArr);

        $votes = Votes::
            select("*")
            ->whereRaw("server_id IN (".$ids.")")
            ->orderBy("votes.voted_on", "DESC")
            ->leftJoin("servers", "servers.id", "=", "votes.server_id")
            ->get();

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

        foreach($votes as $vote) {
            $vote_time = $vote->voted_on;
            $timeDiff  = time() - $vote_time;

            if ($timeDiff <= 3600) {
                $votesArr[$vote->server_id]['1hour']++;
            } 
            if ($timeDiff <= 86400) {
                $votesArr[$vote->server_id]['1day']++;
            }  
            if ($timeDiff <= 604800) {
                $votesArr[$vote->server_id]['7days']++;
            }  
            if ($timeDiff <= 2592000) {
                $votesArr[$vote->server_id]['30days']++;
            }  
            if ($timeDiff <= 10368000) {
                $votesArr[$vote->server_id]['60days']++;
            } 

            $votesArr[$vote->server_id]['lifetime']++;
        }

        $this->set("roles", $roles);
        $this->set("servers", $servers);
        $this->set("voteData", $votesArr);
        return true;
    }

    public function stats() {
        
        return true;
    }

    public $access =  [
        'login_required' => true,
        'roles'  => ['member', 'moderator', 'admin']
    ];

    public function beforeExecute() {
        return parent::beforeExecute();
    }

}