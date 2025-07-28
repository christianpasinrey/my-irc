<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header con título y botón agregar -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-foreground">Servidores IRC</h1>
                <Button
                    as-child
                >
                    <Link :href="route('irc-servers.create')">
                        Agregar Servidor
                    </Link>
                </Button>
            </div>

            <!-- Estado vacío -->
            <div v-if="servers.length === 0" class="flex flex-col items-center justify-center py-12">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mb-4">
                    <svg
                        class="w-8 h-8 text-muted-foreground"
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
                </div>
                <h3 class="text-lg font-semibold text-foreground mb-2">No hay servidores IRC</h3>
                <p class="text-muted-foreground mb-6">Comienza agregando tu primer servidor IRC.</p>
                <Button as-child>
                    <Link :href="route('irc-servers.create')">
                        <svg
                            class="w-4 h-4 mr-2"
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
                </Button>
            </div>

            <!-- Lista de servidores -->
            <div v-else class="space-y-4">
                <Card
                    v-for="server in servers"
                    :key="server.id"
                    class="p-6"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                                <svg
                                    class="w-6 h-6 text-primary"
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
                            <div>
                                <h3 class="font-semibold text-foreground">
                                    {{ server.name || server.host }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ server.host }}:{{ server.port }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="default"
                                size="sm"
                                as-child
                            >
                                <Link :href="route('irc-chat.show', server.id)">
                                    Conectar
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="route('irc-servers.edit', server.id)">
                                    Editar
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteServer(server)"
                            >
                                Eliminar
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import axios from 'axios'
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
    const response = await axios.get(route('irc-servers.list'))
    servers.value = response.data
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
