<?php
/**
 * Model class for common handling of various snippets.
 */
final class Meow_CDEGN_Models_Snippet {

    /** @var int */
    private $id;

    /** @var string */
    private $src;

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string */
    private $code;

    /** @var string */
    private $edit_url;

    public function __construct(
        int $id,
        string $src,
        string $name,
        ?string $description,
        string $code,
        string $edit_url
    ) {
        $this->id = $id;
        $this->src = $src;
        $this->name = $name;
        $this->description = $description ?? '';
        $this->code = $code;
        $this->edit_url = $edit_url;
    }

    public function id(): int {
        return $this->id;
    }

    public function src(): string {
        return $this->src;
    }

    public function name(): string {
        return $this->name;
    }

    public function description(): string {
        return $this->description;
    }

    public function code(): string {
        return $this->code;
    }

    public function edit_url(): string {
        return $this->edit_url;
    }
}
