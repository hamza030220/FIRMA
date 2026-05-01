<?php

namespace App\Service\Event;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Server-side geocoding via OpenStreetMap Nominatim.
 *
 * Nominatim usage policy:
 *   - max 1 request per second
 *   - require a meaningful User-Agent
 *
 * Results are cached in the Symfony app cache (default: file).
 */
class GeocodingService
{
    private const NOMINATIM_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const PHOTON_ENDPOINT = 'https://photon.komoot.io/api/';
    private const USER_AGENT = 'FIRMA-AgriPlatform/1.0';
    private const CACHE_TTL = 2592000; // 30 days

    private HttpClientInterface $http;
    private LoggerInterface $logger;

    public function __construct(
        private readonly CacheInterface $cache,
        ?HttpClientInterface $http = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->http = $http ?? HttpClient::create([
            'headers' => ['User-Agent' => self::USER_AGENT],
            'timeout' => 8.0,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Geocode a free-form address. Returns ['lat' => float, 'lng' => float] or null.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address, string $countryCode = 'tn'): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $cacheKey = 'geocode_' . md5(strtolower($countryCode . '|' . $address));

        /** @var array{lat: float, lng: float}|null $result */
        $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($address, $countryCode): ?array {
            $hit = $this->fetch($address, $countryCode);
            if ($hit === null) {
                // Don't keep negative results long: avoid poisoning cache for 30 days.
                $item->expiresAfter(60);
            } else {
                $item->expiresAfter(self::CACHE_TTL);
            }
            return $hit;
        });

        return $result;
    }

    /**
     * Try multiple candidate query strings in order, return first hit (or null).
     *
     * @param list<string> $candidates
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeBest(array $candidates, string $countryCode = 'tn'): ?array
    {
        $seen = [];
        foreach ($candidates as $q) {
            $q = trim($q);
            if ($q === '' || isset($seen[$q])) {
                continue;
            }
            $seen[$q] = true;
            $hit = $this->geocode($q, $countryCode);
            if ($hit !== null) {
                return $hit;
            }
        }
        return null;
    }

    /**
     * Build progressive geocoding candidates from address + venue name.
     * From most specific to most generic.
     *
     * @return list<string>
     */
    public function buildCandidates(?string $adresse, ?string $lieu): array
    {
        $adresse = trim((string) $adresse);
        $lieu    = trim((string) $lieu);

        $cands = [];

        if ($adresse !== '' && $lieu !== '') {
            $cands[] = $adresse . ', ' . $lieu;
        }
        if ($adresse !== '') {
            $cands[] = $adresse;
            // Strip leading "Route de X km Y, " or "BP 1234, " — keep last segments
            // e.g. "Route de La Marsa, Le Kram, 2015 Tunis" → "Le Kram, 2015 Tunis" → "2015 Tunis"
            $parts = array_map('trim', explode(',', $adresse));
            if (count($parts) > 1) {
                $cands[] = implode(', ', array_slice($parts, -2));
                $cands[] = end($parts) ?: '';
            }
            // Extract postal code + city pattern (e.g. "2015 Tunis", "8050 Hammamet")
            if (preg_match('/\b(\d{4})\s+([A-Za-zÀ-ÿ\'\- ]{2,40})/u', $adresse, $m)) {
                $cands[] = trim($m[1] . ' ' . $m[2]);
                $cands[] = trim($m[2]);
            }
        }
        if ($lieu !== '') {
            $cands[] = $lieu;
        }

        return array_values(array_filter($cands, static fn(string $s): bool => $s !== ''));
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function fetch(string $address, string $countryCode): ?array
    {
        // Try Photon first (komoot's OSM-backed geocoder, no API key, lenient rate-limit)
        $hit = $this->fetchPhoton($address, $countryCode);
        if ($hit !== null) {
            return $hit;
        }
        // Fallback to Nominatim (1 req/s policy, may rate-limit aggressively)
        return $this->fetchNominatim($address, $countryCode);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function fetchPhoton(string $address, string $countryCode): ?array
    {
        try {
            $response = $this->http->request('GET', self::PHOTON_ENDPOINT, [
                'query' => [
                    'q'     => $address,
                    'limit' => 5,
                    'lang'  => 'en',
                ],
                'headers' => ['User-Agent' => self::USER_AGENT],
            ]);

            $status = $response->getStatusCode();
            if ($status !== 200) {
                $this->logger->warning('Photon non-200', ['address' => $address, 'status' => $status]);
                return null;
            }

            /** @var array{features?: list<array{geometry?: array{coordinates?: array<int, float>}, properties?: array<string, mixed>}>} $data */
            $data = $response->toArray(false);
            $features = $data['features'] ?? [];
            if ($features === []) {
                return null;
            }

            // If countrycode requested, ONLY accept features matching that country.
            // Returning a wrong-country result is worse than returning null.
            if ($countryCode !== '') {
                $cc = strtoupper($countryCode);
                foreach ($features as $f) {
                    $props = $f['properties'] ?? [];
                    if (isset($props['countrycode']) && strtoupper((string) $props['countrycode']) === $cc) {
                        $coords = $f['geometry']['coordinates'] ?? null;
                        if (is_array($coords) && isset($coords[0], $coords[1])) {
                            return ['lat' => (float) $coords[1], 'lng' => (float) $coords[0]];
                        }
                    }
                }
                return null;
            }

            $coords = $features[0]['geometry']['coordinates'] ?? null;
            if (!is_array($coords) || !isset($coords[0], $coords[1])) {
                return null;
            }
            return ['lat' => (float) $coords[1], 'lng' => (float) $coords[0]];
        } catch (\Throwable $e) {
            $this->logger->warning('Photon geocoding failed', [
                'address' => $address,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function fetchNominatim(string $address, string $countryCode): ?array
    {
        try {
            $response = $this->http->request('GET', self::NOMINATIM_ENDPOINT, [
                'query' => [
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => $countryCode,
                    'q'            => $address,
                ],
                'headers' => ['User-Agent' => self::USER_AGENT],
            ]);

            $status = $response->getStatusCode();
            if ($status !== 200) {
                $this->logger->warning('Nominatim non-200 response', [
                    'address' => $address,
                    'status'  => $status,
                ]);
                return null;
            }

            /** @var list<array<string, mixed>> $data */
            $data = $response->toArray(false);

            if ($data === [] || !isset($data[0]['lat'], $data[0]['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon'],
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Nominatim geocoding failed', [
                'address' => $address,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }
}
