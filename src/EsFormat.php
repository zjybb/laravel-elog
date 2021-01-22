<?php declare(strict_types=1);

namespace Duduke\Elog;

use Illuminate\Support\Arr;
use Monolog\Formatter\ElasticsearchFormatter;

class EsFormat extends ElasticsearchFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $index = $record['context']['index'] ?? '';

        if ($index != '') {
            $this->index = $index;
            $newRecord = Arr::except($record['context'], ['index']);
            $record['message'] != '' && $newRecord['message'] = $record['message'];
            $newRecord['level_name'] = $record['level_name'];
            $newRecord['datetime'] = $record['datetime'];

            return $this->getDocument(parent::format($newRecord));
        }

        $this->index = 'logs';
        return $this->getDocument(parent::format($record));
    }

    /**
     * Convert a log message into an Elasticsearch record
     *
     * @param array $record Log message
     * @return array
     */
    protected function getDocument(array $record): array
    {
        $index = [
            config('app.env'),
            config('app.name'),
            $this->index
        ];
        $record['_index'] = strtolower(implode('_', $index));
        $record['_type'] = $this->type;

        return $record;
    }
}

