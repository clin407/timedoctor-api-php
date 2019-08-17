<?php

namespace Smartsites\timeDoctor;

use Curl\Curl;
use ErrorException;

class HostingTdTokensService implements TdTokensService
{

    /** @var string */
    public $accessTokenFilePath;

    /** @var string */
    public $refreshTokenFilePath;

    /** @var TdAuth */
    protected $auth;

    public function __construct(
        TdAuth $auth,
        $accessTokenFilePath,
        $refreshTokenFilePath
    )
    {
        $this->auth = $auth;
        $this->accessTokenFilePath = $accessTokenFilePath;
        $this->refreshTokenFilePath = $refreshTokenFilePath;
    }

    public function getAccessToken()
    {
        $filepath = $this->accessTokenFilePath;
        if (!file_exists($filepath)) {
            throw new ErrorException("Token file doesn't exist");
        }
        return trim(
            file_get_contents(
                $filepath
            )
        );
    }

    /**
     * @param string $accessToken
     * @param string $refreshToken
     * @throws ErrorException
     */
    public function storeTokens($accessToken, $refreshToken)
    {
        if (YII_ENV !== 'prod') {
            throw new ErrorException(
                "This path should only be accessed on the production server"
            );
        }
        file_put_contents(
            $this->accessTokenFilePath,
            $accessToken . "\n"
        );
        file_put_contents(
            $this->refreshTokenFilePath,
            $refreshToken . "\n"
        );
    }

    /**
     * @return \stdClass Tokens data
     */
    public function refreshTokens()
    {
        return (new Curl())
            ->get(
                $this->auth->getAccessTokenUrlWithRefreshToken(
                    $this->getRefreshToken()
                )
            );
    }

    /**
     * @return string
     * @throws ErrorException
     */
    public function getRefreshToken()
    {
        $filepath = $this->refreshTokenFilePath;
        if (!file_exists($filepath)) {
            throw new ErrorException("Token file doesn't exist");
        }
        return trim(
            file_get_contents(
                $filepath
            )
        );
    }

}