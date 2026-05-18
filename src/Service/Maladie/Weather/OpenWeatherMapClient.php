<?php

namespace App\Service\Maladie\Weather;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenWeatherMapClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openWeatherApiKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->openWeatherApiKey !== '' && $this->openWeatherApiKey !== 'CHANGE_ME';
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrentWeather(float $latitude, float $longitude): array
    {
        if (!$this->isConfigured()) {
            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'La cle API OpenWeatherMap est absente.');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            return $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Impossible de recuperer la meteo externe.', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrentWeatherByCity(string $city): array
    {
        if (!$this->isConfigured()) {
            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'La cle API OpenWeatherMap est absente.');
        }

        $city = trim($city);
        if ($city === '') {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'La ville de l utilisateur est vide.');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            return $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Impossible de recuperer la meteo de la ville demandee.', $e);
        }
    }
}
