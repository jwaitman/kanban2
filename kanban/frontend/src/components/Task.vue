<template>
  <div>
    <div @click="showModal = true" class="bg-white p-3 rounded-md shadow-sm cursor-pointer hover:bg-gray-50">
      <h4 class="font-medium">{{ task.title }}</h4>
      <p v-if="task.due_date" class="text-sm text-gray-500 mt-1">
        Due: {{ new Date(task.due_date).toLocaleDateString() }}
      </p>
    </div>
    <TaskModal 
      :show="showModal" 
      :task="task" 
      @close="showModal = false" 
      @task-updated="handleTaskUpdate" 
      @task-deleted="handleTaskDelete"
    />
  </div>
</template>

<script setup>
import { defineProps, defineEmits, ref } from 'vue'
import TaskModal from './TaskModal.vue'

defineProps({
  task: Object
})

const emit = defineEmits(['refresh-board'])

const showModal = ref(false)

const handleTaskUpdate = () => {
  emit('refresh-board');
}

const handleTaskDelete = () => {
  emit('refresh-board');
}

</script>
