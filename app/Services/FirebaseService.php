<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebaseService
{

private function getAccessToken()
{
    $credentials = new ServiceAccountCredentials(

        "https://www.googleapis.com/auth/firebase.messaging",

        config('firebase.credentials')

    );

    $token = $credentials->fetchAuthToken();

    return $token['access_token'];

}

public function sendNotification(

    string $deviceToken,

    string $title,

    string $body,

    array $data = []

)
{

    $url =

        "https://fcm.googleapis.com/v1/projects/"

        . config('firebase.project_id')

        . "/messages:send";


    $response = Http::withToken(

        $this->getAccessToken()

    )->post($url,[

        "message"=>[

            "token"=>$deviceToken,

            "notification"=>[

                "title"=>$title,

                "body"=>$body

            ],

            "data"=>$data

        ]

    ]);


    return $response->json();

}

}