<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-3xl font-bold">Dashboard</h1>
      <button @click="logout" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
        Logout
      </button>
    </div>

    <div class="mb-4">
      <form @submit.prevent="createBoard" class="flex gap-2">
        <input type="text" v-model="newBoardName" placeholder="New board name" class="border p-2 rounded w-full" required>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Board</button>
      </form>
      <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
      <Board v-for="board in boards" :key="board.id" :board="board" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store'
import Board from '../components/Board.vue'

const authStore = useAuthStore()
const router = useRouter()

const boards = ref([])
const newBoardName = ref('')
const error = ref(null)

const fetchBoards = async () => {
  error.value = null
  try {
    const response = await authStore.axios.get('/api/v1/boards')
    boards.value = response.data
  } catch (err) {
    error.value = 'Failed to fetch boards.'
    console.error('Failed to fetch boards:', err)
  }
}

const createBoard = async () => {
  error.value = null
  if (!newBoardName.value.trim()) {
    error.value = 'Board name is required.';
    return;
  }
  try {
    await authStore.axios.post('/api/v1/boards', { name: newBoardName.value })
    newBoardName.value = ''
    await fetchBoards() // Refresh the list of boards
  } catch (err) {
    error.value = 'Failed to create board.'
    console.error('Failed to create board:', err)
  }
}

const logout = () => {
  authStore.logout()
  router.push('/login')
}

onMounted(fetchBoards)
</script>
