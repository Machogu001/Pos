<?php

namespace App\utils;

use Illuminate\Http\Request;

/**
 * Minimal helpers class compatible with legacy usage in HRM controllers.
 * Provides a `filter` method that applies simple request-driven filters
 * to an Eloquent query builder.
 */
class helpers
{
    /**
     * Apply filters to the given query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param array $columns  Array of column names to test on
     * @param array $param    Array of operators corresponding to columns (e.g. 'like', '=')
     * @param Request $request
     * @return \Illuminate\Database\Query\Builder
     */
    public function filter($query, array $columns = [], array $param = [], Request $request)
    {
        foreach ($columns as $index => $column) {
            $operator = isset($param[$index]) ? strtolower($param[$index]) : '=';

            // If the request has a parameter with the exact column name, use it.
            if ($request->filled($column)) {
                $value = $request->get($column);

                if ($operator === 'like') {
                    $query = $query->where($column, 'LIKE', "%{$value}%");
                } else {
                    $query = $query->where($column, $operator, $value);
                }
            }

            // Also support a generic `filter_<column>` naming if present.
            $altKey = 'filter_' . $column;
            if ($request->filled($altKey)) {
                $value = $request->get($altKey);
                if ($operator === 'like') {
                    $query = $query->where($column, 'LIKE', "%{$value}%");
                } else {
                    $query = $query->where($column, $operator, $value);
                }
            }
        }

        return $query;
    }
}
