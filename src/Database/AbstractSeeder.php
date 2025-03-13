<?php

namespace Database;

abstract class AbstractSeeder implements SchemaSeeder {

    protected MySQLWrapper $conn;
    protected ?string  $tableName = null;

     // テーブルカラムは、'data_type' と 'column_name' を含む連想配列
    protected array $tableColumns = [];

    // 使用可能なカラムのタイプ、これらはバリデーションチェックとbind_param()のために使用される
    // キーはタイプの文字列で、値はbind_paramの文字列
    const AVAILABLE_TYPES = [
        "int" => "i",
        // PHPのfloatは実際にはdouble型の精度
        "float" => "d",
        "string" => "s",
    ];

    public function __construct(MySQLWrapper $conn) {
        $this->conn = $conn;
    }

    public function seed(): void {
        $data = $this->createRowData();

        if ($this->tableName === null) {
            throw new \Exception("Class requires a table name");
        }

        if (empty($this->tableColumns)) {
            throw new \Exception("Class requires a columns");
        }

        foreach ($data as $row) {
            // 行を検証し問題ないなければ挿入
            $this->validateRow($row);
            $this->insertRow($row);
        }
    }

    // 各行をtableColumnsと照らし合わせて検証する
    public function validateRow(array $row): void {
        if (count($row) !== count($this->tableColumns)) {
            throw new \Exception("Row does not match the ");
        }

        foreach ($row as $i => $value) {
            $columnDataType = $this->tableColumns[$i]["data_type"];
            $columnName = $this->tableColumns[$i]["column_name"];
        }

        if (!isset(static::AVAILABLE_TYPES[$columnDataType])) {
            throw new \InvalidArgumentException(sprintf("The data type %s is not an available data type.", $columnDataType));
        }

        // PHPは、値のデータタイプを返すget_debug_type()関数とgettype()関数の両方を提供している クラス名でも機能する
        // get_debug_typeはネイティブのPHP 8タイプを返す 例えば、floatsのgettype、gettype(4.5)は、ネイティブのデータタイプ'float'ではなく文字列'double'を返す
        if (get_debug_type($value) !== $columnDataType) {
            throw new \InvalidArgumentException(sprintf("Value for %s should be of type %s. Here is the current value: %s", $columnName, $columnDataType, json_encode($value)));
        }
    }

    // 各行をテーブルに挿入 $tableColumnsはデータタイプとカラム名を取得するために使用される
    protected function insertRow(array $row): void {
        // カラム名取得
        $columnNames = array_map(function($columnInfo) {
            return $columnInfo["column_name"];
        }, $this->tableColumns
    );

        // クエリを準備する際、count($row)のプレースホルダー "?" がある bind_param関数はこれにデータを挿入
        $placeholders = str_repeat("?,", count($row) - 1) . "?";

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUE (%s)",
            $this->tableName,
            implode(",", $columnNames),
            $placeholders
        );

        $stmt = $this->conn->prepare($sql);

        $dataTypes = implode(array_map(
            function($columnInfo) {
                return static::AVAILABLE_TYPES[$columnInfo["data_type"]];
            }, $this->tableColumns)
        );

        $stmt->bind_param($dataTypes, ...array_values($row));

        $stmt->execute();
    }

}
