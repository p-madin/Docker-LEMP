<?php

namespace Services;

/**
 * GenericDataMapper
 * 
 * Abstraction layer to bridge raw database result sets and structured component data.
 */
class GenericDataMapper {
    /**
     * Map raw database rows to a standardized structure based on configuration.
     * 
     * @param array $rawData The result set from the database (e.g. $stmt->fetchAll()).
     * @param array $mappingConfig Configuration array where keys are destination keys and values are source column names or descriptors.
     * @return array The mapped and formatted data.
     */
    public function map(array $rawData, array $mappingConfig): array {
        $result = [];
        
        foreach ($rawData as $row) {
            $mappedRow = [];
            foreach ($mappingConfig as $destKey => $sourceSpec) {
                // If sourceSpec is just a string, it's the column name.
                // If it's an array, it contains 'key', 'format', etc.
                $sourceKey = is_array($sourceSpec) ? ($sourceSpec['key'] ?? null) : $sourceSpec;
                
                if (!$sourceKey) continue;

                $val = $row[$sourceKey] ?? null;

                // Apply formatting if specified
                if (is_array($sourceSpec) && isset($sourceSpec['format'])) {
                    $val = $this->applyFormat($val, $sourceSpec['format']);
                }

                $mappedRow[$destKey] = $val;
            }
            $result[] = $mappedRow;
        }

        return $result;
    }

    /**
     * Apply formatting to a value.
     */
    protected function applyFormat($value, $format) {
        if ($value === null) return null;

        switch (strtolower($format)) {
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'datetime':
                return date('Y-m-d H:i:s', strtotime($value));
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
            case 'bool':
                return (bool)$value;
            default:
                return $value;
        }
    }
}
