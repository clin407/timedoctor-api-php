<?php

namespace Smartsites\timeDoctor;

interface TdTokensService
{

    function getAccessToken();

    function getRefreshToken();

    function refreshTokens();

    function storeTokens($accessToken, $refreshToken);

}