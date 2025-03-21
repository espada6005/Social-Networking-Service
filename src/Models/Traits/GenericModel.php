<?php

namespace Models\Traits;

trait GenericModel {

    public function toArray(): array {
        return (array) $this;
    }

    public function toString(): string {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

}
