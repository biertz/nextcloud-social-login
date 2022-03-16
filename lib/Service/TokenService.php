<?php

namespace OCA\SocialLogin\Service;

use DateTime;
use OC\User\LoginException;
use OCA\SocialLogin\Db\Tokens;
use OCA\SocialLogin\Db\TokensMapper;
use OCA\SocialLogin\Service\Exceptions\NoTokensException;
use OCA\SocialLogin\Service\Exceptions\TokensException;
use OCA\SocialLogin\Task\RefreshTokensTask;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class TokenService
{
    private $tokensMapper;
    private $configService;
    private $logger;
    private $adapter;
    private $adapterService;


    public function __construct(TokensMapper $tokensMapper, LoggerInterface $logger, ConfigService $configService, AdapterService $adapterService)
    {
        $this->tokensMapper = $tokensMapper;
        $this->logger = $logger;
        $this->configService = $configService;
        $this->adapterService = $adapterService;
    }

    public function authenticate($adapter, $providerType, $providerId){
        $adapter->authenticate();

        $profile = $adapter->getUserProfile(); // TODO whole paragraph: refactor to service / trait
        $profileId = preg_replace('#.*/#', '', rtrim($profile->identifier, '/'));
        $uid = $providerId.'-'.$profileId;
        if (strlen($uid) > 64 || !preg_match('#^[a-z0-9_.@-]+$#i', $profileId)) {
            $uid = $providerId.'-'.md5($profileId);
        }

        $accessTokens = $adapter->getAccessToken();
        $this->saveTokens($accessTokens, $uid, $providerType, $providerId);
    }

    /**
     * @return Tokens A user's sociallogin tokens for a single provider.
     * @throws TokensException
     * @throws NoTokensException
     */
    public function get(string $uid, string $providerId): Tokens {
        try {
            return $this->tokensMapper->findByConnectedLoginsAndProviderId($uid, $providerId);
        } catch (DoesNotExistException $e) {
            throw new NoTokensException('Could not find tokens for uid '.$uid.'.');
        } catch (MultipleObjectsReturnedException $e) {
            throw new TokensException('There should be only one set of tokens per user, but we found multiple!');
        } catch (Exception $e) {
            throw new TokensException($e->getMessage());
        }
    }

    /**
     * Refreshes all pairs of tokens for all users.
     *
     * @throws TokensException
     * @throws LoginException
     */
    public function refreshAllTokens(): void
    {
        try {
            $allTokens = $this->tokensMapper->findAll();
        } catch (Exception $e) {
            throw new TokensException($e->getMessage());
        }
        if (count($allTokens) === 0) {
            $this->logger->info("No tokens in database.");
            return;
        }
        foreach ($allTokens as $tokens) {
            $this->refreshTokens($tokens);
        }
    }

    /**
     * Refresh a user's tokens for a single provider.
     *
     * @throws LoginException
     * @throws TokensException
     */
    public function refreshUserTokens(string $uid, string $providerId): void
    {
        try {
            $tokens = $this->get($uid, $providerId);
        } catch (NoTokensException $e) {
            return;
        }
        $this->refreshTokens($tokens);
    }

    /**
     * Refresh a set of tokens, if it is not to be deleted.
     *
     * @throws LoginException
     * @throws TokensException
     */
    private function refreshTokens(Tokens $tokens): void
    {
        if ($this->deleteOldTokens($tokens)) {
            return;
        }
        if ($this->hasAccessTokenExpired($tokens)) {
            $config = $this->configService->customConfig($tokens->getProviderType(), $tokens->getProviderId());

            try {
                $this->adapter = $this->adapterService->new(ConfigService::TYPE_CLASSES[$tokens->getProviderType()],
                    $config, null);
            } catch (\Exception $e) {
                throw new TokensException($e->getMessage());
            }
            $this->logger->info("Trying to refresh token for {uid}.", array('uid' => $tokens->getUid()));
            $parameters = array(
                'client_id' => $config['keys']['id'],
                'client_secret' => $config['keys']['secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens->getRefreshToken(),
                'scope' => $config['scope']
            );
            $response = $this->adapter->refreshAccessToken($parameters);#
            $responseArr = json_decode($response, true);

            $this->logger->info("Saving refreshed token for {uid}.", array('uid' => $tokens->getUid()));

            $this->saveTokens($responseArr, $tokens->getUid(), $tokens->getProviderType(), $tokens->getProviderId());
        } else {
            $this->logger->info("Token for {uid} has not yet expired.", array('uid' => $tokens->getUid()));
        }
    }


    /**
     * @throws TokensException
     */
    public function saveTokens(array $accessTokens, string $uid, string $providerType, string $providerId): void
    {
        if (!array_key_exists('expires_at', $accessTokens) && array_key_exists('expires_in', $accessTokens)) {
            $accessTokens['expires_at'] = time() + $accessTokens['expires_in'];
        }
        try {
            // $this->tokensMapper->insertOrUpdate($tokens) would fail, see https://github.com/nextcloud/server/issues/21705
            try {
                $tokens = $this->get($uid, $providerId);
                $tokens->setAccessToken($accessTokens['access_token']);
                $tokens->setRefreshToken($accessTokens['refresh_token']);
                $tokens->setExpiresAt(new DateTime('@' . $accessTokens['expires_at']));
                $tokens->setProviderType($providerType);
                $tokens->setProviderId($providerId);
                $this->tokensMapper->update($tokens);
            } catch (NoTokensException $e) {
                $tokens = new Tokens();
                $tokens->setUid($uid);
                $tokens->setAccessToken($accessTokens['access_token']);
                $tokens->setRefreshToken($accessTokens['refresh_token']);
                $tokens->setExpiresAt(new DateTime('@' . $accessTokens['expires_at']));
                $tokens->setProviderType($providerType);
                $tokens->setProviderId($providerId);
                $this->tokensMapper->insert($tokens);
            }
        } catch (Exception $e) {
            throw new TokensException($e->getMessage());
        }
    }

    /**
     * Delete keys from formerly active instances.
     *
     * @returns bool Whether the tokens had to be deleted.
     * @throws TokensException
     * @throws LoginException
     */
    private function deleteOldTokens(Tokens $tokens): bool
    {
        $config = $this->configService->customConfig($tokens->getProviderType(), $tokens->getProviderId());

        if (!array_key_exists('saveTokens', $config) || $config['saveTokens'] != true) {
            try {
                $this->tokensMapper->delete($tokens); // Delete keys from formerly active instances.
            } catch (Exception $e) {
                throw new TokensException($e->getMessage());
            }
            $this->logger->warning("Deleted old key by {uid}.", array('uid' => $tokens->getUid()));
            return true;
        }
        return false;
    }

    /**
     * Checks whether an access token has expired. Treats any token as expired that would expire before the next cron
     * execution.
     * @param Tokens $tokens
     * @return bool
     * @throws \Exception
     */
    protected function hasAccessTokenExpired(Tokens $tokens): bool
    {
        $t = time() + RefreshTokensTask::$REFRESH_TOKENS_JOB_INTERVAL + 1;
        return $tokens->getExpiresAt() < new DateTime('@'.$t);
    }
}