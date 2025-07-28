<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Tarjetas principales de IRC -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Gestión de Servidores -->
                <Link :href="route('irc-servers.index')" class="group relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-gradient-to-br from-blue-500/10 to-blue-600/10 hover:from-blue-500/20 hover:to-blue-600/20 transition-all duration-200">
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v4a2 2 0 01-2 2H5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-foreground mb-2">Servidores IRC</h3>
                        <p class="text-sm text-muted-foreground">Gestiona tus servidores IRC</p>
                    </div>
                </Link>

                <!-- Agregar Servidor -->
                <Link :href="route('irc-servers.create')" class="group relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-gradient-to-br from-green-500/10 to-green-600/10 hover:from-green-500/20 hover:to-green-600/20 transition-all duration-200">
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-foreground mb-2">Nuevo Servidor</h3>
                        <p class="text-sm text-muted-foreground">Añade un servidor IRC</p>
                    </div>
                </Link>

                <!-- Estados de Conexión -->
                <div class="group relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-gradient-to-br from-purple-500/10 to-purple-600/10">
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-foreground mb-2">Estado</h3>
                        <p class="text-sm text-muted-foreground">0 conexiones activas</p>
                    </div>
                </div>
            </div>

            <!-- Panel principal de información -->
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border bg-background/50">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-4">Bienvenido a tu Cliente IRC</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-3">
                            <h3 class="text-lg font-medium text-foreground">Características:</h3>
                            <ul class="space-y-2 text-sm text-muted-foreground">
                                <li class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                    Gestión de múltiples servidores IRC
                                </li>
                                <li class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                    Chat en tiempo real
                                </li>
                                <li class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                    Múltiples canales por servidor
                                </li>
                                <li class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                    Historial de mensajes
                                </li>
                            </ul>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-lg font-medium text-foreground">Comenzar:</h3>
                            <div class="space-y-2 text-sm text-muted-foreground">
                                <p>1. Añade un servidor IRC desde el botón "Nuevo Servidor"</p>
                                <p>2. Configura el host y puerto del servidor</p>
                                <p>3. Conéctate y únete a canales</p>
                                <p>4. ¡Comienza a chatear!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
