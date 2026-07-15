<?php

declare(strict_types=1);

namespace Diogo\StcpTelegramBot;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class StcpClient
{
    private const BASE_URL = 'https://stcp.pt';
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
        . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36';

    /** Known fallback for a public line code whose API route ID differs. */
    private const ROUTE_ID_FALLBACKS = [
        'ZC' => '107',
    ];

    /** @var array<string, string>|null */
    private ?array $routeIds = null;

    public function arrivalsAtStop(string $stop): string
    {
        $stop = $this->normaliseStopCode($stop);
        $data = $this->requestJson(self::BASE_URL . '/api/stops/' . rawurlencode($stop) . '/realtime');
        $arrivals = $this->extractList($data, ['arrivals', 'realtime', 'data', 'results']);

        if ($arrivals === []) {
            return 'Sem passagens previstas em tempo real para esta paragem.';
        }

        $results = [];
        foreach ($arrivals as $arrival) {
            if (!is_array($arrival)) {
                continue;
            }

            $line = $this->firstString($arrival, [
                'route_short_name', 'route_long_name', 'route', 'line', 'line_code', 'linha',
            ]);
            $arrivalValue = $this->firstValue($arrival, [
                'arrival_minutes', 'minutes', 'eta', 'arrival_time', 'estimated_arrival', 'tempo',
            ]);
            $arrivalText = $this->formatArrival($arrivalValue);

            if ($line === null || $arrivalText === null) {
                continue;
            }

            $destination = $this->firstString($arrival, [
                'trip_headsign', 'headsign', 'destination', 'destination_name', 'destino',
            ]);

            $entry = 'Linha ' . $line;
            if ($destination !== null && strcasecmp($destination, $line) !== 0) {
                $entry .= ' → ' . $destination;
            }

            $results[] = $entry . PHP_EOL . 'Chegada: ' . $arrivalText;
        }

        return $results === []
            ? 'Sem passagens previstas em tempo real para esta paragem.'
            : implode(PHP_EOL . PHP_EOL, $results);
    }

    /** @return list<string> */
    public function lineInBothDirections(string $line): array
    {
        $line = $this->normaliseLineCode($line);
        $routeId = $this->resolveRouteId($line);
        $directions = [];

        foreach ([0, 1] as $direction) {
            $stops = $this->fetchRouteStops($routeId, $direction);
            if ($stops !== []) {
                $directions[] = $this->formatRouteStops($line, $stops);
            }
        }

        if ($directions === []) {
            throw new RuntimeException('A linha não foi encontrada ou não tem paragens disponíveis.');
        }

        return $directions;
    }

    /** @return list<array<string, mixed>> */
    private function fetchRouteStops(string $routeId, int $direction): array
    {
        $url = self::BASE_URL . '/api/route/' . rawurlencode($routeId) . '/stops/direction?'
            . http_build_query(['direction_id' => $direction]);
        $data = $this->requestJson($url);
        $stops = $this->extractList($data, ['stops', 'data', 'results']);

        return array_values(array_filter(
            $stops,
            static fn (mixed $stop): bool => is_array($stop)
                && (isset($stop['stop_name']) || isset($stop['name']) || isset($stop['nome']))
        ));
    }

