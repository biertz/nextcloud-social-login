<?php

namespace OCA\SocialLogin\Db;

use DateTime;
use Exception;
use OCA\SocialLogin\Service\TokenService;
use OCA\SocialLogin\Task\RefreshTokensTask;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * The Tokens objects holds a user's access and refresh tokens for a provider.
 */
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

    /**
     * Checks whether an access token has expired. Treats any token as expired that would expire before the next cron
     * execution.
     *
     * @return bool
     * @throws Exception
     */
    public function isExpired(): bool
    {
        $t = time() + RefreshTokensTask::$REFRESH_TOKENS_JOB_INTERVAL + 1;
        return $this->getExpiresAt() < new DateTime('@' . $t);
    }
}
