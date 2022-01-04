<?php

namespace OCA\SocialLogin\Service;

use OC\User\LoginException;
use OCA\SocialLogin\AppInfo\Application;
use OCA\SocialLogin\Db\TokensMapper;
use OCA\SocialLogin\Provider\CustomOAuth1;
use OCA\SocialLogin\Provider\CustomOAuth2;
use OCA\SocialLogin\Provider\CustomOpenIDConnect;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use Hybridauth\Provider;
use Safe\Exceptions\JsonException;

class ConfigService
{
    private $appName;
    /** @var TokensMapper */
    private $tokensMapper;
    /** @var IDBConnection */
    private $db;
    private $jobList;
    private $config;
    private $urlGenerator;

    private $configMapping = [
        'default' => [
            'keys' => [
                'id' => 'appid',
                'secret' => 'secret',
            ],
        ],
        self::TYPE_OPENID => [
            'openid_identifier' => 'url',
        ],
        self::TYPE_OAUTH1 => [
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'profile_url'    => 'profileUrl',
            ],
            'logout_url' => 'logoutUrl',
        ],
        self::TYPE_OAUTH2 => [
            'scope' => 'scope',
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'api_base_url'     => 'apiBaseUrl',
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'profile_url'    => 'profileUrl',
            ],
            'profile_fields' => 'profileFields',
            'displayname_claim' => 'displayNameClaim',
            'groups_claim'  => 'groupsClaim',
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
        ],
        self::TYPE_OIDC => [
            'scope' => 'scope',
            'keys' => [
                'id'     => 'clientId',
                'secret' => 'clientSecret',
            ],
            'endpoints' => [
                'authorize_url'    => 'authorizeUrl',
                'access_token_url' => 'tokenUrl',
                'user_info_url'    => 'userInfoUrl',
            ],
            'displayname_claim' => 'displayNameClaim',
            'groups_claim'  => 'groupsClaim',
            'group_mapping' => 'groupMapping',
            'logout_url'    => 'logoutUrl',
            'saveTokens' => 'saveTokens',
        ],
    ];

    const TYPE_OPENID = 'openid';
    const TYPE_OAUTH1 = 'custom_oauth1';
    const TYPE_OAUTH2 = 'custom_oauth2';
    const TYPE_OIDC = 'custom_oidc';

    const TYPE_CLASSES = [
        self::TYPE_OPENID => Provider\OpenID::class,
        self::TYPE_OAUTH1 => CustomOAuth1::class,
        self::TYPE_OAUTH2 => CustomOAuth2::class,
        self::TYPE_OIDC => CustomOpenIDConnect::class,
    ];

    public function __construct($appName, TokensMapper $tokensMapper, IDBConnection $db, IJobList $jobList, IConfig $config, IURLGenerator $urlGenerator){
        $this->appName = $appName;
        $this->tokensMapper = $tokensMapper;
        $this->db = $db;
        $this->jobList = $jobList;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
    }

    public function defaultConfig($provider):array {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        if (is_array($providers) && in_array($provider, array_keys($providers))) {
            foreach ($providers as $name => $prov) {
                if ($name === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.oauth', ['provider' => $provider]);
                    $config = array_merge([
                        'callback' => $callbackUrl,
                        'default_group' => $prov['defaultGroup'],
                        'orgs' => $prov['orgs'] ?? null,
                    ], $this->applyConfigMapping('default', $prov));

                    if (isset($prov['auth_params']) && is_array($prov['auth_params'])) {
                        foreach ($prov['auth_params'] as $k => $v) {
                            if (!empty($v)) {
                                $config['authorize_url_parameters'][$k] = $v;
                            }
                        }
                    }
                    break;
                }
            }
        }
        return $config;
    }

    /**
     * @throws LoginException
     */
    public function customConfig($type, $provider): array {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        if (isset($providers[$type])) {
            foreach ($providers[$type] as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.custom', [
                        'type'=> $type,
                        'provider' => $provider
                    ]);
                    $config = array_merge([
                        'callback'          => $callbackUrl,
                        'default_group'     => $prov['defaultGroup'],
                    ], $this->applyConfigMapping($type, $prov));

                    if (isset($config['endpoints']['authorize_url']) && strpos($config['endpoints']['authorize_url'], '?') !== false) {
                        list($authUrl, $authQuery) = explode('?', $config['endpoints']['authorize_url'], 2);
                        $config['endpoints']['authorize_url'] = $authUrl;
                        parse_str($authQuery, $config['authorize_url_parameters']);
                    }
                    break;
                }
            }
        }
        return $config;
    }

    /**
     * Same as customConfig, but does not require to pass the provider type. // TODO merge both functions
     *
     * @param $provider
     * @return array
     * @throws LoginException
     */

    public function getCustomProviderConfig($provider): array {
        $config = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        foreach ($providers as $type=>$provs) {
            foreach ($provs as $prov) {
                if ($prov['name'] === $provider) {
                    $callbackUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName.'.login.custom', [
                        'type'=> $type,
                        'provider' => $provider
                    ]);
                    $config = array_merge([
                        'callback'          => $callbackUrl,
                        'default_group'     => $prov['defaultGroup'],
                    ], $this->applyConfigMapping($type, $prov));

                    if (isset($config['endpoints']['authorize_url']) && strpos($config['endpoints']['authorize_url'], '?') !== false) {
                        list($authUrl, $authQuery) = explode('?', $config['endpoints']['authorize_url'], 2);
                        $config['endpoints']['authorize_url'] = $authUrl;
                        parse_str($authQuery, $config['authorize_url_parameters']);
                    }
                    break;
                }
            }
        }
        return $config;
    }

    /**
     * Returns the names of all providers as an array of strings.
     *
     * @return array
     * @throws JsonException
     */
    public function getProviderIds(): array
    {
        $providerIds = $this->getCustomProviderIds();
        $providerIds = array_merge($providerIds, $this->getOAuthProviderIds());

        return $providerIds;
    }

    /**
     * Returns the names of all custom providers as an array of strings.
     *
     * @return array
     * @throws JsonException
     */
    public function getCustomProviderIds(): array
    {
        $providerIds = [];
        $custom_providers = \Safe\json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true);
        foreach ($custom_providers as $type) {
            foreach ($type as $provider){
                $providerIds[] = $provider['name'];
            }
        }
        return $providerIds;
    }

    /**
     * Returns the names of all preconfigured oauth providers as an array of strings.
     *
     * @return array
     * @throws JsonException
     */
    public function getOAuthProviderIds(): array {
        $providerIds = [];
        $oauth_providers = \Safe\json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true);
        $oauth_providers = array_keys($oauth_providers);
        foreach ($oauth_providers as $provider) {
            $providerIds[] = $provider;
        }
        return $providerIds;
    }

    /**
     * @throws LoginException
     */
    public function getCustomProviderAuthorizeUrl(string $providerId){
        $config = $this->getCustomProviderConfig($providerId);
        return $config['endpoints']['authorize_url'];
    }

    /**
     * @throws LoginException
     */
    public function getCustomProviderTokenUrl(string $providerId){
        $config = $this->getCustomProviderConfig($providerId);
        return $config['endpoints']['access_token_url'];
    }

    /**
     * @throws LoginException
     */
    private function applyConfigMapping($mapping, $data): array
    {
        if (!is_array($mapping)) {
            if (!isset($this->configMapping[$mapping])) {
                throw new LoginException(sprintf('Unknown provider type: %s', $mapping));
            }
            $mapping = $this->configMapping[$mapping];
        }
        $result = [];
        foreach ($mapping as $k => $v) {
            if (is_array($v)) {
                $result[$k] = $this->applyConfigMapping($v, $data);
            } else {
                $result[$k] = isset($data[$v]) ? $data[$v] : null;
            }
        }
        return $result;
    }
}