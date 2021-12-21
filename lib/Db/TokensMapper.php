<?php

namespace OCA\SocialLogin\Db;

use OCP\AppFramework\Db;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

class TokensMapper extends QBMapper
{
    public function __construct(IDBConnection $db, \Psr\Log\LoggerInterface $logger) {
        parent::__construct($db, 'sociallogin_tokens', Tokens::class);
    }

    /**
     * Find all sets of a user's tokens.
     *
     * @throws Exception
     */
    public function find(string $uid) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid))
            );

            return $this->findEntities($qb);
    }

    public function findAll(): ?array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName()
            );

        try {
            return $this->findEntities($qb);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @throws Db\DoesNotExistException
     * @throws Db\MultipleObjectsReturnedException
     * @throws Exception
     */
    public function findByConnectedLoginsAndProviderId(string $uid, string $providerId)
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
     * @param string $uid Nextcloud user id
     * @throws Exception
     */
    public function deleteAll(string $uid)
    {
        $qb = $this->db->getQueryBuilder();

        $qb->delete($this->tableName)
            ->where(
                $qb->expr()->eq('uid', $qb->createNamedParameter($uid, 'string'))
            );
        $qb->execute();
    }
}