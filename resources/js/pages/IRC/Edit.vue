<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header con título y botón volver -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-foreground">Editar Servidor IRC</h1>
                <Button 
                    variant="secondary" 
                    as-child
                >
                    <Link :href="route('irc-servers.index')">
                        Volver
                    </Link>
                </Button>
            </div>

            <div class="max-w-md mx-auto w-full">
                <!-- Formulario -->
                <Card class="p-6">
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">
                                Nombre del Servidor (Opcional)
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="ej: Chat Hispano"
                            />
                            <div v-if="errors.name" class="text-sm text-destructive">
                                {{ errors.name }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="host">
                                Host del Servidor <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="host"
                                v-model="form.host"
                                type="text"
                                required
                                placeholder="ej: irc.freenode.net"
                            />
                            <div v-if="errors.host" class="text-sm text-destructive">
                                {{ errors.host }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="port">
                                Puerto <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="port"
                                v-model.number="form.port"
                                type="number"
                                required
                                min="1"
                                max="65535"
                                placeholder="6667"
                            />
                            <div v-if="errors.port" class="text-sm text-destructive">
                                {{ errors.port }}
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Puerto típico: 6667 (no encriptado) o 6697 (SSL)
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <Button 
                                variant="outline" 
                                type="button"
                                as-child
                            >
                                <Link :href="route('irc-servers.index')">
                                    Cancelar
                                </Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="processing"
                            >
                                <span v-if="processing">Actualizando...</span>
                                <span v-else>Actualizar Servidor</span>
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface IRCServer {
  id: number
  name?: string
  host: string
  port: number
}

interface ServerForm {
  name: string
  host: string
  port: number | string
}

interface Props {
  server: IRCServer
}

const props = defineProps<Props>()

const form = reactive<ServerForm>({
  name: '',
  host: '',
  port: ''
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
