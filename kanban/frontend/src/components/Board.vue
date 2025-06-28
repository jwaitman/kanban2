<template>
  <div class="p-4 border rounded-lg shadow-sm">
    <h2 class="text-xl font-bold">{{ board.name }}</h2>
    <p>{{ board.description }}</p>
    <router-link :to="{ name: 'Board', params: { id: board.id } }" class="text-indigo-600 hover:text-indigo-900">View Board</router-link>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  board: Object
})
</script>
