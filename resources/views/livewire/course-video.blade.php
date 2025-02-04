<div x-data="{
    isGenerating: false,
    checkInterval: null,

    init() {
        Livewire.on('videoGenerationStarted-{{ $lesson->id }}', () => {
            this.isGenerating = true;
            // Start polling for status
            this.checkInterval = setInterval(() => {
                @this.checkVideoStatus();
            }, 5000); // Check every 5 seconds
        });

        Livewire.on('video-generation-complete-{{ $lesson->id }}', ({
            videoUrl
        }) => {
            this.isGenerating = false;
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }
        });
    },
    open: false,
    isOpen: false
}" class="">

    <div class="bg-white shadow-md">
        <div class="border px-2  shadow text-gray-500 font-semibold flex items-center justify-between">
            <div class="pl-2 flex flex-grow">
                <form action="{{ route('lesson.update', ['lesson' => $lesson->id]) }}" method="POST" class="w-full">
                    @csrf
                    @method('PUT')
                    <input id="lesson_input_{{ $lesson->id }}" type="text "
                        class="w-[70%]  border-transparent  p-3 placholder-gray-700 placeholder:font-bold placeholder:uppercase "
                        value="{{ $lesson->title }}" name="lesson">
                    <x-main-button type="submit" class="whitespace-nowrap hidden"
                        id="submit_btn_{{ $lesson->id }}">Save</x-main-button>
                </form>
            </div>

            <button @click="open = !open">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
        <div class="relative" x-show="open ? open : childIsOpen">
            <div x-show="isGenerating" style="display: none"
                class="absolute z-20 left-0 top-0 h-full w-full bg-white bg-opacity-90 flex items-center justify-center">

                <div class="">
                    <img src="{{ asset('images/moving_ball.gif') }}" alt="" style="opacity: 0.8;">
                </div>
            </div>
            <div class="p-2 h-96 overflow-y-auto">
                <div x-data="{ tab: 'avatar', selectedAvatar: null, selectedVoice: null }" class="">


                    <div>
                        @error('video')
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <span class="block sm:inline">{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                    <!-- Avatars Section -->
                    <div class="mb-4" x-show="tab === 'avatar'">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-medium mb-3 pb-2 border-b ">Select Avatar</h3>
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="tab = 'voice'"
                                    :disabled="selectedAvatar === null"
                                    :class="{
                                        'bg-blue-500': selectedAvatar !==
                                            null,
                                        'bg-gray-400 cursor-not-allowed': selectedAvatar === null
                                    }"
                                    class="text-white px-4 py-2 rounded-md">Next</button>
                                <button type="button" @click="tab = 'video'"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Response</button>
                            </div>

                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Select Avatar</h3>
                        <div class="grid grid-cols-4 gap-4">
                            <template x-for="avatar in $wire.avatars" :key="avatar.id">
                                <div class="cursor-pointer p-2 rounded-lg transition-all duration-200"
                                    :class="{ 'ring-2 ring-blue-500 bg-blue-50': selectedAvatar === avatar.id }"
                                    @click="selectedAvatar = avatar.id; $wire.selectAvatar(avatar.id)">
                                    <img :src="avatar.image_url ? avatar.image_url : 'https://placehold.co/60x60'"
                                        :alt="avatar.name" class="w-full h-auto rounded-lg">
                                    <p class="text-sm text-center mt-1" x-text="avatar.name"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Voices Section -->
                    <div x-show="tab === 'voice'">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-medium mb-3 pb-2 border-b ">Select Vioce</h3>
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="tab = 'avatar'; $wire.selectAvatar(null)"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Prev</button>


                                <button type="button" @click="tab = 'script'"
                                    :disabled="selectedVoice === null"
                                    :class="{
                                        'bg-blue-500': selectedVoice !==
                                            null,
                                        'bg-gray-400 cursor-not-allowed': selectedVoice === null
                                    }"
                                    class="text-white px-4 py-2 rounded-md">Next</button>
                                <button type="button" @click="tab = 'video'"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Response</button>
                            </div>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Select Voice</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <template x-for="voice in $wire.voices" :key="voice.id">
                                <div class="cursor-pointer p-3 border rounded-lg transition-all duration-200 bg-gray-50"
                                    :class="{ 'ring-2 ring-blue-500 bg-blue-50': selectedVoice === voice.id }"
                                    @click="selectedVoice = voice.id; $wire.selectVoice(voice.id)">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium" x-text="voice.name"></p>
                                            <p class="text-sm text-gray-500" x-text="voice.language"></p>
                                        </div>
                                    </div>
                                    <div x-data="{ isPlaying: false }" class="mt-2">
                                        <button
                                            class="w-full px-3 py-1 text-sm text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            @click.stop="$wire.previewVoice(voice.id)">
                                            <span>Preview</span>
                                        </button>

                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="tab === 'script'">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-medium mb-3 pb-2 border-b ">Enter Script</h3>
                            <div class="flex justify-end space-x-2">

                                <button type="button" @click="tab = 'voice'; $wire.selectVoice(null)"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Prev</button>
                                <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded-md" 
                                    wire:click="generateVideo"
                                    :disabled="!$wire.content?.trim()"
                                    :class="{ 'opacity-50 cursor-not-allowed': !$wire.content?.trim() }">
                                    Generate
                                </button>

                               
                                <button type="button" @click="tab = 'video'"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Preview</button>
                            </div>

                        </div>
                        <textarea wire:model.live="content" id="" cols="30" rows="8"
                            class="border-2 p-3 rounded-lg outline-none ring-0 mx-auto w-[80%] block" placeholder="Add content here..."></textarea>
                    </div>
                    <div x-show="tab === 'video'" class="w-full">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-medium mb-3 pb-2 border-b ">Review</h3>
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="tab = 'script'"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Prev</button>
                                <button type="button" @click="tab = 'video'"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md">Preview</button>

                            </div>
                        </div>
                        @if ($videoUrl)
                            <video width="50%" controls class="mx-auto">
                                <source src="{{ $videoUrl }}" type="video/webm">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <p class="text-center text-gray-500">No video generated yet.</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('lesson_input_{{ $lesson->id }}');
            const button = document.getElementById('submit_btn_{{ $lesson->id }}');
            const originalValue = input.value; // Save the original value of the input

            input.addEventListener('input', () => {
                // Check if the current value differs from the original value
                if (input.value.trim() !== originalValue.trim()) {
                    button.classList.remove('hidden'); // Show the button
                } else {
                    button.classList.add('hidden'); // Hide the button
                }
            });
        });

        window.addEventListener('play-audio', event => {
            const audio = new Audio(event.detail.url);
            audio.play();
        });
    </script>
</div>
