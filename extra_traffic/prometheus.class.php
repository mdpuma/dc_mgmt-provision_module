<?php

class PrometheusClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl, int $timeout = 10)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Execute instant query
     *
     * Example:
     * $client->query('up');
     */
    public function query(string $query, ?int $time = null): array
    {
        $params = [
            'query' => $query,
        ];

        if ($time !== null) {
            $params['time'] = $time;
        }

        return $this->request('/api/v1/query', $params);
    }

    /**
     * Execute range query
     *
     * Example:
     * $client->queryRange(
     *     'rate(node_network_receive_bytes_total[5m])',
     *     strtotime('-1 hour'),
     *     time(),
     *     '30s'
     * );
     */
    public function queryRange(
        string $query,
        int $start,
        int $end,
        string $step = '60s'
    ): array {
        $params = [
            'query' => $query,
            'start' => $start,
            'end'   => $end,
            'step'  => $step,
        ];

        return $this->request('/api/v1/query_range', $params);
    }

    /**
     * Get label values
     */
    public function getLabelValues(string $label): array
    {
        return $this->request("/api/v1/label/{$label}/values");
    }

    /**
     * Execute HTTP request
     */
    private function request(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Prometheus API returned HTTP {$httpCode}");
        }

        $decoded = json_decode($response, true);

        if (!$decoded) {
            throw new Exception('Invalid JSON response');
        }

        if (($decoded['status'] ?? '') !== 'success') {
            throw new Exception('Prometheus query failed');
        }

        return $decoded;
    }
}
