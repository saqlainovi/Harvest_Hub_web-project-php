// Firebase Authentication 
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-analytics.js";
import { 
    getAuth, 
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    GoogleAuthProvider,
    signInWithPopup,
    sendPasswordResetEmail 
} from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyAOAa7VEgItWHej6-HFUVNJVpfwzB5hE3A",
    authDomain: "ovisoft-e5377.firebaseapp.com",
    projectId: "ovisoft-e5377",
    storageBucket: "ovisoft-e5377.firebasestorage.app",
    messagingSenderId: "950806320878",
    appId: "1:950806320878:web:3879c1c50a517365e605ff",
    measurementId: "G-SV29JBD062"
};

// Get site base URL from the current location
const getSiteUrl = () => {
    const pathParts = window.location.pathname.split('/');
    // Find the index of the main folder (asraf idp2)
    let mainFolderIndex = -1;
    for (let i = 0; i < pathParts.length; i++) {
        if (pathParts[i] === 'asraf idp2' || pathParts[i] === 'asraf%20idp2') {
            mainFolderIndex = i;
            break;
        }
    }
    
    if (mainFolderIndex !== -1) {
        const basePathParts = pathParts.slice(0, mainFolderIndex + 1);
        return window.location.origin + basePathParts.join('/');
    }
    
    // Fallback to default
    return window.location.origin;
};

const SITE_URL = getSiteUrl();

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
const auth = getAuth(app);
const googleProvider = new GoogleAuthProvider();

// Register with email and password
export async function registerWithEmailPassword(email, password, userData) {
    try {
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        
        // Get Firebase ID token for server verification
        const idToken = await user.getIdToken();
        
        // Register user on the server side with the token
        return await registerUserOnServer(idToken, userData);
    } catch (error) {
        console.error("Registration error:", error);
        return { success: false, error: error.message };
    }
}

// Login with email and password
export async function loginWithEmailPassword(email, password) {
    try {
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        
        // Get Firebase ID token for server verification
        const idToken = await user.getIdToken();
        
        // Authenticate on the server side with the token
        return await authenticateOnServer(idToken);
    } catch (error) {
        console.error("Login error:", error);
        return { success: false, error: error.message };
    }
}

// Login with Google
export async function loginWithGoogle() {
    try {
        const result = await signInWithPopup(auth, googleProvider);
        const user = result.user;
        
        // Get Firebase ID token for server verification
        const idToken = await user.getIdToken();
        
        // Create or authenticate user on server
        return await authenticateOnServer(idToken);
    } catch (error) {
        console.error("Google login error:", error);
        return { success: false, error: error.message };
    }
}

// Logout
export async function logout() {
    try {
        await signOut(auth);
        
        // Clear server session
        await fetch(`${SITE_URL}/pages/logout.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        
        return { success: true };
    } catch (error) {
        console.error("Logout error:", error);
        return { success: false, error: error.message };
    }
}

// Reset password
export async function resetPassword(email) {
    try {
        await sendPasswordResetEmail(auth, email);
        return { success: true };
    } catch (error) {
        console.error("Password reset error:", error);
        return { success: false, error: error.message };
    }
}

// Check authentication state
export function onAuthStateChange(callback) {
    return onAuthStateChanged(auth, callback);
}

// Function to register user on the server
async function registerUserOnServer(idToken, userData) {
    try {
        const response = await fetch(`${SITE_URL}/pages/firebase_register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idToken,
                userData,
            }),
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.error("Server registration error:", data);
            throw new Error(data.message || 'Server registration failed');
        }
        
        return data;
    } catch (error) {
        console.error("Server registration error:", error);
        throw error;
    }
}

// Function to authenticate with the server
async function authenticateOnServer(idToken) {
    try {
        console.log("Authenticating with server at:", `${SITE_URL}/pages/firebase_auth.php`);
        
        const response = await fetch(`${SITE_URL}/pages/firebase_auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idToken,
            }),
        });
        
        console.log("Server response status:", response.status);
        
        const data = await response.json();
        console.log("Server response data:", data);
        
        if (!data.success) {
            console.error("Server authentication error:", data);
            throw new Error(data.message || 'Server authentication failed');
        }
        
        return data;
    } catch (error) {
        console.error("Server authentication error:", error);
        throw error;
    }
} 