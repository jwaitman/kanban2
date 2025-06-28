<template>
  <div>
    <h1 class="text-3xl font-bold">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
      <Board v-for="board in boards" :key="board.id" :board="board" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Board from '../components/Board.vue'

const boards = ref([])

onMounted(async () => {
  try {
    const response = await axios.get('/api/v1/boards', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    boards.value = response.data
  } catch (error) {
    console.error('Failed to fetch boards:', error)
  }
})
</script>
