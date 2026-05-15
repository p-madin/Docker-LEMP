<?php

interface DataProviderInterface {
    /**
     * Returns the column definitions for the table.
     * Each column should follow the structure: ['key' => '...', 'label' => '...', 'action' => '...', 'actionConfig' => [...]]
     */
    public function getColumns(): array;

    /**
     * Returns the data rows for the table.
     */
    public function getData(): array;

    /**
     * Returns the key used for nested/hierarchical data (optional).
     */
    public function getNestedKey(): ?string;

    /**
     * Returns a human-readable name for this data source.
     */
    public function getDataSourceName(): string;
}
