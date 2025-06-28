<template>
  <div class="bg-white p-4 rounded-lg shadow-sm">
    <h4 class="font-bold">{{ task.title }}</h4>
    <p>{{ task.description }}</p>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  task: Object
})
</script>
