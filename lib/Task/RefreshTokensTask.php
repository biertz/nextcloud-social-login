<?php

namespace OCA\SocialLogin\Task;

use OCA\SocialLogin\Db\TokensMapper;
use OCA\SocialLogin\Service\TokenService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
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
     * @inheritDoc
     * @throws \Hybridauth\Exception\Exception
     */
    protected function run($argument)
    {
            $this->logger->info('Refresh cron is running.');
            $this->tokenService->refreshAllTokens();
            $this->logger->info('Refresh cron ran.');
    }
}
