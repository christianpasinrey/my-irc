<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold text-gray-900">Servidores IRC</h1>
          <Link
            :href="route('irc-servers.create')"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
          >
            Agregar Servidor
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div v-if="servers.length === 0" class="text-center py-12">
          <svg
            class="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No hay servidores IRC</h3>
          <p class="mt-1 text-sm text-gray-500">Comienza agregando tu primer servidor IRC.</p>
          <div class="mt-6">
            <Link
              :href="route('irc-servers.create')"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                class="-ml-1 mr-2 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                  clip-rule="evenodd"
                />
              </svg>
              Agregar Servidor
            </Link>
          </div>
        </div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
          <ul role="list" class="divide-y divide-gray-200">
            <li v-for="server in servers" :key="server.id" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                      <svg
                        class="h-6 w-6 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ server.name || server.host }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ server.host }}:{{ server.port }}
                    </div>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Link
                    :href="route('irc-chat.show', server.id)"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Conectar
                  </Link>
                  <Link
                    :href="route('irc-servers.edit', server.id)"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    Editar
                  </Link>
                  <button
                    @click="deleteServer(server)"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                  >
                    Eliminar
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

interface IRCServer {
  id: number
  name?: string
  host: string
  port: number
  created_at: string
  updated_at: string
}

const servers = ref<IRCServer[]>([])

onMounted(async () => {
  await loadServers()
})

async function loadServers() {
  try {
    const response = await fetch('/irc/api/servers')
    const data = await response.json()
    servers.value = data
  } catch (error) {
    console.error('Error loading servers:', error)
  }
}

function deleteServer(server: IRCServer) {
  if (confirm(`¿Estás seguro de que quieres eliminar el servidor "${server.name || server.host}"?`)) {
    router.delete(route('irc-servers.destroy', server.id), {
      onSuccess: () => {
        loadServers()
      }
    })
  }
}
</script>
