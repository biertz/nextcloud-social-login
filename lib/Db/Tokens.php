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
    /** @var bool */
    protected $hasFailed=false;

    public function __construct() {
        $this->addType('uid', Types::STRING);
        $this->addType('accessToken', Types::STRING);
        $this->addType('refreshToken', Types::STRING);
        $this->addType('expiresAt', Types::DATETIME);
        $this->addType('providerType', Types::STRING);
        $this->addType('providerId', Types::STRING);
        $this->addType('hasFailed', Types::BOOLEAN);
    }
}