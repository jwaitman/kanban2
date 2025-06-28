<template>
  <div class="bg-gray-100 p-4 rounded-lg">
    <h3 class="font-bold">{{ column.name }}</h3>
    <div class="mt-4 space-y-4">
      <Task v-for="task in tasks" :key="task.id" :task="task" />
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, onMounted } from 'vue'
import axios from 'axios'
import Task from './Task.vue'

const props = defineProps({
  column: Object
})

const tasks = ref([])

onMounted(async () => {
  try {
    const response = await axios.get(`/api/v1/columns/${props.column.id}/tasks`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    tasks.value = response.data
  } catch (error) {
    console.error('Failed to fetch tasks:', error)
  }
})
</script>
