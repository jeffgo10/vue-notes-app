import { defineStore } from 'pinia';
import axios from 'axios';

export const useNoteStore = defineStore('noteStore', {
  state: () => ({
    notes: [],
    isLoading: false,
  }),

  actions: {
    async fetchNotes() {
      this.isLoading = true;
      try {
        const response = await axios.get('/api/notes');
        this.notes = response.data;
      } catch (error) {
        console.error('Failed to load notes', error);
      } finally {
        this.isLoading = false;
      }
    },

    async updateNote(id, formData) {
      formData.append('_method', 'PUT');

      await axios.post(`/api/notes/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      await this.fetchNotes();
    },

    async saveNote(formData) {
      await axios.post('/api/notes', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      await this.fetchNotes();
    },

    async deleteNote(id) {
      await axios.delete(`/api/notes/${id}`);
      await this.fetchNotes();
    },
  },
});
