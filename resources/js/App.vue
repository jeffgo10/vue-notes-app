<template>
  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">My Notes</h1>

    <!-- Create Note Form -->
    <form @submit.prevent="submitNote" class="bg-white p-6 rounded-lg shadow-md mb-8">
      <div class="mb-4">
        <label class="block text-gray-700 font-bold mb-2">Title</label>
        <input v-model="form.title" type="text" class="w-full border border-gray-300 p-2 rounded" required>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 font-bold mb-2">Content</label>
        <textarea v-model="form.content" class="w-full border border-gray-300 p-2 rounded" rows="3" required></textarea>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 font-bold mb-2">Attachment</label>
        <input type="file" ref="fileInput" @change="handleFileUpload" class="w-full border border-gray-300 p-2 rounded">
      </div>
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 cursor-pointer" :disabled="isLoading">
        {{ isLoading ? 'Saving...' : 'Save Note' }}
      </button>
    </form>

    <!-- Notes List -->
    <div v-if="isLoading && notes.length === 0" class="text-gray-500">Loading notes...</div>

    <div class="grid gap-6">
      <div v-for="note in notes" :key="note.id" class="bg-white p-6 rounded-lg shadow-md relative">
        <h2 class="text-xl font-bold text-gray-800">{{ note.title }}</h2>
        <p class="text-gray-600 mt-2 whitespace-pre-wrap">{{ note.content }}</p>

        <div v-if="note.attachment" class="mt-4">
          <a :href="`/storage/${note.attachment}`" target="_blank" class="text-blue-500 hover:underline text-sm">
            📎 View Attachment
          </a>
        </div>

        <button @click="removeNote(note.id)" class="absolute top-4 right-4 text-red-500 hover:text-red-700 cursor-pointer">
          Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useNoteStore } from './stores/useNoteStore';

const noteStore = useNoteStore();
const { notes, isLoading } = storeToRefs(noteStore);

const form = ref({ title: '', content: '', file: null });
const fileInput = ref(null);

const handleFileUpload = (event) => {
  form.value.file = event.target.files[0];
};

const submitNote = async () => {
  const formData = new FormData();
  formData.append('title', form.value.title);
  formData.append('content', form.value.content);
  if (form.value.file) {
    formData.append('attachment', form.value.file);
  }

  await noteStore.saveNote(formData);

  form.value = { title: '', content: '', file: null };
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const removeNote = async (id) => {
  if (confirm('Are you sure you want to delete this note?')) {
    await noteStore.deleteNote(id);
  }
};

onMounted(() => {
  noteStore.fetchNotes();
});
</script>