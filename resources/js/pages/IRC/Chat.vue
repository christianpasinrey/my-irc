<template>
    <AppLayout>
        <div class="h-[91dvh] flex">
            <!-- Sidebar con canales -->
            <div class="w-64 bg-card border-r border-sidebar-border/70 dark:border-sidebar-border flex flex-col">
                <!-- Header del servidor -->
                <div class="p-4 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <h2 class="text-lg font-semibold text-foreground truncate">{{ server.name || server.host }}</h2>
                    <p class="text-sm text-muted-foreground">{{ server.host }}:{{ server.port }}</p>
                    <div class="mt-2 flex items-center">
                        <div :class="connectionStatusClass" class="w-3 h-3 rounded-full mr-2"></div>
                        <span class="text-sm text-muted-foreground">{{ connectionStatus }}</span>
                    </div>
                </div>

                <!-- Formulario de conexión -->
                <Card v-if="!isConnected" class="m-4 p-4">
                    <form @submit.prevent="connect" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Nickname</Label>
                            <Input
                                v-model="connectionForm.nickname"
                                type="text"
                                required
                                placeholder="Tu nickname"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Canal</Label>
                            <Input
                                v-model="connectionForm.channel"
                                type="text"
                                required
                                placeholder="#general"
                            />
                        </div>
                        <Button
                            type="submit"
                            :disabled="connecting"
                            class="w-full"
                            variant="default"
                        >
                            {{ connecting ? 'Conectando...' : 'Conectar' }}
                        </Button>
                    </form>
                </Card>
                <div class="p-4 border-t border-sidebar-border/70 dark:border-sidebar-border space-y-2">
                    <Button
                        @click="disconnect"
                        variant="destructive"
                        class="w-full"
                    >
                        Desconectar
                    </Button>
                    <Button
                        @click="disconnectAll"
                        variant="outline"
                        class="w-full"
                    >
                        Desconectar todas mis conexiones
                    </Button>
                </div>
                <!-- Lista de canales -->
                <div v-if="isConnected" class="flex-1 overflow-y-auto">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-foreground">Canales</h3>
                            <Button
                                @click="loadChannelList"
                                variant="ghost"
                                size="sm"
                                :disabled="loadingChannels"
                            >
                                {{ loadingChannels ? '...' : '🔄' }}
                            </Button>
                        </div>

                        <!-- Canales unidos -->
                        <div v-if="joinedChannels.length > 0" class="space-y-1 mb-4">
                            <p class="text-xs text-muted-foreground mb-1">Unidos:</p>
                            <Button
                                v-for="channel in joinedChannels"
                                :key="channel"
                                @click="switchChannel(channel)"
                                :variant="currentChannel === channel ? 'default' : 'ghost'"
                                class="w-full justify-start"
                                size="sm"
                            >
                                {{ channel }}
                            </Button>
                        </div>

                        <!-- Lista de canales disponibles -->
                        <div v-if="availableChannels.length > 0" class="space-y-1 mb-4">
                            <p class="text-xs text-muted-foreground mb-1">Disponibles:</p>
                            <div class="max-h-40 overflow-y-auto space-y-1">
                                <div
                                    v-for="channel in availableChannels.slice(0, 20)"
                                    :key="channel.name"
                                    class="flex items-center justify-between p-2 rounded border border-sidebar-border/50"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate">{{ channel.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ channel.users }} usuarios</p>
                                        <p v-if="channel.topic" class="text-xs text-muted-foreground truncate">{{ channel.topic }}</p>
                                    </div>
                                    <Button
                                        @click="joinChannelFromList(channel.name)"
                                        variant="ghost"
                                        size="sm"
                                        class="ml-2"
                                    >
                                        +
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Agregar nuevo canal -->
                        <div class="mt-4">
                            <form @submit.prevent="joinNewChannel" class="flex">
                                <Input
                                    v-model="newChannelName"
                                    type="text"
                                    placeholder="#nuevo-canal"
                                    class="rounded-r-none"
                                />
                                <Button
                                    type="submit"
                                    class="rounded-l-none"
                                    size="sm"
                                >
                                    +
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Botón de desconexión -->
                <div v-if="isConnected" class="p-4 border-t border-sidebar-border/70 dark:border-sidebar-border">
                    <Button
                        @click="disconnect"
                        variant="destructive"
                        class="w-full"
                    >
                        Desconectar
                    </Button>
                </div>
            </div>

            <!-- Area principal de chat -->
            <div class="flex-1 flex flex-col">
                <!-- Header del canal actual -->
                <div v-if="isConnected" class="bg-card border-b border-sidebar-border/70 dark:border-sidebar-border px-6 py-4">
                    <h3 class="text-lg font-semibold text-foreground">{{ currentChannel }}</h3>
                    <p class="text-sm text-muted-foreground">{{ onlineUsers.length }} usuarios conectados</p>
                </div>

                <!-- Mensajes del chat -->
                <div class="flex-1 flex">
                    <!-- Lista de mensajes -->
                    <div class="flex-1 flex flex-col">
                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto p-4 space-y-3"
                        >
                            <div v-if="!isConnected" class="text-center text-muted-foreground py-12">
                                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg
                                        class="w-8 h-8 text-muted-foreground"
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
                                <p>Conéctate al servidor para comenzar a chatear</p>
                            </div>

                            <div
                                v-for="message in messages"
                                :key="message.id || message.timestamp"
                                class="flex items-start space-x-3"
                            >
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-xs font-medium text-primary">
                                        {{ message.nickname.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-baseline space-x-2">
                                        <span class="font-medium text-primary">{{ message.nickname }}</span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ formatTime(message.timestamp) }}
                                        </span>
                                    </div>
                                    <p class="text-foreground mt-1">{{ message.message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Input para enviar mensajes -->
                        <div v-if="isConnected" class="border-t border-sidebar-border/70 dark:border-sidebar-border p-4">
                            <form @submit.prevent="sendMessage" class="flex space-x-2">
                                <Input
                                    v-model="messageInput"
                                    type="text"
                                    placeholder="Escribe tu mensaje..."
                                    class="flex-1"
                                />
                                <Button
                                    type="submit"
                                    :disabled="!messageInput.trim() || sending"
                                >
                                    {{ sending ? 'Enviando...' : 'Enviar' }}
                                </Button>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de usuarios -->
                    <div v-if="isConnected" class="w-48 bg-card border-l border-sidebar-border/70 dark:border-sidebar-border p-4">
                        <h4 class="text-sm font-medium text-foreground mb-3">Usuarios ({{ onlineUsers.length }})</h4>
                        <div class="space-y-1">
                            <div
                                v-for="user in onlineUsers"
                                :key="user"
                                class="flex items-center space-x-2 text-sm text-muted-foreground"
                            >
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span>{{ user }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'

// Declarar Echo en window para evitar error de acceso
declare global {
  interface Window {
    Echo: any;
  }
}
import axios from 'axios'
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

interface IRCMessage {
  id?: number
  nickname: string
  message: string
  channel: string
  timestamp: string
}

interface ConnectionForm {
  nickname: string
  channel: string
}

interface Props {
  server: IRCServer
  channels: string[]
  nickname: string
}

const props = defineProps<Props>()

const isConnected = ref(false)
const connecting = ref(false)
const currentChannel = ref('#general')
const messages = ref<IRCMessage[]>([])
const messageInput = ref('')
const sending = ref(false)
const newChannelName = ref('')
const onlineUsers = ref<string[]>([])
const messagesContainer = ref<HTMLElement>()
const loadingChannels = ref(false)
const joinedChannels = ref<string[]>([])
const availableChannels = ref<Array<{name: string, users: number, topic: string}>>([])

const connectionForm = reactive<ConnectionForm>({
  nickname: props.nickname,
  channel: '#general'
})

const connectionStatus = computed(() => {
  if (connecting.value) return 'Conectando...'
  if (isConnected.value) return 'Conectado'
  return 'Desconectado'
})

const connectionStatusClass = computed(() => {
  if (connecting.value) return 'bg-yellow-500'
  if (isConnected.value) return 'bg-green-500'
  return 'bg-red-500'
})

let messagePolling: number | null = null

onMounted(() => {
  // Auto-conectar si ya tenemos los datos
  if (connectionForm.nickname && connectionForm.channel) {
    connect()
  }

  // Verificar estado de conexión
  checkConnectionStatus()
  if (window.Echo) {
    window.Echo.channel('irc.' + props.server.id + '.' + currentChannel.value.replace('#', ''))
      .listen('IRCMessageReceived', async (e) => {
        messages.value.push({
          nickname: e.nickname,
          message: e.message,
          channel: e.channel,
          timestamp: e.timestamp
        });
        await loadChannelUsers();
        nextTick(() => scrollToBottom());
      });
  }
})

onBeforeUnmount(() => {
  if (messagePolling) {
    clearInterval(messagePolling)
  }
})

async function connect() {
  if (connecting.value) return

  connecting.value = true

  try {
    const response = await axios.post(route('irc-chat.connect', props.server.id), {
      channel: connectionForm.channel,
      nickname: connectionForm.nickname
    })

    const data = response.data

    if (data.success) {
      isConnected.value = true
      currentChannel.value = connectionForm.channel
      joinedChannels.value = [connectionForm.channel]
      //startMessagePolling()
      await loadMessages()
      await loadChannelUsers()
    } else {
      alert(data.message || 'Error al conectar')
    }
  } catch (error) {
    console.error('Error connecting:', error)
    alert('Error de conexión')
  } finally {
    connecting.value = false
  }
}

async function disconnect() {
  try {
    await axios.post(route('irc-chat.disconnect', props.server.id))

    // Desuscribirse del canal Echo
    if (window.Echo) {
      window.Echo.leave('irc.' + props.server.id + '.' + currentChannel.value.replace('#', ''));
    }

    isConnected.value = false
    messages.value = []
    joinedChannels.value = []
    onlineUsers.value = []
    availableChannels.value = []

    if (messagePolling) {
      clearInterval(messagePolling)
      messagePolling = null
    }
  } catch (error) {
    console.error('Error disconnecting:', error)
  }
}

async function disconnectAll() {
  try {
    const response = await axios.post(route('irc-chat.disconnect-all'));
    const data = response.data;
    alert(data.message || 'Desconectadas todas las conexiones');
    // Desuscribirse del canal Echo
    if (window.Echo) {
      window.Echo.leave('irc.' + props.server.id + '.' + currentChannel.value.replace('#', ''));
    }

    isConnected.value = false;
    joinedChannels.value = [];
    availableChannels.value = [];
    onlineUsers.value = [];
    messages.value = [];
    if (messagePolling) {
      clearInterval(messagePolling)
      messagePolling = null
    }
  } catch (error) {
    alert('Error al desconectar todas las conexiones');
    console.error(error);
  }
}

async function sendMessage() {
  if (!messageInput.value.trim() || sending.value) return

  sending.value = true

  try {
    const response = await axios.post(route('irc-chat.send', props.server.id), {
      message: messageInput.value,
      channel: currentChannel.value
    })

    const data = response.data

    if (data.success) {
      messageInput.value = ''
      await loadMessages()
    } else {
      alert(data.message || 'Error al enviar mensaje')
    }
  } catch (error) {
    console.error('Error sending message:', error)
    alert('Error al enviar mensaje')
  } finally {
    sending.value = false
  }
}

async function loadMessages() {
  try {
    const response = await axios.get(`${route('irc-chat.messages', props.server.id)}?channel=${encodeURIComponent(currentChannel.value)}`)
    const data = response.data

    if (data.success) {
      messages.value = data.messages
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Error loading messages:', error)
  }
}

function switchChannel(channel: string) {
  currentChannel.value = channel
  messages.value = []
  loadMessages()
  loadChannelUsers()
}

async function joinNewChannel() {
  if (!newChannelName.value.trim()) return

  const channel = newChannelName.value.startsWith('#')
    ? newChannelName.value
    : '#' + newChannelName.value

  await joinChannelByName(channel)
  newChannelName.value = ''
}

/* function startMessagePolling() {
  messagePolling = setInterval(() => {
    if (isConnected.value) {
      loadMessages()
    }
  }, 2000) // Actualizar cada 2 segundos
} */

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function formatTime(timestamp: string): string {
  return new Date(timestamp).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function checkConnectionStatus() {
  try {
    const response = await axios.get(route('irc-chat.status', props.server.id))
    const data = response.data

    if (data.success) {
      isConnected.value = data.isConnected
      if (data.isConnected && data.connectionInfo) {
        connectionForm.nickname = data.connectionInfo.nickname
        await loadChannelUsers()
      }
    }
  } catch (error) {
    console.error('Error checking connection status:', error)
  }
}

async function loadChannelList() {
  if (loadingChannels.value || !isConnected.value) return

  loadingChannels.value = true

  try {
    const response = await axios.get(route('irc-chat.channel-list', props.server.id))
    const data = response.data

    if (data.success) {
      availableChannels.value = data.channels
    } else {
      console.error('Error loading channels:', data.message)
    }
  } catch (error) {
    console.error('Error loading channel list:', error)
  } finally {
    loadingChannels.value = false
  }
}

async function loadChannelUsers() {
  try {
    const response = await axios.get(`${route('irc-chat.channel-users', props.server.id)}?channel=${encodeURIComponent(currentChannel.value)}`)
    const data = response.data

    if (data.success) {
      onlineUsers.value = data.users
    }
  } catch (error) {
    console.error('Error loading channel users:', error)
  }
}

async function joinChannelFromList(channelName: string) {
  await joinChannelByName(channelName)
}

async function joinChannelByName(channelName: string) {
  try {
    const response = await axios.post(route('irc-chat.join-channel', props.server.id), {
      channel: channelName
    })

    const data = response.data

    if (data.success) {
      if (!joinedChannels.value.includes(channelName)) {
        joinedChannels.value.push(channelName)
      }
      switchChannel(channelName)
    } else {
      alert(data.message || 'Error al unirse al canal')
    }
  } catch (error) {
    console.error('Error joining channel:', error)
    alert('Error al unirse al canal')
  }
}
</script>
