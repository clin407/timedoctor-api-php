<?php

namespace Smartsites\timeDoctor;

class TdAuth
{

    /** @var string */
    public $secretKey;

    /** @var string */
    public $clientId;

    /** @var string */
    public $redirectUri;

    public function __construct($secretKey, $clientId, $redirectUri)
    {
        $this->secretKey = $secretKey;
        $this->clientId = $clientId;
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return string
     */
    public function getAuthCodeUrl()
    {
        return "https://webapi.timedoctor.com/oauth/v2/auth?client_id="
            . $this->clientId
            . "&response_type=code&redirect_uri="
            . $this->redirectUri;
    }

    public function getAccessTokenUrlWithAuthCode($authCode)
    {
        return "https://webapi.timedoctor.com/oauth/v2/token?client_id="
            . $this->clientId
            . "&client_secret="
            . $this->secretKey
            . "&grant_type=authorization_code&redirect_uri="
            . $this->redirectUri
            . "&code="
            . $authCode;
    }

    public function getAccessTokenUrlWithRefreshToken($refreshToken)
    {
        return "https://webapi.timedoctor.com/oauth/v2/token?client_id="
            . $this->clientId
            . "&client_secret="
            . $this->secretKey
            . "&grant_type=refresh_token&refresh_token="
            . $refreshToken;
    }

}