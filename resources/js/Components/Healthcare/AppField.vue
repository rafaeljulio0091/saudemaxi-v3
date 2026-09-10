<script setup>
defineOptions({ inheritAttrs: false });
defineProps({
    id: { type: String, required: true },
    label: String,
    type: { type: String, default: 'text' },
    error: String,
    required: Boolean,
    modelValue: [String, Number],
});
defineEmits(['update:modelValue']);
</script>
<template>
    <div class="sm-field">
        <label :for="id">{{ label }}</label>
        <input
            v-bind="$attrs"
            :id="id"
            :type="type"
            :value="modelValue"
            :required="required"
            :aria-invalid="!!error"
            :aria-describedby="error ? id + '-error' : undefined"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" :id="id + '-error'" class="sm-error">{{ error }}</p>
    </div>
</template>
