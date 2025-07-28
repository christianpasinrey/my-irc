<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header con título y botón volver -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-foreground">Agregar Servidor IRC</h1>
                <Button 
                    variant="secondary" 
                    as-child
                >
                    <Link :href="route('irc-servers.index')">
                        Volver
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
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
                                <span v-if="processing">Guardando...</span>
                                <span v-else>Guardar Servidor</span>
                            </Button>
                        </div>
                    </form>
                </Card>

                <!-- Ejemplos de servidores populares -->
                <Card class="p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Servidores IRC Populares</h3>
                    <div class="space-y-2">
                        <div
                            v-for="example in examples"
                            :key="example.host"
                            class="group relative rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-background/50 hover:bg-accent/50 cursor-pointer transition-all duration-200 p-4"
                            @click="useExample(example)"
                        >
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-foreground">{{ example.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ example.host }}:{{ example.port }}</p>
                                </div>
                                <span class="text-primary text-sm font-medium group-hover:text-primary/80">
                                    Usar
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface ServerForm {
  name: string
  host: string
  port: number | string
}

interface ServerExample {
  name: string
  host: string
  port: number
}

const form = reactive<ServerForm>({
  name: '',
  host: '',
  port: ''
})

const errors = ref<Record<string, string>>({})
const processing = ref(false)

const examples: ServerExample[] = [
  { name: 'Libera Chat', host: 'irc.libera.chat', port: 6667 },
  { name: 'Libera Chat (SSL)', host: 'irc.libera.chat', port: 6697 },
  { name: 'OFTC', host: 'irc.oftc.net', port: 6667 },
  { name: 'EFNet', host: 'irc.efnet.org', port: 6667 },
  { name: 'IRCnet', host: 'open.ircnet.net', port: 6667 },
  { name: 'QuakeNet', host: 'irc.quakenet.org', port: 6667 },
  { name: 'IRC-Hispano', host: 'irc.irc-hispano.org', port: 6667 },
  { name: 'Rizon', host: 'irc.rizon.net', port: 6667 },
  { name: 'EsperNet', host: 'irc.esper.net', port: 6667 }
]

function useExample(example: ServerExample) {
  form.name = example.name
  form.host = example.host
  form.port = example.port
  // Limpiar errores cuando se selecciona un ejemplo
  errors.value = {}
}

function submitForm() {
  if (processing.value) return

  processing.value = true
  errors.value = {}

  router.post(route('irc-servers.store'), form, {
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
