<x-filament::widget>
    <div class="flex gap-4">

        <button
            wire:click="turnOn"
            class="px-4 py-2 bg-green-500 text-white rounded"
        >
            ON
        </button>

        <button
            wire:click="turnOff"
            class="px-4 py-2 bg-red-500 text-white rounded"
        >
            OFF
        </button>

    </div>
</x-filament::widget>
