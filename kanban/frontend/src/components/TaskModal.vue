<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center" @click.self="$emit('close')">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
      <div class="flex justify-between items-center mb-4">
        <input v-if="isEditing" type="text" v-model="editableTask.title" class="text-2xl font-bold w-full border-b-2 focus:outline-none focus:border-blue-500">
        <h2 v-else class="text-2xl font-bold">{{ editableTask.title }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-800">&times;</button>
      </div>

      <div class="mb-4">
        <h3 class="font-semibold">Description</h3>
        <textarea v-if="isEditing" v-model="editableTask.description" class="w-full border rounded p-2 mt-1" rows="4"></textarea>
        <p v-else class="text-gray-700 mt-1">{{ editableTask.description || 'No description provided.' }}</p>
      </div>

      <div class="mb-4">
          <h3 class="font-semibold">Due Date</h3>
          <input v-if="isEditing" type="date" v-model="editableTask.due_date" class="w-full border rounded p-2 mt-1">
          <p v-else class="text-gray-700 mt-1">{{ editableTask.due_date || 'No due date set.' }}</p>
      </div>

      <div class="flex justify-end gap-4 mt-6">
        <button v-if="!isEditing" @click="isEditing = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
        <button v-if="isEditing" @click="saveTask" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Save</button>
        <button v-if="isEditing" @click="isEditing = false" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Cancel</button>
        <button @click="deleteTask" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
      </div>
       <p v-if="error" class="text-red-500 mt-2 text-right">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import { useAuthStore } from '../store';

const props = defineProps({
  show: Boolean,
  task: Object
});

const emit = defineEmits(['close', 'task-updated', 'task-deleted']);

const authStore = useAuthStore();
const isEditing = ref(false);
const editableTask = ref(null);
const error = ref(null);

watch(() => props.task, (newTask) => {
  if (newTask) {
    // Create a deep copy for editing to avoid mutating the prop directly
    editableTask.value = JSON.parse(JSON.stringify(newTask));
    // Format date for the input field
    if (editableTask.value.due_date) {
        editableTask.value.due_date = editableTask.value.due_date.split('T')[0];
    }
  } else {
    editableTask.value = null;
  }
  isEditing.value = false; // Reset editing state when task changes
  error.value = null;
}, { immediate: true });

const saveTask = async () => {
  error.value = null;
  if (!editableTask.value) return;

  try {
    await authStore.axios.put(`/api/v1/tasks/${editableTask.value.id}`, {
        title: editableTask.value.title,
        description: editableTask.value.description,
        due_date: editableTask.value.due_date
    });
    isEditing.value = false;
    emit('task-updated');
    emit('close');
  } catch (err) {
    error.value = 'Failed to save task.';
    console.error('Failed to save task:', err);
  }
};

const deleteTask = async () => {
    error.value = null;
    if (!editableTask.value || !confirm('Are you sure you want to delete this task?')) return;

    try {
        await authStore.axios.delete(`/api/v1/tasks/${editableTask.value.id}`);
        emit('task-deleted');
        emit('close');
    } catch (err) {
        error.value = 'Failed to delete task.';
        console.error('Failed to delete task:', err);
    }
};

</script>
