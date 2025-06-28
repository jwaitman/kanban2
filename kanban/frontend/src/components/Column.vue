<template>
  <div class="bg-gray-200 p-3 rounded-lg w-80 flex-shrink-0">
    <h3 class="font-bold text-lg mb-3">{{ column.name }}</h3>

    <!-- Tasks -->
    <div class="space-y-3">
      <Task v-for="task in column.tasks" :key="task.id" :task="task" @refresh-board="$emit('refresh-board')" />
    </div>

    <!-- Add Task Form -->
    <div class="mt-4">
      <form @submit.prevent="createTask">
        <input 
          type="text" 
          v-model="newTaskTitle" 
          placeholder="+ Add a card"
          class="bg-gray-200 hover:bg-white focus:bg-white p-2 rounded w-full text-sm focus:outline-none"
        />
        <div v-if="newTaskTitle" class="mt-2 flex items-center gap-2">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">Add Card</button>
            <button @click="newTaskTitle = ''" type="button" class="text-gray-500 hover:text-gray-800">Cancel</button>
        </div>
      </form>
      <p v-if="error" class="text-red-500 mt-2 text-sm">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, ref } from 'vue'
import { useAuthStore } from '../store'
import Task from './Task.vue'

const props = defineProps({
  column: Object
})

const emit = defineEmits(['refresh-board'])

const authStore = useAuthStore()
const newTaskTitle = ref('')
const error = ref(null)

const createTask = async () => {
  error.value = null
  if (!newTaskTitle.value.trim()) return;

  try {
    await authStore.axios.post('/api/v1/tasks', {
      title: newTaskTitle.value,
      column_id: props.column.id
    })
    newTaskTitle.value = ''
    emit('refresh-board') // Notify parent to refresh
  } catch (err) {
    error.value = 'Failed to create task.'
    console.error('Failed to create task:', err)
  }
}

</script>
