<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class PipioService
{
    protected $client;
    protected $apiKey;
    protected $videoUrl;
    protected $assetsUrl;
    protected $cacheDuration = 86400; // 24 hours in seconds

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('PIPIO_API_KEY');
        $this->videoUrl = env('PIPIO_VIDEO_URL');
        $this->assetsUrl = env('PIPIO_ASSETS_URL');
    }

    /**
     * Get list of available avatars
     * @return array|null
     */
    public function getAvatars()
    {
        return Cache::remember('pipio_avatars_raw', $this->cacheDuration, function () {
            try {
                $response = $this->client->request('GET', "{$this->assetsUrl}/actor?page=0&pageSize=33", [
                // $response = $this->client->request('GET', "{$this->assetsUrl}/actor", [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => $this->apiKey
                    ]
                ]);

                return json_decode($response->getBody(), true);
            } catch (RequestException $e) {
                Log::error('Error fetching avatars: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get list of available voices
     * @return array|null
     */
    public function getVoices()
    {
        return Cache::remember('pipio_voices_raw', $this->cacheDuration, function () {
            try {
                $response = $this->client->request('GET', "{$this->assetsUrl}/voice", [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => $this->apiKey
                    ]
                ]);

                return json_decode($response->getBody(), true);
            } catch (RequestException $e) {
                Log::error('Error fetching voices: ' . $e->getMessage());
                return null;
            }
        });
    }

    // /**
    //  * Generate a video with specified avatar and voice
    //  * @param string $actorId
    //  * @param string $voiceId
    //  * @param string $script
    //  * @return array|null
    //  */
    // public function generateVideo($actorId, $voiceId, $script)
    // {
    //     try {
    //         $response = $this->client->request('POST', $this->videoUrl, [
    //             'headers' => [
    //                 'Authorization' => $this->apiKey,
    //                 'Content-Type' => 'application/json'
    //             ],
    //             'json' => [
    //                 'actorId' => $actorId,
    //                 'voiceId' => $voiceId,
    //                 'script' => "Welcome to Pipio API. Lets create amazing avatar videos together"
    //                 // 'script' => $script
    //             ]
    //         ]);
    //         dd($response);

    //         return json_decode($response->getBody(), true);
    //     } catch (RequestException $e) {
    //         Log::error('Error generating video: ' . $e->getMessage());
    //         return null;
    //     }
    // }
    /**
     * Check if a video generation is already in progress
     * @return bool
     */
    private function isVideoGenerationInProgress()
    {
        return Cache::has('pipio_video_generation_in_progress');
    }

    /**
     * Lock video generation to prevent concurrent requests
     * @return bool
     */
    private function lockVideoGeneration()
    {
        return Cache::add('pipio_video_generation_in_progress', true, now()->addMinutes(10));
    }

    /**
     * Release the video generation lock
     */
    private function releaseVideoGenerationLock()
    {
        Cache::forget('pipio_video_generation_in_progress');
    }

    /**
     * Generate a video with specified avatar and voice, ensuring only one request at a time
     * @param string $actorId
     * @param string $voiceId 
     * @param string $script
     * @return array|null
     */
    public function generateVideo($actorId, $voiceId, $script)
    {
        if ($this->isVideoGenerationInProgress()) {
            Log::warning('Video generation already in progress. Please wait for the current request to complete.');
            throw new \Exception('A video is currently being generated. Please try again in a few minutes.');
        }
        // $this->releaseVideoGenerationLock();

        try {
            // Lock video generation
            if (!$this->lockVideoGeneration()) {
                throw new \Exception('Could not acquire video generation lock');
            }

            $response = $this->client->request('POST', $this->videoUrl, [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'actorId' => $actorId,
                    'voiceId' => $voiceId,
                    'script' => $script
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            Log::error('Error generating video: ' . $e->getMessage());
            return null;
        } finally {
            // Always release the lock when done
            $this->releaseVideoGenerationLock();
        }
    }

    /**
     * Check video generation status
     * @param string $videoId
     * @return array|null
     */
    public function checkVideoStatus($videoId)
    {
        if (empty($videoId)) {
            Log::error('Video ID is empty or null');
            return null;
        }

        try {
            
            $response = $this->client->request('GET', "{$this->videoUrl}/{$videoId}", [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $this->apiKey
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info("Video status response:", ['response' => $result]);
            
            return $result;
        } catch (RequestException $e) {
            Log::error('Error checking video status: ' . $e->getMessage(), [
                'videoId' => $videoId,
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
                'url' => "{$this->videoUrl}/{$videoId}"
            ]);
            return null;
        }
    }

    /**
     * Download generated video
     * @param string $videoUrl
     * @param string $outputPath
     * @return bool
     */
    public function downloadVideo($videoUrl, $outputPath)
    {
        try {
            $response = $this->client->request('GET', $videoUrl, [
                'sink' => $outputPath
            ]);

            return true;
        } catch (RequestException $e) {
            Log::error('Error downloading video: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear the cached Pipio data
     */
    public function clearCache()
    {
        Cache::forget('pipio_avatars_raw');
        Cache::forget('pipio_voices_raw');
    }
}
