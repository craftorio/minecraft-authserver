<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Authenticator;

use Craftorio\Authserver\Authenticator\Exception\UnauthorizedException;
use Craftorio\Authserver\Config;
use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Entity;
use Craftorio\Authserver\Hash\HashInterface;
use Craftorio\Authserver\Session;
use Craftorio\Authserver\Skin;
use Craftorio\Authserver\Account\Storage\StorageInterface;
use Craftorio\Authserver\ProfileId;
use stdClass;

/**
 * Interface StorageInterface
 * @package Craftorio\Authserver\AccountStorage
 */
class Authenticator implements AuthenticatorInterface
{
    private $hash;
    private $config;
    private $accountStorage;
    private $session;
    private $skin;

    /**
     * Authenticator constructor.
     * @param HashInterface $hash
     * @param Config $config
     * @param StorageInterface $accountStorage
     * @param Session $session
     * @param Skin $skin
     */
    public function __construct(
        HashInterface $hash,
        Config $config,
        StorageInterface $accountStorage,
        Session $session,
        Skin $skin
    ) {
        $this->hash = $hash;
        $this->config = $config;
        $this->accountStorage = $accountStorage;
        $this->session = $session;
        $this->skin = $skin;
    }

    /**
     * @return \SleekDB\Store
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    private function getSessionStore()
    {
        return $this->session->getSessionStore();
    }

    /**
     * @return \SleekDB\Store
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    private function getServerSessionStore()
    {
        return $this->session->getServerSessionStore();
    }

    /**
     * @return \SleekDB\Store
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    private function getSkinStore()
    {
        return $this->skin->getStore();
    }

    /**
     * @param AccountInterface $account
     * @param string $password
     * @return bool
     */
    public function checkPassword(AccountInterface $account, string $password): bool
    {
        return $this->hash->checkPassword($account, $password);
    }

    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function hashPassword(string $password): string
    {
        return $this->hash->hashPassword($password);
    }

    /**
     * @param AccountInterface $account
     * @param string $clientToken
     * @return array|null
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function refreshSession(AccountInterface $account, string $clientToken): ?array
    {
        // One account may have multiple SleekDB session rows (re-login, refresh).
        // current() picks the first row — order is undefined; its accessToken is reused when present.
        $sessions = $this->getSessionStore()->findBy(['accountUuid', '=', $account->getUuid()]);
        $currentSession = current($sessions);
        $accessToken = $currentSession['accessToken'] ?? $this->generateAccessToken();

        // Collapse to a single active session per account (Yggdrasil expects one clientToken pair).
        if (count($sessions) > 1) {
            foreach ($sessions as $session) {
                if ($currentSession['_id'] != $session['_id']) {
                    $this->getSessionStore()->deleteById($session['_id']);
                }
            }
        }

        // Update or create session
        if ($currentSession) {
            $this->getSessionStore()->updateById($currentSession['_id'], [
                'accountId'   => $account->getId(),
                'accountUuid' => $account->getUuid(),
                'profileId'   => $account->getSelectedProfile()->getId(),
                'profileUuid' => $account->getSelectedProfile()->getUuid(),
                'accessToken' => $accessToken,
                'clientToken' => $clientToken,
            ]);
        } else {
            $this->getSessionStore()->insert([
                'accountId'   => $account->getId(),
                'accountUuid' => $account->getUuid(),
                'profileId'   => $account->getSelectedProfile()->getId(),
                'profileUuid' => $account->getSelectedProfile()->getUuid(),
                'accessToken' => $accessToken,
                'clientToken' => $clientToken,
            ]);
        }

        $array = $this->accountToArray($account);
        $array['accessToken'] = $accessToken;
        $array['clientToken'] = $clientToken;

        return $array;
    }

    /**
     * @param AccountInterface $account
     * @param string $password
     * @param string $clientToken
     * @return array|null
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function authenticateByPassword(AccountInterface $account, string $password, string $clientToken): ?array
    {
        if ($this->checkPassword($account, $password)) {
            return $this->refreshSession($account, $clientToken);
        }

        return null;
    }

    /**
     * @return string
     */
    /**
     * 64-char hex token matching Mojang accessToken shape.
     * Uses rand() (not random_int) for historical compatibility with existing clients.
     */
    private function generateAccessToken(): string
    {
        $chars    = "0123456789abcdef";
        $max      = 64;
        $size     = StrLen($chars) - 1;
        $token = null;
        while ($max--) {
            $token .= $chars[rand(0, $size)];
        }

        return $token;
    }

