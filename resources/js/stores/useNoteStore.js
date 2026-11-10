import { defineStore } from 'pinia';
import axios from 'axios';
import { connectWallet, notarizeHashOnChain } from '../services/web3';

export const useNoteStore = defineStore('noteStore', {
  state: () => ({
    notes: [],
    isLoading: false,
    walletAddress: null,
    notarizingId: null,
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

    async connectUserWallet() {
      const { address } = await connectWallet();
      this.walletAddress = address;
    },

    async notarizeNote(note) {
      this.notarizingId = note.id;
      try {
        const { txHash, address } = await notarizeHashOnChain(note.current_hash);
        this.walletAddress = address;
        await axios.post(`/api/notes/${note.id}/notarize`, {
          tx_hash: txHash,
          wallet_address: address,
        });
        await this.fetchNotes();
      } finally {
        this.notarizingId = null;
      }
    },
  },
});
