<?php

namespace Smartsites\timeDoctor;

use Curl\Curl;
use ErrorException;

class ClientTdTokensService implements TdTokensService
{

    private $hostTokensUri;

    private $hostHttpAuthUsername;

    private $hostHttpAuthPassword;

    public function __construct(
        $hostTokensUri,
        $hostHttpAuthUsername,
        $hostHttpAuthPassword
    )
    {
        $this->hostTokensUri = $hostTokensUri;
        $this->hostHttpAuthUsername = $hostHttpAuthUsername;
        $this->hostHttpAuthPassword = $hostHttpAuthPassword;
    }

    /**
     * Gets access token from a [[HostingTimeDoctorTokensService]]
     * @return string
     * @throws ErrorException If request to hosting server doesn't succeed.
     * @see HostingTdTokensService
     */
    public function getAccessToken()
    {
        $curl = new Curl();
        $curl->setBasicAuthentication(
            $this->hostHttpAuthUsername,
            $this->hostHttpAuthPassword
        );
        $curl->get($this->hostTokensUri);
        if ($curl->error) {
            throw new ErrorException($curl->errorCode);
        }
        return $curl->response;
    }

    public function refreshTokens()
    {
        throw new ErrorException(
            self::class . " can't refresh tokens"
        );
    }

    public function storeTokens($accessToken, $refreshToken)
    {
        throw new ErrorException(
            self::class . " can't store tokens"
        );
    }

    public function getRefreshToken()
    {
        throw new ErrorException(
            self::class . " can't return refresh token"
        );
    }

}