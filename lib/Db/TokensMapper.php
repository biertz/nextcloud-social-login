<?php

namespace OCA\SocialLogin\Db;

use OCP\AppFramework\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

class TokensMapper extends QBMapper
{
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'sociallogin_tokens', Tokens::class);
    }

    /**
     * Find the set of Tokens of a user for a specified provider.
     *
     * @param string $uid Nextcloud user id
     * @param string $providerId
     *
     * @return Db\Entity
     * @throws Db\DoesNotExistException
     * @throws Db\MultipleObjectsReturnedException
     * @throws Exception
     */
    public function find(string $uid, string $providerId): ?Db\Entity
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid))
            )
            ->andWhere(
                $qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId))
            );

        return $this->findEntity($qb);
    }

    /**
     * Find all sets of a user's tokens.
     *
     * @throws Exception
     */
    public function findAllByUser(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid))
            );

            return $this->findEntities($qb);
    }

    /**
     * Find all Tokens for all users and all providers.
     *
     * @throws Exception
     */
    public function findAll(): ?array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName()
            );

        return $this->findEntities($qb);
    }

    /**
     * @param string $uid Nextcloud user id
     * @throws Exception
     */
    public function deleteAllByUser(string $uid)
    {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->tableName)
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid, 'string'))
            );
        $qb->execute();
    }
}
