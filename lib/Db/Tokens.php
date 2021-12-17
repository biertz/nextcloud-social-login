<?php

namespace OCA\SocialLogin\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class Tokens extends Entity
{
    /** @var string Nextcloud user id */
    protected $uid;
    /** @var string */
    protected $accessToken;
    /** @var string */
    protected $refreshToken;
    /** @var DateTime */
    protected $expiresAt;
    /** @var string */
    protected $providerType;
    /** @var string */
    protected $providerId;

    public function __construct() {
        $this->addType('uid', Types::STRING);
        $this->addType('access_token', Types::STRING);
        $this->addType('refresh_token', Types::STRING);
        $this->addType('expires_at', Types::DATETIME);
        $this->addType('provider_type', Types::STRING);
        $this->addType('provider_id', Types::STRING);
    }
}