    /**
     * @param AccountInterface $account
     * @return array
     */
    private function accountToArray(AccountInterface $account): array
    {
        return [
            "availableProfiles" => $account->getProfiles(),
            "selectedProfile" => $account->getSelectedProfile(),
            "user" => [
                // Mojang legacy: user.id is md5(uuid), not the raw uuid string.
                "id" => md5($account->getUuid() ?? $account->getId()),
                "username" => $account->getUsername(),
            ]
        ];
    }

    /**
     * @param string $accessToken
     * @param string $selectedProfile
     * @param string $serverId
     * @throws UnauthorizedException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function joinServer(string $accessToken, string $selectedProfile, string $serverId)
    {
        $sessionData = $this->getSessionStore()->findOneBy(['accessToken', '=', $accessToken]);
        if (!$sessionData || empty($sessionData['accountId'])) {
            throw new UnauthorizedException();
        }

        $account = $this->accountStorage->findById($sessionData['accountId']);
        if (!$account) {
            throw new UnauthorizedException();
        }

        // serverId is stored but not used for lookup — one join row per account uuid.
        // serverId filter is intentionally disabled (see hasJoinedServer).
        $serverSessionData = $this->getServerSessionStore()->findOneBy([
            ['accountUuid', '=', $account->getUuid()],
//            'AND',
//            ['serverId', '=', $serverId]
        ]) ?? [];

        $serverSessionData['accessToken'] = $accessToken;
        $serverSessionData['accountId'] = $account->getId();
        $serverSessionData['accountUuid'] = $account->getUuid();
        $serverSessionData['username'] = $account->getUsername();
        $serverSessionData['serverId'] = $serverId;
        $serverSessionData['selectedProfile'] = $selectedProfile;

        if (empty($serverSessionData['_id'])) {
            $this->getServerSessionStore()->insert($serverSessionData);
        } else {
            // Full-document update (includes _id) rather than updateById.
            $this->getServerSessionStore()->update($serverSessionData);
        }
    }

    /**
     * @param string $serverId
     * @param string $username
     * @return array
     * @throws UnauthorizedException
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function hasJoinedServer(string $serverId, string $username)
    {
        // $serverId is accepted for Yggdrasil API compatibility but not validated here.
        $serverSessionData = $this->getServerSessionStore()->findOneBy([
            ['username', '=', $username],
//            'AND',
//            ['serverId', '=', $serverId]
        ]);

        if (!$serverSessionData) {
            throw new UnauthorizedException();
        }

        // Resolve by username so a stale accountId in the join row still works after
        // SleekDB mirror cleanup (same hybrid-storage edge case as findByUsername backfill).
        $account = $this->accountStorage->findByUsername($username);
        if (!$account) {
            throw new UnauthorizedException();
        }

        // Profile id comes from the account's selected profile, not from join payload selectedProfile.
        return [
            'id' => $account->getSelectedProfile()->getId(),
            'name' => $account->getUsername(),
            'properties' => $this->getProperties($account)
        ];
    }

    /**
     * @param string $profileId
     * @return array
     */
    public function getProfile(string $profileId): array
    {
        $normalized = ProfileId::normalize($profileId);
        $account = $this->findAccountForProfileRef($normalized);

        if (!$account) {
            return [];
        }

        return [
            'id' => $normalized,
            'name' => $account->getUsername(),
            'properties' => $this->getProperties($account),
        ];
    }

    /**
     * Resolve account by profile id, uuid, or OfflinePlayer-derived id.
     *
     * Clients may send profile refs in several formats; profileRefMatches() accepts:
     * md5(profile uuid), raw profile uuid, account uuid, or OfflinePlayer:{username} hash.
     */
    private function findAccountForProfileRef(string $normalizedRef): ?AccountInterface
    {
        // Fast path: indexed lookup on profileId stored at login.
        $sessionData = $this->getSessionStore()->findOneBy(['profileId', '=', $normalizedRef]);
        if (!empty($sessionData['accountId'])) {
            $account = $this->accountStorage->findById($sessionData['accountId']);
            if ($account) {
                return $account;
            }
        }

        // Fallback: scan all sessions when ref format does not match stored profileId.
        foreach ($this->getSessionStore()->findAll() as $session) {
            if (empty($session['accountId'])) {
                continue;
            }
            $account = $this->accountStorage->findById($session['accountId']);
            if ($account && $this->profileRefMatches($normalizedRef, $account)) {
                return $account;
            }
        }

        return null;
    }

