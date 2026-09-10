import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

/**
 * Shared Authentication Sync Service
 * Allows sharing login credentials between different Cursor windows/projects
 */
@Injectable({
  providedIn: 'root'
})
export class AuthSyncService {
  private authDataSubject = new BehaviorSubject<any>(null);
  public authData$: Observable<any> = this.authDataSubject.asObservable();

  // Storage key prefix for cross-project sharing
  private readonly STORAGE_KEY_PREFIX = 'shared_auth_';
  
  // Keys used in localStorage
  private readonly AUTH_KEYS = [
    'token',
    'plant_id',
    'is_corporate',
    'department',
    'logo_path',
    'licence_no',
    'loger_id',
    'emp_id',
    'hrView',
    'ho_plant_id',
    'designation',
    'user',
    'checker',
    'approver',
    'qms_approver',
    'dept_head',
    'username',
    'user_department',
    'user_no',
    'type',
    'company_name',
    'login',
    'client_code'
  ];

  constructor() {
    // Listen for storage events from other windows/tabs
    window.addEventListener('storage', (e) => {
      if (e.key && e.key.startsWith(this.STORAGE_KEY_PREFIX)) {
        this.loadAuthFromStorage();
      }
    });

    // Listen for postMessage from other windows (for same-origin)
    window.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'AUTH_SYNC') {
        this.handleAuthSync(event.data.authData);
      }
    });

    // Load initial auth data
    this.loadAuthFromStorage();
  }

  /**
   * Save authentication data to localStorage and sync across windows
   */
  saveAuthData(authData: any): void {
    // Save to regular localStorage
    Object.keys(authData).forEach(key => {
      if (authData[key] !== null && authData[key] !== undefined) {
        localStorage.setItem(key, authData[key]);
      }
    });

    // Save to shared storage with prefix for cross-project access
    const sharedData: any = {};
    this.AUTH_KEYS.forEach(key => {
      const value = localStorage.getItem(key);
      if (value) {
        sharedData[key] = value;
        localStorage.setItem(`${this.STORAGE_KEY_PREFIX}${key}`, value);
      }
    });

    // Save complete auth object
    localStorage.setItem(`${this.STORAGE_KEY_PREFIX}complete`, JSON.stringify(sharedData));

    // Broadcast to other windows via postMessage
    this.broadcastAuthData(sharedData);

    // Update subject
    this.authDataSubject.next(sharedData);
  }

  /**
   * Load authentication data from localStorage
   */
  loadAuthFromStorage(): any {
    // Try to load from shared storage first
    const sharedAuthStr = localStorage.getItem(`${this.STORAGE_KEY_PREFIX}complete`);
    
    if (sharedAuthStr) {
      try {
        const sharedAuth = JSON.parse(sharedAuthStr);
        // Copy to regular localStorage keys
        Object.keys(sharedAuth).forEach(key => {
          localStorage.setItem(key, sharedAuth[key]);
        });
        this.authDataSubject.next(sharedAuth);
        return sharedAuth;
      } catch (e) {
        console.error('Error parsing shared auth data:', e);
      }
    }

    // Fallback: load from regular localStorage
    const authData: any = {};
    this.AUTH_KEYS.forEach(key => {
      const value = localStorage.getItem(key);
      if (value) {
        authData[key] = value;
      }
    });

    if (Object.keys(authData).length > 0) {
      this.authDataSubject.next(authData);
      return authData;
    }

    return null;
  }

  /**
   * Get specific auth value
   */
  getAuthValue(key: string): string | null {
    // Try shared storage first
    const sharedValue = localStorage.getItem(`${this.STORAGE_KEY_PREFIX}${key}`);
    if (sharedValue) {
      return sharedValue;
    }
    // Fallback to regular localStorage
    return localStorage.getItem(key);
  }

  /**
   * Get all auth data
   */
  getAllAuthData(): any {
    return this.loadAuthFromStorage();
  }

  /**
   * Check if user is logged in
   */
  isLoggedIn(): boolean {
    const token = this.getAuthValue('token');
    const login = this.getAuthValue('login');
    return token !== null && login === 'yes';
  }

  /**
   * Clear authentication data
   */
  clearAuthData(): void {
    // Clear regular localStorage
    this.AUTH_KEYS.forEach(key => {
      localStorage.removeItem(key);
      localStorage.removeItem(`${this.STORAGE_KEY_PREFIX}${key}`);
    });
    localStorage.removeItem(`${this.STORAGE_KEY_PREFIX}complete`);
    
    // Broadcast clear event
    this.broadcastAuthData(null);
    
    this.authDataSubject.next(null);
  }

  /**
   * Broadcast auth data to other windows via postMessage
   */
  private broadcastAuthData(authData: any): void {
    // This works for same-origin windows
    // For cross-origin, you'd need a different approach
    try {
      // Try to send to opener window
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage({
          type: 'AUTH_SYNC',
          authData: authData
        }, '*');
      }

      // Try to send to parent window
      if (window.parent && window.parent !== window) {
        window.parent.postMessage({
          type: 'AUTH_SYNC',
          authData: authData
        }, '*');
      }
    } catch (e) {
      // Cross-origin restrictions - this is expected
      console.log('Cannot broadcast to other windows (cross-origin):', e);
    }
  }

  /**
   * Handle auth sync from postMessage
   */
  private handleAuthSync(authData: any): void {
    if (authData) {
      this.saveAuthData(authData);
    } else {
      this.clearAuthData();
    }
  }

  /**
   * Request auth data from other windows
   */
  requestAuthFromOtherWindows(): void {
    // Send request message
    try {
      if (window.opener && !window.opener.closed) {
        window.opener.postMessage({
          type: 'AUTH_REQUEST'
        }, '*');
      }

      if (window.parent && window.parent !== window) {
        window.parent.postMessage({
          type: 'AUTH_REQUEST'
        }, '*');
      }
    } catch (e) {
      console.log('Cannot request auth from other windows:', e);
    }
  }
}
