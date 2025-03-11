<?php

namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;

class CodeGeneration extends AbstractCommand {

    // 使用するコマンド名を指定
    protected static ?string $alias = "code-gen";
    protected static bool $requiredCommandValue = true;

    // 引数を割り当て
    public static function getArguments(): array {
        return [
            (new Argument("name"))->description("Name of the file that is to be generated.")->required(false),
        ];
    }

    public function execute(): int {
        $codeGenType = $this->getCommandValue();
        $this->log("Generating code for..." . $codeGenType);

        if ($codeGenType == "migration") {
            $migrationName = $this->getArgumentValue("name");
            $this->generatedMigrationFile($migrationName); 
        } else if ($codeGenType == "seeder") {
            $seederName = $this->getArgumentValue("name");
            $this->generateSeederFile($seederName);
        }

        return 0;
    }

    private function generatedMigrationFile(string $migrationName): void {
        $filename = sprintf(
            "%s_%s_%s.php",
            date("Y-m-d"),
            time(),
            $migrationName
        );

        $migrationContent = $this->getMigrationContent($migrationName);

        // マイグレーションファイルを保存するディレクトリとパス
        $directory = sprintf("%s/../../Database/Migrations", __DIR__);

        // ディレクトリの存在確認と作成
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            $this->log("Directory {$directory} has been created!");
        }

        $path = sprintf("%s/%s", $directory, $filename);

        file_put_contents($path, $migrationContent);
        $this->log("Migration file {$filename} has been generated!");
    }

    private function getMigrationContent(string $migrationName): string {
        $className = $this->pascalCase($migrationName);

        return <<<MIGRATION
        <?php

        namespace Database\Migrations;
        
        use Database\SchemaMigration;
        
        class {$className} implements SchemaMigration {
            public function up(): array {
                // マイグレーションロジックをここに追加してください
                return [];
            }
        
            public function down(): array {
                // ロールバックロジックを追加してください
                return [];
            }
        }
        MIGRATION;
    }

    private function generateSeederFile(string $seederName): void {
        $filename = sprintf("%s.php", $seederName);

        $seederContent = $this->getSeederContent($seederName);

        $directory = sprintf("%s/../../Database/Seeds", __DIR__);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            $this->log("Directory {$directory} has been created!");
        }

        $path = sprintf("%s/%s", $directory, $filename);

        file_put_contents($path, $seederContent);
        $this->log("Seeder file {$filename} has been generated!");
    }

    private function getSeederContent(string $seederName): string {
        $className = $this->pascalCase($seederName);

        return <<<SEEDER
        <?php

        namespace Database\Seeds;

        use Faker\Factory;
        use Database\AbstractSeeder;

        class {$className} extends AbstractSeeder {
            // TODO: tableName文字列の割り当て
            protected ?string \$tableName = null;

            // TODO: tableColumns配列の割り当て
            protected array \$tableColumns = [];

            public function createRowData(): array {
                // TODO: createRowData()メソッドの実装
                return [];
            }
        }          
        SEEDER;
    }

    private function pascalCase(string $string): string {
        return str_replace(" ", "", ucwords(str_replace("_", " ", $string)));
    }

}
