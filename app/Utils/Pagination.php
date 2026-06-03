<?php

namespace App\Utils;

class Pagination
{
    private $total;
    private $limit;
    private $pages;
    private $currentPage;
    private $url;

    public function __construct($total, $currentPage = 1, $limit = 10, $url = '')
    {
        $this->total = $total;
        $this->limit = $limit;
        $this->currentPage = (is_numeric($currentPage) && $currentPage > 0) ? (int)$currentPage : 1;
        $this->url = $url;
        $this->calculate();
    }

    private function calculate()
    {
        $this->pages = $this->total > 0 ? ceil($this->total / $this->limit) : 1;
        $this->currentPage = $this->currentPage <= $this->pages ? $this->currentPage : $this->pages;
    }

    public function getLimit()
    {
        $offset = ($this->limit * ($this->currentPage - 1));
        return $offset . ',' . $this->limit;
    }

    public function render()
    {
        if ($this->pages <= 1) return '';

        $links = '<ul class="pagination pagination-rounded mb-0">';

        // Prev
        if ($this->currentPage > 1) {
            $links .= '<li class="page-item"><a class="page-link" href="' . $this->getUrl($this->currentPage - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
        } else {
            $links .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
        }

        // Pages
        for ($i = 1; $i <= $this->pages; $i++) {
            $active = ($i == $this->currentPage) ? 'active' : '';
            $links .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $this->getUrl($i) . '">' . $i . '</a></li>';
        }

        // Next
        if ($this->currentPage < $this->pages) {
            $links .= '<li class="page-item"><a class="page-link" href="' . $this->getUrl($this->currentPage + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
        } else {
            $links .= '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
        }

        $links .= '</ul>';

        return $links;
    }

    private function getUrl($page)
    {
        $url = $this->url;
        
        // Ensure we don't duplicate query params if they already exist
        $parsedUrl = parse_url($url);
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        $queryParams['page'] = $page;
        $newQuery = http_build_query($queryParams);
        
        $basePath = $parsedUrl['path'] ?? '';
        return $basePath . '?' . $newQuery;
    }
}
