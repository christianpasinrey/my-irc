<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold text-gray-900">Editar Servidor IRC</h1>
          <Link
            :href="route('irc-servers.index')"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
          >
            Volver
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
          <div class="px-6 py-4">
            <form @submit.prevent="submitForm">
              <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                  Nombre del Servidor (Opcional)
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  placeholder="ej: Chat Hispano"
                />
                <div v-if="errors.name" class="mt-1 text-sm text-red-600">
                  {{ errors.name }}
                </div>
              </div>

              <div class="mb-4">
                <label for="host" class="block text-sm font-medium text-gray-700 mb-2">
                  Host del Servidor <span class="text-red-500">*</span>
                </label>
                <input
                  id="host"
                  v-model="form.host"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  placeholder="ej: irc.freenode.net"
                />
                <div v-if="errors.host" class="mt-1 text-sm text-red-600">
                  {{ errors.host }}
                </div>
              </div>

              <div class="mb-6">
                <label for="port" class="block text-sm font-medium text-gray-700 mb-2">
                  Puerto <span class="text-red-500">*</span>
                </label>
                <input
                  id="port"
                  v-model.number="form.port"
                  type="number"
                  required
                  min="1"
                  max="65535"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  placeholder="6667"
                />
                <div v-if="errors.port" class="mt-1 text-sm text-red-600">
                  {{ errors.port }}
                </div>
                <p class="mt-1 text-sm text-gray-500">
                  Puerto típico: 6667 (no encriptado) o 6697 (SSL)
                </p>
              </div>

              <div class="flex items-center justify-between">
                <Link
                  :href="route('irc-servers.index')"
                  class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="processing"
                  class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="processing">Actualizando...</span>
                  <span v-else>Actualizar Servidor</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

interface IRCServer {
  id: number
  name?: string
  host: string
  port: number
}

interface ServerForm {
  name: string
  host: string
  port: number | null
}

interface Props {
  server: IRCServer
}

const props = defineProps<Props>()

const form = reactive<ServerForm>({
  name: '',
  host: '',
  port: null
})

const errors = ref<Record<string, string>>({})
const processing = ref(false)

onMounted(() => {
  // Llenar el formulario con los datos del servidor existente
  form.name = props.server.name || ''
  form.host = props.server.host
  form.port = props.server.port
})

function submitForm() {
  if (processing.value) return
  
  processing.value = true
  errors.value = {}

  router.put(route('irc-servers.update', props.server.id), form, {
    onSuccess: () => {
      // Será redirigido automáticamente por el controlador
    },
    onError: (errorResponse: any) => {
      errors.value = errorResponse
    },
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>
