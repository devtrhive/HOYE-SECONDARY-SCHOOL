// 1. Load the Firebase SDKs for Service Workers
importScripts('https://www.gstatic.com/firebasejs/9.22.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.1/firebase-messaging-compat.js');

// 2. Initialize Firebase in the background
// Replace these values with your ACTUAL config from the Firebase Console
firebase.initializeApp({
  apiKey: "AIzaSyC04fhzQlRgrkB-3yJ_sxVPCacUhX3CvQA",
  authDomain: "hoye-secondary-alerts.firebaseapp.com",
  projectId: "hoye-secondary-alerts",
  storageBucket: "hoye-secondary-alerts.firebasestorage.app",
  messagingSenderId: "40366068065",
  appId: "1:40366068065:web:2fe5002647c4768eb93520"
});

// 3. Start the messaging engine
const messaging = firebase.messaging();

// 4. Handle background messages 
// This is what makes the "SMS-style" alert show up when the browser is closed
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: 'images/SKAVS.png', // Ensure this path is correct
    badge: 'images/logo.png', // Small icon for the notification tray
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});