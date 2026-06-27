<?php

declare(strict_types=1);

namespace Craftorio\Authserver\Account\Storage;

use Craftorio\Authserver\Entity\Account;
use Craftorio\Authserver\Entity\AccountInterface;
use Craftorio\Authserver\Config;

/**
 * Hybrid account storage: MySQL holds credentials (legacy launcher DB),
 * SleekDB holds authserver metadata (internal _id, uuid, external_id link).
 *
 * @package Craftorio\Authserver\AccountStorage
 */
class Mysql extends StorageAbstract
{
    private $sleekDb;
    private $pdo;
    private $h;

    /** @var \ClanCats\Hydrahon\Query\Sql\Table */
    private $table;
    private $columnId;
    private $columnUsername;
    private $columnEmail;
    private $columnPasswordHash;

    protected $selectFields;

    /**
     * Mysql constructor.
     * @param Config $config
     * @throws \ClanCats\Hydrahon\Exception
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\InvalidConfigurationException
     */
    public function __construct(Config $config, SleekDb $sleekDb)
    {
        $this->sleekDb = $sleekDb;

        $dsn      = $config->get('account.mysql.dsn');
        $username = $config->get('account.mysql.username');
        $password = $config->get('account.mysql.password');

        $this->pdo = new \PDO($dsn, $username, $password);
        $this->h = new \ClanCats\Hydrahon\Builder(
            'mysql',
            function($query, string $queryString, array $queryParameters = []) {
            $statement = $this->pdo->prepare($queryString);

            $statement->execute($queryParameters);

            // when the query is fetchable return all results and let hydrahon do the rest
            // (there's no results to be fetched for an update-query for example)
            if ($query instanceof \ClanCats\Hydrahon\Query\Sql\FetchableInterface) {
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }

            // when the query is a instance of a insert return the last inserted id
            elseif($query instanceof \ClanCats\Hydrahon\Query\Sql\Insert) {
                return $this->pdo->lastInsertId();
            }
            // when the query is not a instance of insert or fetchable then
            // return the number os rows affected
            else {
                return $statement->rowCount();
            }
        });

        $this->columnId = $config->get('account.mysql.columns.id');
        $this->columnUsername = $config->get('account.mysql.columns.username');
        $this->columnEmail = $config->get('account.mysql.columns.email');
        $this->columnPasswordHash = $config->get('account.mysql.columns.password_hash');

        $this->table = $this->h->table($config->get('account.mysql.table'));
        $this->selectFields = "{$this->columnId} as external_id, {$this->columnUsername} as username, {$this->columnEmail} as email, {$this->columnPasswordHash} as password_hash";
    }

    /**
     * @param \Closure $closure
     * @return mixed
     * @throws \Throwable
     */
    private function transaction(\Closure $closure)
    {
        $this->pdo->beginTransaction();
        try {
            $result = $closure();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $t) {
            $this->pdo->rollBack();
            throw $t;
        }
    }

    /**
     * @param AccountInterface $account
     * @throws \Throwable
     */
    public function insert(AccountInterface $account): void
    {
        $this->transaction(function () use ($account) {
            if ($this->findByUsername($account->getUsername())) {
                throw new \InvalidArgumentException('This username already taken');
            }

            if ($this->findByEmail($account->getEmail())) {
                throw new \InvalidArgumentException('This email already taken');
            }

            $data = [
                $this->columnUsername => $account->getUsername(),
                $this->columnEmail => $account->getEmail(),
                $this->columnPasswordHash => $account->getPasswordHash(),
            ];

            $externalId = $this->table
                ->insert($data)
                ->execute();

            $account->setExternalId($externalId);

            // Mirror into SleekDB so sessions reference authserver-internal ids.
            $this->sleekDb->insert($account);
        });
    }

    /**
     * @param AccountInterface $account
     * @throws \Throwable
     */
    public function delete(AccountInterface $account): void
    {
        $this->transaction(function () use ($account) {
            $this->table
                ->delete()
                ->where($this->columnId, $account->getExternalId())
                ->execute();

            $this->sleekDb->delete($account);
        });
    }

    /**
     * @param string $id
     * @return AccountInterface|null
     * @throws \ClanCats\Hydrahon\Query\Sql\Exception
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     */
    public function findById(string $id): ?AccountInterface
    {
        $account = $this->sleekDb->findById($id);

        $row = $this->table
            ->select($this->selectFields)
            ->where($this->columnId, $account->getExternalId())
            ->first();

        if (!$row) {
            return null;
        }

        return $this->accountOrNull(array_merge($this->toArray($account), $row));
    }

    /**
     * @param string $username
     * @return AccountInterface|null
     * @throws \ClanCats\Hydrahon\Query\Sql\Exception
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function findByUsername(string $username): ?AccountInterface
    {
        $row = $this->table
            ->select($this->selectFields)
            ->where($this->columnUsername, $username)
            ->first();

        if (!$row) {
            return null;
        }

        $account = $this->sleekDb->findByExternalId($row['external_id']);
        if (!$account) {
            // Lazy backfill: MySQL row exists but SleekDB mirror was never created.
            $account = $this->createAccountFromExternal($row);
        }

        return $this->accountOrNull(array_merge($this->toArray($account), $row));
    }

    /**
     * @param string $email
     * @return AccountInterface|null
     * @throws \ClanCats\Hydrahon\Query\Sql\Exception
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\JsonException
     */
    public function findByEmail(string $email): ?AccountInterface
    {
        // NOTE: queries columnId, not columnEmail — matches legacy schema where email lookup uses id column.
        $row = $this->table
            ->select($this->selectFields)
            ->where($this->columnId, $email)
            ->first();

        if (!$row) {
            return null;
        }

        $account = $this->sleekDb->findByExternalId($row['external_id']);
        if (!$account) {
            $account = $this->createAccountFromExternal($row);
        }

        return $this->accountOrNull(array_merge($this->toArray($account), $row));
    }

    /**
     * @param array $row
     * @return AccountInterface
     * @throws \SleekDB\Exceptions\IOException
     * @throws \SleekDB\Exceptions\IdNotAllowedException
     * @throws \SleekDB\Exceptions\InvalidArgumentException
     * @throws \SleekDB\Exceptions\JsonException
     */
    private function createAccountFromExternal(array $row): AccountInterface
    {
        $account = new Account($row);
        $this->sleekDb->insert($account);

        return $account;
    }
}