    /**
     * @param list<array<string, mixed>> $stops
     */
    private function formatRouteStops(string $line, array $stops): string
    {
        $names = [];
        foreach ($stops as $stop) {
            $name = $this->firstString($stop, ['stop_name', 'name', 'nome']);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        if ($names === []) {
            throw new RuntimeException('A linha não contém paragens válidas.');
        }

        $output = [sprintf('Linha %s: %s – %s', $line, $names[0], $names[array_key_last($names)])];

        foreach ($stops as $stop) {
            $name = $this->firstString($stop, ['stop_name', 'name', 'nome']);
            if ($name === null) {
                continue;
            }

            $code = $this->firstString($stop, ['stop_code', 'stop_id', 'code', 'codigo']);
            $output[] = $code === null ? "• {$name}" : "• {$name} [{$code}]";
        }

        return implode(PHP_EOL, $output);
    }

    private function resolveRouteId(string $line): string
    {
        if ($this->routeIds === null) {
            try {
                $this->routeIds = $this->fetchRouteIds();
            } catch (RuntimeException $exception) {
                error_log('[stcp-bot] Route list request failed: ' . $exception->getMessage());
                $this->routeIds = [];
            }
        }

        return $this->routeIds[$line] ?? self::ROUTE_ID_FALLBACKS[$line] ?? $line;
    }

    /** @return array<string, string> */
    private function fetchRouteIds(): array
    {
        $html = $this->request(self::BASE_URL . '/pt/linhas', 'text/html');
        $routes = $this->parseRouteIds($html);

        if ($routes === []) {
            throw new RuntimeException('A lista de linhas da STCP não contém dados válidos.');
        }

        return $routes;
    }

    /** @return array<string, string> */
    private function parseRouteIds(string $html): array
    {
        $routes = [];
        $pattern = '~<a\b[^>]*href\s*=\s*(["\'])([^"\']*?/linha\?[^"\']*\bline=[^"\']+)\1[^>]*>(.*?)</a>~is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $href = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $labelHtml = preg_replace('/<[^>]+>/', ' ', $match[3]) ?? '';
            $label = html_entity_decode($labelHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $label = strtoupper(trim(preg_replace('/\s+/u', ' ', $label) ?? ''));

            if (preg_match('/^([0-9]{3}|(?:[1-9]|1[0-3])M|ZC)(?:\s|$)/u', $label, $lineMatch) !== 1) {
                continue;
            }

            $query = parse_url($href, PHP_URL_QUERY);
            if (!is_string($query)) {
                continue;
            }

            parse_str($query, $parameters);
            $routeId = strtoupper(trim((string) ($parameters['line'] ?? '')));
            if ($routeId === '' || preg_match('/^[A-Z0-9]{1,10}$/', $routeId) !== 1) {
                continue;
            }

            $routes[$lineMatch[1]] = $routeId;
        }

        return $routes;
    }

    private function normaliseStopCode(string $value): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^[A-Z0-9.]{1,12}$/', $value) !== 1) {
            throw new InvalidArgumentException('Use um identificador de paragem válido, por exemplo FCUP1.');
        }

        return $value;
    }

    private function normaliseLineCode(string $value): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^[A-Z0-9]{1,4}$/', $value) !== 1) {
            throw new InvalidArgumentException('Use um identificador de linha válido, por exemplo 404, 1M ou ZC.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $keys
     * @return list<mixed>
     */
    private function extractList(array $data, array $keys): array
    {
        if (array_is_list($data)) {
            return $data;
        }

        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                if (array_is_list($value)) {
                    return $value;
                }

                foreach ($keys as $nestedKey) {
                    $nested = $value[$nestedKey] ?? null;
                    if (is_array($nested) && array_is_list($nested)) {
                        return $nested;
                    }
                }
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $record
     * @param list<string> $keys
     */
    private function firstString(array $record, array $keys): ?string
    {
        $value = $this->firstValue($record, $keys);
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $record
     * @param list<string> $keys
     */
    private function firstValue(array $record, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $record) && $record[$key] !== null) {
                return $record[$key];
            }
        }

        return null;
    }

    private function formatArrival(mixed $value): ?string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            $minutes = max(0, (int) round((float) $value));

            return match ($minutes) {
                0 => 'a chegar',
                1 => '1 minuto',
                default => "{$minutes} minutos",
            };
        }

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array<string, mixed> */
    private function requestJson(string $url): array
    {
        try {
            $data = json_decode($this->request($url, 'application/json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('A resposta da STCP não pôde ser interpretada.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('A resposta da STCP não contém dados válidos.');
        }

        return $data;
    }

    private function request(string $url, string $accept): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensão cURL do PHP não está disponível.');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Não foi possível iniciar o pedido ao serviço STCP.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: ' . $accept,
                'Accept-Language: pt-PT,pt;q=0.9,en;q=0.5',
                'Cache-Control: no-cache',
            ],
        ]);

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
            error_log(sprintf('[stcp-bot] STCP request failed: HTTP %d %s', $status, $error));
            throw new RuntimeException(
                'Não foi possível obter informação atualizada da STCP. Tente novamente dentro de momentos.'
            );
        }

        return $body;
    }
}
