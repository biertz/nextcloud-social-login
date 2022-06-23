<?php

namespace OCA\SocialLogin\Task;

use OC\User\LoginException;
use OCA\SocialLogin\Db\TokensMapper;
use OCA\SocialLogin\Service\Exceptions\TokensException;
use OCA\SocialLogin\Service\TokenService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class RefreshTokensTask extends TimedJob
{
    private $tokenService;
    private $tokensMapper;
    private $logger;

    public static int $REFRESH_TOKENS_JOB_INTERVAL = 60;

    public function __construct(ITimeFactory $time, TokenService $tokenService, TokensMapper $tokensMapper, LoggerInterface $logger)
    {
        parent::__construct($time);
        $this->tokenService = $tokenService;
        $this->tokensMapper = $tokensMapper;
        $this->logger = $logger;

        parent::setInterval(self::$REFRESH_TOKENS_JOB_INTERVAL);
    }

    /**
     * Refreshes all tokens periodically, but skips those which have failed in the past.
     *
     * @inheritDoc
     * @throws TokensException
     * @throws LoginException
     */
    protected function run($argument)
    {
            $this->logger->info('Refresh cron is running.');
            $this->tokenService->refreshAllTokens(true);
            $this->logger->info('Refresh cron ran.');
    }
}
