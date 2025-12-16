/**
 * Firebase Configuration
 * Configuración de Firebase para autenticación
 */

// Configuración de Firebase
const firebaseConfig = {
    apiKey: "AIzaSyCxA82nygUTYZwH1Qj932-XFzxdimP1R-k",
    authDomain: "insertel-105f8.firebaseapp.com",
    projectId: "insertel-105f8",
    storageBucket: "insertel-105f8.firebasestorage.app",
    messagingSenderId: "178210674842",
    appId: "1:178210674842:web:3dce11f30bf6acf4ff6723"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);

// Obtener referencia al servicio de autenticación
const auth = firebase.auth();

// Configurar idioma español para mensajes de Firebase
auth.languageCode = 'es';
