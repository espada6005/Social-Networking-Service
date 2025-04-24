<?php

namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;
use Database\MySQLWrapper;

class Migrate extends AbstractCommand {

    // 使用するコマンド名を設定
    protected static ?string $alias = "migrate";

    // 引数を割り当て
    public static function getArguments(): array {
        return [
            (new Argument("rollback"))->description("Roll backwards. An integer n may also be provided to rollback n times.")->required(false)->allowAsShort(true),
            (new Argument("init"))->description("Create the migrations table if it doesn't exist.")->required(false)->allowAsShort(true),
        ];
    }

    public function execute(): int {
        $rollback = $this->getArgumentValue("rollback");

        if ($this->getArgumentValue("init")) {
            $this->createMigrationsTable();
        }

        if ($rollback === false) {
            $this->log("Starting migration...");
            $this->migrate();
        } else {
            // rollbackはtrueに設定されているか、それに関連付けられた値が整数として存在するかのいずれか
            $rollback = $rollback === true ? 1 : (int) $rollback;
            $this->log("Runnning rollback...");
            for ($i=0; $i <$rollback; $i++) { 
                $this->rollback();
            }
        }
        return 0;
    }

    private function createMigrationsTable(): void {
        $this->log("Creating migrations table if necessary...");

        $mysqli = new MySQLWrapper();

        $result = $mysqli->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL
            );
        ");

        if ($result === false) {
            throw new \Exception("Failed to create migration table;");
        }

        $this->log("Done setting up migration tables.");
    } 

    private function migrate(): void {
        $this->log("Runnning migration...");

        $lastMigration = $this->getLastMigration();
        // ファイル名を日付順(ASC)に並べた配列を返す
        $allMigrations = $this->getAllMigrationFiles();
        $startIndex = ($lastMigration) ? array_search($lastMigration, $allMigrations) + 1 : 0;

        for ($i=$startIndex; $i < count($allMigrations); $i++) { 
            $filename = $allMigrations[$i];

            include_once($filename);
            
            $migrationClass = $this->getClassnameFromMigrationFilename($filename);
            $migration = new $migrationClass();
            $this->log(sprintf("Processing up migration for %s", $migrationClass));
            $queries = $migration->up();

            if (empty($queries)) {
                throw new \Exception("Must have queries to run for . " . $migrationClass);
            }

            $this->processQueries($queries);
            $this->insertMigration($filename);
        }

        $this->log("Migration ended...");
    }

    private function getClassnameFromMigrationFilename(string $filename): string {
        // マイグレーションのクラス名を正規表現で取得する
        // /: これは正規表現パターンの開始と終了のデリミタ
        // ([^_]+): "_"以外のすべての文字を一致させる ()はグループをキャプチャするためのもので、[^abc]はabc以外を意味する キャプチャグループは個別に一致させるために使用される
        // \.php: "."が"\"でエスケープされているので、これは終端が'.php'に一致しなければならないことを意味する
        if (preg_match("/([^_]+)\.php$/", $filename, $matches)) {
            return sprintf("%s\%s", "Database\Migrations", $matches[1]);
        } else {
            throw new \Exception("Unexpected migration filename format: " . $filename);
        }
    }

    private function getLastMigration(): ?string {
        $mysqli = new MySQLWrapper();

        $query = "SELECT filename FROM migrations ORDER BY id DESC LIMIT 1";

        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row["filename"];
        }
        
        return null;
    }

    private function getAllMigrationFiles(string $order = "asc"): array {
        $directory = sprintf("%s/../../Database/Migrations", __DIR__);
        $this->log($directory);
        // globはワイルドカード文字列を引数として渡すと、一致する"globbing pathnames"をすべて返すLinuxのシステムコール
        // これはワイルドカードに一致するすべてのファイルを意味する
        $allFiles = glob($directory . "/*.php");

        usort($allFiles, function ($a, $b) use ($order){
            $compareResult = strcmp($a, $b);
            return ($order === "desc") ? -$compareResult : $compareResult;  
        });
        return $allFiles;
    }

    private function processQueries(array $queries): void {
        $mysqli = new MySQLWrapper();
        foreach ($queries as $query) {
            $result = $mysqli->query($query);
            if ($result === false) {
                throw new \Exception(sprintf("Query {%s} failed", $query));
            } else {
                $this->log("Ran query: " . $query);
            }
        }
    }

    private function insertMigration(string $filname): void {
        $mysqli = new MySQLWrapper();
        // Prepareは、SQLインジェクションのようなものを防ぐために文字列を準備するために使用される
        // statementはprepareが返す ここから、オリジナルのクエリ文字列に実際の値を挿入して、クエリを実行することもできる
        $statement = $mysqli->prepare("INSERT INTO migrations (filename) VALUES (?)");

        if (!$statement) {
            throw new \Exception("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        }

        $statement->bind_param("s", $filname);

        if (!$statement->execute()) {
            throw new \Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $statement->close(); 

    }

    private function rollback(int $n = 1): void {
        $this->log("Rolling back {$n} migration(s)...");

        $lastMigration =$this->getLastMigration();
        $allMigrations = $this->getAllMigrationFiles();

        // ソートされたリストで最後のマイグレーションのインデックスを探す
        $lastMigrationIndex = array_search($lastMigration, $allMigrations);

        if ($lastMigrationIndex === false) {
            $this->log("Could not find the last migration ran: " . $lastMigration);
            return;
        }

        $count = 0;

        for ($i = $lastMigrationIndex; $count < $n && $i >= 0; $i--) { 
            $filename = $allMigrations[$i];

            $this->log("Rolling back: {$filename}");

            include_once($filename);

            $migratoinClass = $this->getClassnameFromMigrationFilename($filename);
            $migration = new $migratoinClass();

            $queries = $migration->down();
            if (empty($queries)) {
                throw new \Exception("Must have queries to run for ." . $migratoinClass);
            }

            $this->processQueries($queries);
            $this->removeMigration($filename);
            $count++;
        }

        $this->log("Rollback completed.");
    }

    private function removeMigration(string $filename): void {
        $mysqli = new MySQLWrapper();
        $statement = $mysqli->prepare("DELETE FROM migrations WHERE filename = ?");

        if (!$statement) {
            throw new \Exception("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
        }

        $statement->bind_param("s", $filename);
        if (!$statement->execute()) {
            throw new \Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $statement->close();
    }

}
