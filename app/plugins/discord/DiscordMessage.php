<?php
class DiscordMessage extends Discord {

    
    private $channel_id;
    private $title;
    private $message;
    private $is_rich = true;
    private $tts = false;

    public function __construct($dataArr = null) {
        if ($dataArr != null) {
            foreach ($dataArr as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function send() {
        $this->setEndpoint("/channels/{$this->channel_id}/messages"); 

        $data = ['tts' => $this->tts ];

        if ($this->is_rich) {
            $data['embed'] = [
                'title' => $this->title,
                'description' => $this->message
            ];
        } else {
            $data['content'] = $this->message;
        }
        
        try {
            return parent::sendMessage($data);
        } catch (Exception $e) {
            return null;
        }
    }

    public function setChannel($channel) {
        $this->channel_id = $channel;
        return $this;
    }

    public function setMessage($msg) {
        $this->message = $msg;
        return $this;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }



}