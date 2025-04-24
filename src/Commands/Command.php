<?php

namespace Commands;

interface Command {

    public static function getAlias(): string;
    /** @return Argument[] */
    public static function getArguments(): array;
    public static function getHelp(): string;
    public static function isCommandValueRequired(): bool;

    /** @return bool | string */
    public function getArgumentValue(string $args): bool | string;
    public function execute(): int;
}
