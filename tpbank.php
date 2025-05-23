<?php
class TpBank
{
    private $username;
    private $password;
    private $device_id;
    private $access_token;
    private $accountNo;
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->device_id = "F652F851-920D-4DA1-98FA-22267D7D3EC5";
    }
    public function generateqrcodepre($amount, $text)
    {
        $params = json_encode(
            array(
                "accountNumber" => $this->accountNo,
                "amount" => $amount,
                "content" => $text,
                "reqLanguage" => "VN",
                "sourceApp" => "HYDRO",
            )
        );
        require "phpqrcode/qrlib.php";
        $dataArray = $this->curlPost($this->urlparam('generateqrcodepre'), $params, $this->getAccess_token());
        if (isset($dataArray['qrContent'])) {
            $qrContent = $dataArray['qrContent'];
            QRcode::png($qrContent, "" . $dataArray['qrTransID'] . ".png");
        } else {
            return "Error: Unable to retrieve qrContent from API response.";
        }
    }

    private function urlparam($a)
    {
        $data = array(
            "login" => "auth/login/v3",
            "account-transactions" => "smart-search-presentation-service/v2/account-transactions/find",
            "bank-accounts" => "common-presentation-service/v1/bank-accounts?function=inquiry",
            "generateqrcodepre" => "qr-payment-service/v1/qrcode/generateqrcodepre",
        );
        if (!$data[$a]) {
            return false;
        }
        return $data[$a];
    }
    public function account_transactions()
    {
        $currentDate = new DateTime();
        $data = array(
            "accountNo" => $this->accountNo,
            "currency" => "VND",
            "fromDate" => $currentDate->format('Ymd'),
            "pageNumber" => 1,
            "pageSize" => 400,
            "toDate" => $currentDate->format('Ymd')
        );
        $jsonString = $this->curlPost($this->urlparam("account-transactions"), json_encode($data), $this->access_token);
        return $jsonString;
    }
    public function bank_accounts()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ebank.tpb.vn/gateway/api/common-presentation-service/v1/bank-accounts?function=inquiry');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Accept: */*',
            'APP_VERSION: 10.12.08',
            'SOURCE_APP: HYDRO',
            'Accept-Language: vi-US;q=1.0, en-US;q=0.9',
            'Content-Type: application/json',
            'Connection: keep-alive',
            'DEVICE_ID: ' . $this->device_id,
        ]);
        $response = curl_exec($ch);

        curl_close($ch);
        return json_decode($response, TRUE);
    }
    public function refresh_access_token()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ebank.tpb.vn/gateway/api/auth/refresh?transport=header');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Accept: */*',
            'APP_VERSION: 10.12.08',
            'SOURCE_APP: HYDRO',
            'Accept-Language: vi-US;q=1.0, en-US;q=0.9',

            'Content-Type: application/json',
            'Connection: keep-alive',
            'DEVICE_ID: ' . $this->device_id,
        ]);
        $response = json_decode(curl_exec($ch), TRUE);

        curl_close($ch);
        $this->access_token = $response["access_token"];
        return $response["access_token"];
    }
    public function login()
    {
        $param = json_encode(
            array(
                "username" => $this->username,
                "password" => $this->password,
                "step_2FA" => "VERIFY",
                "deviceId" => $this->device_id
            )
        );
        $send = $this->curlPost($this->urlparam("login"), $param, "");
        $this->access_token = $send["access_token"];
        return $send['access_token'];
    }
    public function setAccess_token($access_token)
    {
        $this->access_token = $access_token;
    }
    public function getAccess_token()
    {
        return $this->access_token;
    }
    public function setAccountNo($account)
    {
        $this->accountNo = $account;
    }
    public function getAccountNo()
    {
        return $this->accountNo;
    }
    private function curlPost($url, $data, $auth)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://ebank.tpb.vn/gateway/api/' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $headers = [
            'PLATFORM_NAME: iOS',
            'Accept: */*',
            'APP_VERSION: 10.12.08',
            'SOURCE_APP: HYDRO',
            'Accept-Language: vi-US;q=1.0, en-US;q=0.9',
            'Content-Type: application/json',
            'Connection: keep-alive',
            'DEVICE_ID: ' . $this->device_id,
        ];
        if (isset($auth)) {
            $headers[] = 'Authorization: Bearer ' . $auth;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);
        echo $response;
        return json_decode($response, true);
    }

    private function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return strtoupper(
            substr($hex, 0, 8) . '-' .
            substr($hex, 8, 4) . '-' .
            substr($hex, 12, 4) . '-' .
            substr($hex, 16, 4) . '-' .
            substr($hex, 20, 12)
        );
    }
}