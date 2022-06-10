<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version041503Date20220610115731 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('sociallogin_tokens')) {
            $table = $schema->getTable('sociallogin_tokens');
            $table->changeColumn('access_token', [
                'Type' => Type::getType(Types::TEXT),
                // make sure MySQL uses LONGTEXT instead of TINYTEXT
                'Length' => 4294967295
            ]);
            $table->changeColumn('refresh_token', [
                'Type' => Type::getType(Types::TEXT),
                // make sure MySQL uses LONGTEXT instead of TINYTEXT
                'Length' => 4294967295
            ]);
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
