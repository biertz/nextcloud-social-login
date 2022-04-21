<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version040902Date20211217144901 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

    /**
     * Dropping and recreating the database so that it follows standard column naming schema.
     * Reasoning: even if someone else *were* using this in production, data loss would be acceptable due to
     * comparatively short lifetime of tokens and easy fix by logging out and logging in again. Weighed against
     * the importance of consistency, consistency wins.
     *
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     * @throws SchemaException
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sociallogin_tokens')) {
            $table = $schema->createTable('sociallogin_tokens');
            $table->addColumn('id', Types::INTEGER, [
                'notnull' => true,
                'autoincrement' => true
            ]);
            $table->addColumn('uid', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('access_token', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('refresh_token', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('expires_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('provider_type', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('provider_id', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addUniqueConstraint(['uid', 'provider_id']);
            $table->setPrimaryKey(['id']);
        }
        return $schema;
    }

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
