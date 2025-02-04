<?php

namespace App\Http\Livewire;

use App\Services\PipioService;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CourseVideo extends Component
{
    public $lesson;
    public $avatars = [];
    public $voices = [];
    public $selectedVoice = null;
    public $selectedAvatar = null;
    public $content = '';
    public $videoUrl = null;

    protected $pipioService;
    protected $cacheDuration = 86400; // 24 hours in seconds

    public function mount($lesson)
    {
        // Cache::forget('pipio_avatars_raw');
        // Cache::forget('pipio_voices_raw');
        $this->lesson = $lesson;
        $this->videoUrl = $this->lesson->video_url ?? '';
        $this->content = strip_tags($this->lesson->content ?? '');
        $this->pipioService = app(PipioService::class);
        $this->loadAvatarsAndVoices();
    }

    protected function loadAvatarsAndVoices()
    {
        $this->avatars = Cache::remember('pipio_avatars_raw', $this->cacheDuration, function () {
            $avatarResponse = $this->pipioService->getAvatars();
            if (!$avatarResponse) {
                return [];
            }

            return array_map(function ($avatar) {
                return [
                    'id' => $avatar['id'],
                    'name' => $avatar['name'],
                    'image_url' => $avatar['thumbnailImagePath'],
                    'gender' => $avatar['gender'],
                    'type' => $avatar['actorType']
                ];
            }, $avatarResponse['items']);
        });

        $this->voices = Cache::remember('pipio_voices_raw', $this->cacheDuration, function () {
            $voiceResponse = $this->pipioService->getVoices();
            if (!$voiceResponse) {
                return [];
            }

            return array_map(function ($voice) {
                return [
                    'id' => $voice['id'],
                    'name' => $voice['name'],
                    'language' => implode(', ', $voice['languages']),
                    'gender' => $voice['gender'],
                    'type' => $voice['voiceType'],
                    'preview_url' => $voice['previewAudioPath']
                ];
            }, $voiceResponse['items']);
        });
    }

    public function refreshPipioData()
    {
        Cache::forget('pipio_avatars_raw');
        Cache::forget('pipio_voices_raw');
        $this->loadAvatarsAndVoices();
        $this->emit('pipioDataRefreshed');
    }

    public function selectVoice($voiceId)
    {
        $this->selectedVoice = $voiceId;
    }

    public function selectAvatar($avatarId)
    {
        $this->selectedAvatar = $avatarId;
    }

    public function previewVoice($voiceId)
    {
        $voice = collect($this->voices)->firstWhere('id', $voiceId);
        if ($voice && isset($voice['preview_url'])) {
            $this->dispatchBrowserEvent('play-audio', ['url' => $voice['preview_url']]);
        }
    }

    public function generateVideo()
    {
        if (!$this->selectedAvatar || !$this->selectedVoice || !$this->content) {
            $this->addError('video', 'Please select both an avatar and voice before generating the video');
            return;
        }

        $this->pipioService = app(PipioService::class);

        try {

            $response = $this->pipioService->generateVideo(
                $this->selectedAvatar,
                $this->selectedVoice,
                $this->content
            );

            // if ($response) {
            $this->lesson->update([
                'pipio_video_id' => $response['id'],
                'pipio_status' => 'processing'
            ]);

            $this->emit('videoGenerationStarted-'. $this->lesson->id);
            // }
        } catch (\Exception $e) {
            $this->addError('video', 'Failed to generate video: ' . $e->getMessage());
        }
    }

    public function checkVideoStatus()
    {
        if (!$this->lesson->pipio_video_id) {
            return;
        }

        $this->pipioService = app(PipioService::class);
        try {
            $status = $this->pipioService->checkVideoStatus($this->lesson->pipio_video_id);

            if ($status && isset($status['status'])) {
                $this->lesson->update([
                    'pipio_status' => $status['status']
                ]);

                if ($status['status'] === 'Failure') {
                    $this->emit('video-generation-complete-' . $this->lesson->id, [
                        'videoUrl' => ''
                    ]);
                    $this->addError('video', 'Video Generation Failed');
                    return;
                }

                if ($status['status'] === 'Completed' && isset($status['videoUrl'])) {
                    $this->lesson->update([
                        'video_url' => $status['videoUrl'],
                        'is_pipio_processed' => true
                    ]);

                    $this->videoUrl = $status['videoUrl'];

                    if ($this->lesson->video_url && !empty($this->lesson->video_url)) {
                        $this->emit('video-generation-complete-' . $this->lesson->id, [
                            'videoUrl' => $this->lesson->video_url
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addError('video', 'Failed to check video status: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.course-video');
    }
}
