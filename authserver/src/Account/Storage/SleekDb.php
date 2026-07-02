<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Account\Storage;

use Craftorio\Authserver\Entity\Account;
use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Config;
use SleekDB\Query;

/**
 * Interface StorageInterface
 * @package Craftorio\Authserver\AccountStorage
 */
class SleekDb extends StorageAbstract
{
    private $accountsStore;

    /**
     * SleekDb constructor.
     * @param Config $config
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function __construct(Config $config)
    {
        $baseDir = $config->get('baseDir');
        $baseDataDir = $config->get('account.sleekdb.data_dir') ?? 'var' . DIRECTORY_SEPARATOR .'storage';
        $cacheLifetime = $config->get('account.sleekdb.cache_lifetime') ?? 900;
        $dataDir =  $baseDir . DIRECTORY_SEPARATOR . $baseDataDir;
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $accountsConfig = [
            "auto_cache" => false,
            "cache_lifetime" => $cacheLifetime,
            "timeout" => false,
            "primary_key" => "_id",
            "search" => [
                "min_length" => 2,
                "mode" => "or",
                "score_key" => "scoreKey",
                "algorithm" => Query::SEARCH_ALGORITHM["hits"]
            ]
        ];

        $this->accountsStore = new \SleekDB\Store("account", $dataDir, $accountsConfig);
    }

    /**
     * @param AccountInterface $account
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function insert(AccountInterface $account): void
    {
        if ($this->findByUsername($account->getUsername())) {
            throw new \InvalidArgumentException('This username already taken');
        }

        if ($this->findByEmail($account->getEmail())) {
            throw new \InvalidArgumentException('This email already taken');
        }

        $this->accountsStore->insert($this->toArray($account));
    }

    /**
     * @param AccountInterface $account
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function delete(AccountInterface $account): void
    {
        $this->accountsStore->deleteById($account->getId());
    }

    /**
     * Remove any mirror rows matching the given username or email.
     *
     * Used to purge stale mirrors left when an account was deleted directly in
     * MySQL (not via account:delete): the username/email key would otherwise make
     * insert() throw "already taken" when the same name is registered again.
     *
     * @param string $username
     * @param string $email
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function deleteByIdentity(string $username, string $email): void
    {
        foreach ($this->accountsStore->findBy(["username", "=", $username]) as $row) {
            $this->accountsStore->deleteById($row["_id"]);
        }
        foreach ($this->accountsStore->findBy(["email", "=", $email]) as $row) {
            $this->accountsStore->deleteById($row["_id"]);
        }
    }

    /**
     * @param string $id
     * @return AccountInterface|null
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function findById(string $id): ?AccountInterface
    {
        return $this->accountOrNull($this->accountsStore->findById($id));
    }

    /**
     * @param string $externalId
     * @return AccountInterface|null
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function findByExternalId(string $externalId): ?AccountInterface
    {
        return $this->accountOrNull($this->accountsStore->findOneBy(["external_id", "=", $externalId]));
    }

    /**
     * @param string $username
     * @return AccountInterface|null
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function findByUsername(string $username): ?AccountInterface
    {
        return $this->accountOrNull($this->accountsStore->findOneBy(["username", "=", $username]));
    }

    /**
     * @param string $email
     * @return AccountInterface|null
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function findByEmail(string $email): ?AccountInterface
    {
        return $this->accountOrNull($this->accountsStore->findOneBy(["email", "=", $email]));
    }
}
