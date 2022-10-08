<?php

class Core_Service_Helper extends Phpfox_Service
{
    /**
     * Get short number string
     *
     * @param integer $number
     * @param int $precision
     *
     * @return string
     * @since 4.6.0
     * @author phpFox LLC
     */
    public function shortNumber($number, $precision = 1)
    {
        $n_format = '';
        $suffix = '';

        if (!function_exists('roundDown')) {
            function roundDown($number, $precision = 1)
            {
                $fig = (int)str_pad('1', $precision + 1, '0');

                return (floor($number * $fig) / $fig);
            }
        }

        if ($number > 0 && $number < 1000) {
            // 1 - 999
            $n_format = roundDown($number, $precision);
            $suffix = '';
        } else if ($number >= 1000 && $number < 1000000) {
            // 1k-999k
            $n_format = roundDown($number / 1000, $precision);
            $suffix = 'shorten_k_plus';
        } else if ($number >= 1000000 && $number < 1000000000) {
            // 1m-999m
            $n_format = roundDown($number / 1000000, $precision);
            $suffix = 'shorten_m_plus';
        } else if ($number >= 1000000000 && $number < 1000000000000) {
            // 1b-999b
            $n_format = roundDown($number / 1000000000, $precision);
            $suffix = 'shorten_b_plus';
        } else if ($number >= 1000000000000) {
            // 1t+
            $n_format = roundDown($number / 1000000000000, $precision);
            $suffix = 'shorten_t_plus';
        }

        if(!empty($suffix)){
            $suffix =  _p($suffix);
        }

        return !empty($n_format . $suffix) ? $n_format . $suffix : 0;
    }

    /**
     * @param string $class
     * @param string $asc
     * @param string $desc
     * @param string $sorting
     * @param string $query
     * @param string $first
     *
     * @return string
     */
    public function tableSort($class, $asc, $desc, $sorting, $query, $first)
    {
        $sorting = strtolower(empty($sorting) ? Phpfox::getLib('search')->getSort() : $sorting);
        $tableSort = [
            'first'   => strtolower($first ? $first : 'asc'),
            'asc'     => $asc,
            'desc'    => $desc,
            'query'   => $query,
            'sorting' => $sorting,
        ];

        if (strtolower($asc) == $sorting) {
            $status = 'asc';
        } elseif (strtolower($desc) == $sorting) {
            $status = 'desc';
        } else {
            $status = '';
        }

        return ' data-cmd="core.table_sort" class="' . $class . ' sortable ' . $status . '" data-table_sort="' . implode('|',
                $tableSort) . '"';
    }

    /**
     *
     * @param $number
     * @return string
     */
    public function shortNumberOver100($number)
    {
        return intval($number) > 99 ? '99+' : $number;
    }
}
