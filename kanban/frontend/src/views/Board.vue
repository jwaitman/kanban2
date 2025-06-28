<template>
  <div>
    <router-link to="/" class="text-blue-500 hover:underline mb-4 inline-block">&larr; Back to Dashboard</router-link>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold">{{ board.name }}</h1>
        <button @click="logout" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
          Logout
        </button>
    </div>
    <p v-if="board.description" class="mb-4 text-gray-600">{{ board.description }}</p>

    <div class="mb-4">
      <form @submit.prevent="createColumn" class="flex gap-2">
        <input type="text" v-model="newColumnName" placeholder="New column name" class="border p-2 rounded w-full md:w-1/3" required>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Column</button>
      </form>
      <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>
    </div>

    <div class="flex overflow-x-auto space-x-4 p-4 bg-gray-100 rounded-lg">
        <Column v-for="column in columns" :key="column.id" :column="column" @refresh-board="fetchBoardDetails"/>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../store'
import Column from '../components/Column.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const board = ref({})
const columns = ref([])
const newColumnName = ref('')
const error = ref(null)

const boardId = computed(() => route.params.id)

const fetchBoardDetails = async () => {
  error.value = null
  try {
    // Fetch board details
    const boardResponse = await authStore.axios.get(`/api/v1/boards/${boardId.value}`)
    board.value = boardResponse.data

    // Fetch columns for the board
    const columnsResponse = await authStore.axios.get(`/api/v1/columns?board_id=${boardId.value}`)
    columns.value = columnsResponse.data

  } catch (err) {
    error.value = 'Failed to fetch board details.'
    console.error('Failed to fetch board details:', err)
    if (err.response && err.response.status === 404) {
        router.push('/') // Redirect to dashboard if board not found
    }
  }
}

const createColumn = async () => {
  error.value = null
  if (!newColumnName.value.trim()) {
    error.value = 'Column name is required.'
    return
  }
  try {
    await authStore.axios.post('/api/v1/columns', { 
      name: newColumnName.value, 
      board_id: boardId.value 
    })
    newColumnName.value = ''
    await fetchBoardDetails() // Refresh the board
  } catch (err) {
    error.value = 'Failed to create column.'
    console.error('Failed to create column:', err)
  }
}

const logout = () => {
  authStore.logout()
  router.push('/login')
}

onMounted(fetchBoardDetails)
</script>
