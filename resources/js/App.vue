<template>
  <div class="max-w-4xl mx-auto p-6">
    <!-- Header + Wallet Connection -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Vuetiful Crypty Notes</h1>

      <button
        @click="handleConnectWallet"
        class="px-4 py-2 rounded font-medium text-sm cursor-pointer transition"
        :class="walletAddress
          ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
          : 'bg-indigo-600 text-white hover:bg-indigo-700'"
      >
        {{ walletAddress ? `Connected: ${shortAddress(walletAddress)}` : '🦊 Connect Wallet' }}
      </button>
    </div>

    <!-- Web3 Error Alert -->
    <div v-if="web3Error" class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
      <span>{{ web3Error }}</span>
      <button @click="web3Error = null" class="text-red-500 font-bold cursor-pointer">✕</button>
    </div>

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
      <div class="flex gap-2">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 cursor-pointer" :disabled="isLoading">
          {{ isLoading ? 'Saving...' : (editingId ? 'Update Note' : 'Save Note') }}
        </button>
        <button v-if="editingId" type="button" @click="cancelEdit" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 cursor-pointer">
          Cancel
        </button>
      </div>
    </form>

    <!-- Notes List -->
    <div v-if="isLoading && notes.length === 0" class="text-gray-500">Loading notes...</div>

    <div class="grid gap-6">
      <div v-for="note in notes" :key="note.id" class="bg-white p-6 rounded-lg shadow-md relative">
        <h2 class="text-xl font-bold text-gray-800">{{ note.title }}</h2>
        <p class="text-gray-600 mt-2 whitespace-pre-wrap">{{ note.content }}</p>

        <div v-if="note.attachment" class="mt-4">
          <button
            v-if="isImageAttachment(note.attachment)"
            type="button"
            @click="openImagePreview(note)"
            class="text-blue-500 hover:underline text-sm cursor-pointer inline-flex items-center gap-1"
          >
            🖼️ Preview Attachment
          </button>
          <a
            v-else
            :href="`/storage/${note.attachment}`"
            target="_blank"
            class="text-blue-500 hover:underline text-sm inline-flex items-center gap-1"
          >
            📎 View Attachment
          </a>
        </div>

                <!-- Blockchain Notarization Footer -->
        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="space-y-1">
            <!-- Status Badges -->
            <div class="flex items-center gap-2">
              <span
                v-if="note.integrity_status === 'verified'"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-semibold bg-emerald-100 text-emerald-800"
              >
                ✅ Verified On-Chain
              </span>

              <span
                v-else-if="note.integrity_status === 'tampered'"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-semibold bg-red-100 text-red-800"
              >
                ⚠️ Tampered! Content modified since notarization
              </span>

              <span
                v-else
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-600"
              >
                ⏳ Not Notarized
              </span>

              <span v-if="note.wallet_address" class="text-gray-500">
                by <code class="bg-gray-100 px-1 py-0.5 rounded">{{ shortAddress(note.wallet_address) }}</code>
              </span>
            </div>

            <!-- Hash & Transaction Metadata -->
            <div class="text-gray-400 font-mono">
              Hash: {{ shortHash(note.current_hash) }}
              <span v-if="note.tx_hash" class="ml-2">
                | Tx: {{ shortHash(note.tx_hash) }}
              </span>
            </div>
          </div>

          <!-- Notarize / Re-Notarize Button -->
          <button
            v-if="note.integrity_status !== 'verified'"
            @click="handleNotarize(note)"
            :disabled="notarizingId === note.id"
            class="bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 disabled:opacity-50 cursor-pointer font-medium"
          >
            {{
              notarizingId === note.id
                ? 'Notarizing...'
                : (note.integrity_status === 'tampered' ? '⛓️ Re-Notarize New Version' : '⛓️ Notarize on Chain')
            }}
          </button>
        </div>

        <div class="absolute top-4 right-4 flex gap-3">
          <button @click="startEdit(note)" class="text-blue-500 hover:text-blue-700 cursor-pointer">
            Edit
          </button>
          <button @click="removeNote(note.id)" class="text-red-500 hover:text-red-700 cursor-pointer">
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Image Attachment Preview Modal -->
    <div
      v-if="previewNote"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
      @click.self="closeImagePreview"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
          <h3 class="font-semibold text-gray-800 truncate">{{ previewNote.title }} — Attachment</h3>
          <div class="flex items-center gap-3">
            <a
              :href="`/storage/${previewNote.attachment}`"
              target="_blank"
              class="text-xs text-blue-600 hover:underline"
            >
              Open in new tab ↗
            </a>
            <button
              type="button"
              @click="closeImagePreview"
              class="text-gray-500 hover:text-gray-800 font-bold text-lg leading-none cursor-pointer"
              aria-label="Close preview"
            >
              ✕
            </button>
          </div>
        </div>
        <div class="p-4 bg-gray-50 flex items-center justify-center max-h-[75vh] overflow-auto">
          <img
            :src="`/storage/${previewNote.attachment}`"
            :alt="`${previewNote.title} attachment`"
            class="max-w-full max-h-[70vh] object-contain rounded"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useNoteStore } from './stores/useNoteStore';

const noteStore = useNoteStore();
const { notes, isLoading, walletAddress, notarizingId } = storeToRefs(noteStore);
const web3Error = ref(null);
const editingId = ref(null);
const previewNote = ref(null);

const form = ref({ title: '', content: '', file: null });
const fileInput = ref(null);

const IMAGE_EXTENSIONS = /\.(jpe?g|png|gif|webp|svg|bmp|avif|ico)$/i;

const isImageAttachment = (path) => {
  if (!path) return false;
  const cleanPath = path.split('?')[0].split('#')[0];
  return IMAGE_EXTENSIONS.test(cleanPath);
};

const openImagePreview = (note) => {
  previewNote.value = note;
};

const closeImagePreview = () => {
  previewNote.value = null;
};

const handleKeydown = (event) => {
  if (event.key === 'Escape' && previewNote.value) {
    closeImagePreview();
  }
};

const shortAddress = (addr) => (addr ? `${addr.slice(0, 6)}...${addr.slice(-4)}` : '');
const shortHash = (hash) => (hash ? `${hash.slice(0, 10)}...${hash.slice(-6)}` : '');

const handleConnectWallet = async () => {
  web3Error.value = null;
  try {
    await noteStore.connectUserWallet();
  } catch (err) {
    web3Error.value = err.shortMessage || err.message || 'Failed to connect wallet.';
  }
};

const handleNotarize = async (note) => {
  web3Error.value = null;
  try {
    await noteStore.notarizeNote(note);
  } catch (err) {
    web3Error.value = err.response?.data?.message || err.shortMessage || err.message || 'Notarization failed.';
  }
};

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

  if (editingId.value) {
    await noteStore.updateNote(editingId.value, formData);
  } else {
    await noteStore.saveNote(formData);
  }
  cancelEdit();
};

const startEdit = (note) => {
  editingId.value = note.id;
  form.value = {
    title: note.title,
    content: note.content,
    file: null,
  };
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
  editingId.value = null;
  form.value = { title: '', content: '', file: null };
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const removeNote = async (id) => {
  if (confirm('Are you sure you want to delete this note?')) {
    if (previewNote.value?.id === id) {
      closeImagePreview();
    }
    await noteStore.deleteNote(id);
  }
};

onMounted(() => {
  noteStore.fetchNotes();
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>