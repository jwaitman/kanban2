<template>
  <div>
    <h1 class="text-3xl font-bold">{{ board.name }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
      <Column v-for="column in columns" :key="column.id" :column="column" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import Column from '../components/Column.vue'

const route = useRoute()
const board = ref({})
const columns = ref([])

onMounted(async () => {
  const boardId = route.params.id
  try {
    const boardResponse = await axios.get(`/api/v1/boards/${boardId}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    board.value = boardResponse.data

    const columnsResponse = await axios.get(`/api/v1/boards/${boardId}/columns`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    columns.value = columnsResponse.data
  } catch (error) {
    console.error('Failed to fetch board details:', error)
  }
})
</script>
