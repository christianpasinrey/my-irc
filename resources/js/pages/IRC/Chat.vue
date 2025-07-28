<template>
  <div class="h-screen bg-gray-900 text-white flex">
    <!-- Sidebar con canales -->
    <div class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">
      <!-- Header del servidor -->
      <div class="p-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold truncate">{{ server.name || server.host }}</h2>
        <p class="text-sm text-gray-400">{{ server.host }}:{{ server.port }}</p>
        <div class="mt-2 flex items-center">
          <div :class="connectionStatusClass" class="w-3 h-3 rounded-full mr-2"></div>
          <span class="text-sm">{{ connectionStatus }}</span>
        </div>
      </div>

      <!-- Formulario de conexión -->
      <div v-if="!isConnected" class="p-4 border-b border-gray-700">
        <form @submit.prevent="connect">
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-300 mb-1">Nickname</label>
            <input
              v-model="connectionForm.nickname"
              type="text"
              required
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              placeholder="Tu nickname"
            />
          </div>
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-300 mb-1">Canal</label>
            <input
              v-model="connectionForm.channel"
              type="text"
              required
              class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              placeholder="#general"
            />
          </div>
          <button
            type="submit"
            :disabled="connecting"
            class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-600 px-4 py-2 rounded text-sm font-medium"
          >
            {{ connecting ? 'Conectando...' : 'Conectar' }}
          </button>
        </form>
      </div>

      <!-- Lista de canales -->
      <div v-if="isConnected" class="flex-1 overflow-y-auto">
        <div class="p-4">
          <h3 class="text-sm font-medium text-gray-300 mb-2">Canales</h3>
          <div class="space-y-1">
            <button
              v-for="channel in channels"
              :key="channel"
              @click="switchChannel(channel)"
              :class="[
                'w-full text-left px-3 py-2 rounded text-sm',
                currentChannel === channel
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-300 hover:bg-gray-700'
              ]"
            >
              {{ channel }}
            </button>
          </div>
          
          <!-- Agregar nuevo canal -->
          <div class="mt-4">
            <form @submit.prevent="joinNewChannel" class="flex">
              <input
                v-model="newChannelName"
                type="text"
                placeholder="#nuevo-canal"
                class="flex-1 px-2 py-1 bg-gray-700 border border-gray-600 rounded-l text-white placeholder-gray-400 text-sm focus:outline-none focus:border-blue-500"
              />
              <button
                type="submit"
                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-r text-sm"
              >
                +
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Botón de desconexión -->
      <div v-if="isConnected" class="p-4 border-t border-gray-700">
        <button
          @click="disconnect"
          class="w-full bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm font-medium"
        >
          Desconectar
        </button>
      </div>
    </div>

    <!-- Area principal de chat -->
    <div class="flex-1 flex flex-col">
      <!-- Header del canal actual -->
      <div v-if="isConnected" class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <h3 class="text-lg font-semibold">{{ currentChannel }}</h3>
        <p class="text-sm text-gray-400">{{ onlineUsers.length }} usuarios conectados</p>
      </div>

      <!-- Mensajes del chat -->
      <div class="flex-1 flex">
        <!-- Lista de mensajes -->
        <div class="flex-1 flex flex-col">
          <div
            ref="messagesContainer"
            class="flex-1 overflow-y-auto p-4 space-y-3"
          >
            <div v-if="!isConnected" class="text-center text-gray-400 py-12">
              <svg
                class="mx-auto h-12 w-12 text-gray-600 mb-4"
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
              <p>Conéctate al servidor para comenzar a chatear</p>
            </div>

            <div
              v-for="message in messages"
              :key="message.id || message.timestamp"
              class="flex items-start space-x-3"
            >
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-xs font-medium">
                  {{ message.nickname.charAt(0).toUpperCase() }}
                </div>
              </div>
              <div class="flex-1">
                <div class="flex items-baseline space-x-2">
                  <span class="font-medium text-blue-400">{{ message.nickname }}</span>
                  <span class="text-xs text-gray-500">
                    {{ formatTime(message.timestamp) }}
                  </span>
                </div>
                <p class="text-gray-300 mt-1">{{ message.message }}</p>
              </div>
            </div>
          </div>

          <!-- Input para enviar mensajes -->
          <div v-if="isConnected" class="border-t border-gray-700 p-4">
            <form @submit.prevent="sendMessage" class="flex space-x-2">
              <input
                v-model="messageInput"
                type="text"
                placeholder="Escribe tu mensaje..."
                class="flex-1 px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
              <button
                type="submit"
                :disabled="!messageInput.trim() || sending"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed rounded font-medium"
              >
                {{ sending ? 'Enviando...' : 'Enviar' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Lista de usuarios -->
        <div v-if="isConnected" class="w-48 bg-gray-800 border-l border-gray-700 p-4">
          <h4 class="text-sm font-medium text-gray-300 mb-3">Usuarios ({{ onlineUsers.length }})</h4>
          <div class="space-y-1">
            <div
              v-for="user in onlineUsers"
              :key="user"
              class="flex items-center space-x-2 text-sm text-gray-300"
            >
              <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              <span>{{ user }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'

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
const onlineUsers = ref<string[]>(['Usuario1', 'Usuario2', 'Bot'])
const messagesContainer = ref<HTMLElement>()

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
    // connect()
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
    const response = await fetch(`/irc/chat/${props.server.id}/connect`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(connectionForm)
    })
    
    const data = await response.json()
    
    if (data.success) {
      isConnected.value = true
      currentChannel.value = connectionForm.channel
      startMessagePolling()
      await loadMessages()
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
    await fetch(`/irc/chat/${props.server.id}/disconnect`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    isConnected.value = false
    messages.value = []
    
    if (messagePolling) {
      clearInterval(messagePolling)
      messagePolling = null
    }
  } catch (error) {
    console.error('Error disconnecting:', error)
  }
}

async function sendMessage() {
  if (!messageInput.value.trim() || sending.value) return
  
  sending.value = true
  
  try {
    const response = await fetch(`/irc/chat/${props.server.id}/send`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        message: messageInput.value,
        channel: currentChannel.value
      })
    })
    
    const data = await response.json()
    
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
    const response = await fetch(`/irc/chat/${props.server.id}/messages?channel=${encodeURIComponent(currentChannel.value)}`)
    const data = await response.json()
    
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
}

async function joinNewChannel() {
  if (!newChannelName.value.trim()) return
  
  const channel = newChannelName.value.startsWith('#') 
    ? newChannelName.value 
    : '#' + newChannelName.value
  
  try {
    const response = await fetch(`/irc/chat/${props.server.id}/join-channel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ channel })
    })
    
    const data = await response.json()
    
    if (data.success) {
      newChannelName.value = ''
      switchChannel(channel)
    } else {
      alert(data.message || 'Error al unirse al canal')
    }
  } catch (error) {
    console.error('Error joining channel:', error)
    alert('Error al unirse al canal')
  }
}

function startMessagePolling() {
  messagePolling = setInterval(() => {
    if (isConnected.value) {
      loadMessages()
    }
  }, 2000) // Actualizar cada 2 segundos
}

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
</script>
