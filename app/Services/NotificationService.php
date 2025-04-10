<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as MessagingNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

    class NotificationService {
        private $topics = [
            'topic_football_stadium' => 'topic_football_stadium',
            'topic_football_news' => 'topic_football_news',
            'topic_football_league' => 'topic_football_league',
            'topic_football_club' => 'topic_football_club',
            'topic_football_player' => 'topic_football_player',
            'topic_football_match' => 'topic_football_match',
            'topic_football_event' => 'topic_football_event',
            'topic_welcome' => 'topic_welcome',
        ];

        public function sendToAllCategories($title, $body, $data = []) 
        {
            foreach ($this->topics as $category => $topic) {
                $this->sendToTopic($topic, $title, $body, $data);
            }
        }

        public function sendToCategory($category, $title, $body, $data = [])
        {
            $topic = $this->topics[$category] ?? null;
            if ($topic) {
                $this->sendToTopic($topic, $title, $body, $data);
            }
        }

        private function sendToTopic($topic, $title, $body, $data=[])
        {
            $messaging = Firebase::messaging();
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(MessagingNotification::create($title, $body))
                ->withData($data);

            $messaging->send($message);
        }
    }
?>