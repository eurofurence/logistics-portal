<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: String, default: null },
    error: { type: String, default: null },
    type: { type: String, default: 'text' },
    autocomplete: { type: String, default: null },
    options: { type: Object, default: null },
    required: Boolean,
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ label }}</label>
        <select
            v-if="options"
            :id="id"
            :name="id"
            :value="modelValue ?? ''"
            :required="required"
            :autocomplete="autocomplete"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? id + '-error' : undefined"
            class="portal-input"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option value="">—</option>
            <option v-for="(option, value) in options" :key="value" :value="value">{{ option }}</option>
        </select>
        <input
            v-else
            :id="id"
            :name="id"
            :value="modelValue ?? ''"
            :type="type"
            :autocomplete="autocomplete"
            :required="required"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? id + '-error' : undefined"
            class="portal-input"
            @input="emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" :id="id + '-error'" class="mt-1.5 text-sm text-red-700 dark:text-red-300">{{ error }}</p>
    </div>
</template>
