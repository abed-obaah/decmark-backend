<?php

namespace App\Classes;

use App\Notifications\AppNotifications;
use Illuminate\Support\Facades\Notification;

    class SenderHelper{
        
        public static function appNotification($user, $title, $message, $screen=null, $res_id=null){

            $data = [
                'title' => $title,
                'message' => $message,
                'fcmTokens' => json_decode($user->fcm_tokens) ?? [],
                'screen' => $screen ?? null,
                'res_id' => $res_id ?? null,
            ];
    
            Notification::send($user, new AppNotifications($data));
        }

        public static function userLog($user, $message, $screen=null, $res_id=null){

            $data = [
                'screen' => $screen ?? null,
                'res_id' => $res_id ?? null,
            ];
    
            $user->add_log($message, $data);
        }

    }
?>