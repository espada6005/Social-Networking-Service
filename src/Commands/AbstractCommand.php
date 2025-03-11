<?php

namespace Commands;

use Exception;

abstract class AbstractCommand implements Command {

    protected ?string $value;
    protected array $argsMap = [];
    protected static ?string $alias = null;

    protected static bool $requiredCommandValue = false;

    /**
     * @throws Exception　
     */
    public function __construct() {
        $this->setUpArgsMap();
    }

    /**
     * シェルからすべての引数を読み込み、それをこのクラスの getArguments() と整列するハッシュマップを作成する
     * このargsMapは getArgumentValue() のために使用される
     * すべての引数は短縮バージョンでは "-"、完全なバージョンでは "--" で始まる
     **/
    public function setUpArgsMap(): void {
        // オリジナルのマッピングを設定
        $args = $GLOBALS["argv"];
        // エイリアスのインデックスが見つかるまで探索
        $startIndex = array_search($this->getAlias(), $args);

        if ($startIndex === false) {
            throw new Exception(sprintf("Could not find alias %s", $this->getAlias()));
        } else {
            $startIndex++;
        }

        $shellArgs = [];

        // メインコマンドの値である初期を取得
        if (!isset($args[$startIndex]) || ($args[$startIndex][0] === "-")) {
            if ($this->isCommandValueRequired()) {
                throw new Exception(sprintf("%s's value is required." , $this->getAlias()));
            }
        } else {
            $this->argsMap[$this->getAlias()] = $args[$startIndex];
            $startIndex++;
        }
        
        // すべての引数を$agrsハッシュに格納
        for ($i=$startIndex; $i < count($args); $i++) { 
            $arg = $args[$i];

            if ($arg[0].$arg[1] === "--") {
                $key = substr($arg, 2);
            } elseif ($arg[0] === "-") {
                $key = substr($arg, 1);
            } else throw new Exception("Options must start with - or --");

            $shellArgs[$key] = true;

            // 次のargsエントリがオプションでない場合は、引数値となる iも同様にインクリメント
            if (isset($args[$i + 1]) && $args[$i + 1] !== "-") {
                $shellArgs[$key] = $args[$i + 1];
                $i++;
            }
        }

        // このコマンドの引数を設定
        foreach ($this->getArguments() as $argument) {
            $argsString = $argument->getArgument();
            $value = null;

            if ($argument->isShortAllowed() && isset($shellArgs[$argsString[0]])) {
                $value = $shellArgs[$argsString[0]];
            } else if (isset($shellArgs[$argsString])) {
                $value = $shellArgs[$argsString];
            }

            if ($value === null) {
                if ($argument->isRequired()) {
                    throw new Exception(sprintf("Could not find the required argument %s", $argsString));
                } else {
                    $this->argsMap[$argsString] = false;
                }
            } else {
                $this->argsMap[$argsString] = $value;
            }    
        }
        $this->log(json_encode($this->argsMap));
    }

    public static function getHelp(): string {
        $helpString = "Command: " . static::getAlias() . (static::isCommandValueRequired() ? " {value}" : "") . PHP_EOL;

        $arguments = static::getArguments();
        if (empty($arguments)) {
            return $helpString;
        }

        $helpString .= "Arguments:" . PHP_EOL;

        foreach ($arguments as $argument) {
            $helpString .= " --" . $argument->getArgument(); // long argument name
            if ($argument->isShortAllowed()) {
                $helpString .= " (-" . $argument->getArgument()[0] . ")"; // short argument name
            }
            $helpString .= ": " . $argument->getDescription();
            $helpString .= $argument->isRequired() ? " (Required)" : "(Optional)";
            $helpString .= PHP_EOL;
        }

        return $helpString;
    }

    public static function getAlias(): string {
        return static::$alias !== null ? static::$alias : static::class;
    }

    public static function isCommandValueRequired(): bool {
        return static::$requiredCommandValue;
    }

    public function getCommandValue(): string {
        return $this->argsMap[static::getAlias()] ?? "";
    }

    public function getArgumentValue(string $arg): bool|string {
        return $this->argsMap[$arg];
    }

    protected function log(string $info): void {
        fwrite(STDOUT, $info . PHP_EOL);
    }

    /** @return Argument[] */
    public abstract static function getArguments(): array;
    public abstract function execute(): int;

}