    private function profileRefMatches(string $normalizedRef, AccountInterface $account): bool
    {
        $profile = $account->getSelectedProfile();

        if ($normalizedRef === ProfileId::normalize($profile->getId())) {
            return true;
        }
        if ($normalizedRef === ProfileId::normalize($profile->getUuid())) {
            return true;
        }
        if ($account->getUuid() && $normalizedRef === ProfileId::normalize($account->getUuid())) {
            return true;
        }
        if ($normalizedRef === ProfileId::offlineUsername($account->getUsername())) {
            return true;
        }

        return false;
    }

    /**
     * @param string $username
     * @return array|null
     */
    public function getProfileByUsername(string $username): ?array
    {
        $account = $this->accountStorage->findByUsername($username);
        if (!$account) {
            return null;
        }
        $profile = $account->getSelectedProfile();

        return [
            'id' => ProfileId::normalize($profile->getId()),
            'name' => $profile->getName(),
        ];
    }

    /**
     * @param AccountInterface $account
     * @return array[]
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    private function getProperties(AccountInterface $account): array
    {
        return [
            $this->getPropertiesTextures($account),
        ];
    }

    /**
     * @param AccountInterface $account
     * @return array
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    private function getPropertiesTextures(AccountInterface $account): array
    {
        $textures = $this->getTextures($account);
        $signature = '';

        $pemFile = $this->config->get('certificatesDir') . DIRECTORY_SEPARATOR . 'yggdrasil_session_private.pem';
        if (!is_readable($pemFile)) {
            throw new \Exception("Can't read pem file");
        }

        // Sign base64-encoded textures blob — same algorithm/key as Mojang Yggdrasil (sha1WithRSA).
        // Private key is produced by certificates:generate (1024-bit RSA, sha1 digest).
        $key = openssl_pkey_get_private("file://{$pemFile}");
        openssl_sign($textures, $signature, $key, 'sha1WithRSAEncryption');

        return [
            'name' => 'textures',
            'value' => $textures,
            'signature' => base64_encode($signature),
        ];
    }

    /**
     * @param AccountInterface $account
     * @return string
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function getTextures(AccountInterface $account): string
    {
        $textures = [];
        // Skin file is resolved from disk each call; SleekDB row is rewritten as a texture index.
        //$skin = $this->getSkinStore()->findOneBy(['profile_uuid', '=', $account->getSelectedProfile()->getUuid()]) ?? [];
        $skin['id'] = $account->getId();
        $skin['username'] = $account->getUsername();
        $skin['profile_uuid'] = $account->getSelectedProfile()->getUuid();
        $skin['profile_id'] = $account->getSelectedProfile()->getId();
        $skin['timestamp'] = time() * 1000;

        // First file in skinDir/{lowercase username}/ wins (directory order from scandir).
        $basePath = $this->config->get('skinDir') . DIRECTORY_SEPARATOR . strtolower($account->getUsername());
        $path = '';
        if (is_dir($basePath)) {
            foreach (scandir($basePath) as $file) {
                if (is_file("{$basePath}/{$file}")) {
                    $path = "{$basePath}/{$file}";
                    break;
                }
            }
        }

        // Hash is sha256 of the filesystem path string, not file bytes — used as /texture/@hash key.
        $skin['hash'] = hash('sha256', $path);
        $skin['path'] = $path;
        $skinEntity = new Entity\Skin($skin);
        $this->getSkinStore()->deleteBy(['id', '=', $account->getId()]);
        $this->getSkinStore()->insert($skinEntity->jsonSerialize());
        
        // URL mimics Mojang CDN shape; actual bytes are served locally via GET /texture/@hash.
        $textures = [
            'SKIN' => [
                'url' => "https://textures.minecraft.net/texture/{$skin['hash']}",
            ]
        ];

        return base64_encode(
            json_encode([
                'timestamp' => $skin['timestamp'],
                'profileId' => $account->getSelectedProfile()->getId(),
                'profileName' => $account->getSelectedProfile()->getName(),
                // Empty textures when no skin file — client falls back to default steve/alex.
                'textures' => $path ? $textures : [],
            ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
        );
    }
}
