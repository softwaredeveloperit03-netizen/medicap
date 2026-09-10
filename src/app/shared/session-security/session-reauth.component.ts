import { Component } from '@angular/core';
import { SessionSecurityService } from './session-security.service';
import { DataAccessService } from '../../data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-session-reauth',
  templateUrl: './session-reauth.component.html',
  styleUrls: ['./session-reauth.component.css'],
})
export class SessionReauthComponent {
  visible = false;
  busy = false;
  error = '';
  /** Session idle reauth defaults to 4-digit PIN (login itself is password-only). */
  loginMode: 'password' | 'pin' = 'pin';
  password = '';
  readonly pinLength = 4;
  readonly defaultPin = '1234';
  pinDigits: string[] = ['', '', '', ''];

  get displayUser(): string {
    if (typeof localStorage === 'undefined') {
      return '';
    }
    return localStorage.getItem('username') || localStorage.getItem('emp_id') || '';
  }

  constructor(
    public session: SessionSecurityService,
    private service: DataAccessService
  ) {
    this.session.reauthRequired.subscribe((v) => {
      const shouldShow = !!v && localStorage.getItem('login') === 'yes';
      const opening = shouldShow && !this.visible;
      this.visible = shouldShow;
      // Only clear credentials when the modal first opens — not on every timer tick.
      if (opening) {
        this.error = '';
        this.password = '';
        this.loginMode = 'pin';
        this.clearPin();
        setTimeout(() => this.focusActiveField(), 50);
      }
    });
  }

  private focusActiveField(): void {
    if (this.loginMode === 'password') {
      const el = document.getElementById('reauth-password') as HTMLInputElement | null;
      el?.focus();
      return;
    }
    this.focusPinCell(0);
  }

  setMode(mode: 'password' | 'pin'): void {
    this.loginMode = mode;
    this.error = '';
    this.password = '';
    this.clearPin();
    setTimeout(() => this.focusActiveField(), 0);
  }

  onPasswordInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.password = input?.value ?? '';
  }

  clearPin(): void {
    this.pinDigits = Array(this.pinLength).fill('');
    this.syncPinDom();
  }

  pinValue(): string {
    return this.pinDigits.join('');
  }

  private syncPinDom(): void {
    for (let i = 0; i < this.pinLength; i++) {
      const el = document.getElementById(`reauth-pin-${i}`) as HTMLInputElement | null;
      if (el) {
        el.value = this.pinDigits[i] || '';
        el.removeAttribute('placeholder');
        el.placeholder = '';
      }
    }
  }

  private setPinCell(index: number, digit: string): void {
    this.pinDigits[index] = digit;
    const el = document.getElementById(`reauth-pin-${index}`) as HTMLInputElement | null;
    if (el) {
      el.value = digit;
    }
  }

  private focusPinCell(index: number): void {
    setTimeout(() => {
      const el = document.getElementById(`reauth-pin-${index}`) as HTMLInputElement | null;
      el?.focus();
      el?.select();
    }, 0);
  }

  onPinInput(index: number, event: Event): void {
    const input = event.target as HTMLInputElement;
    const raw = (input.value || '').replace(/\D/g, '');
    const last = this.pinLength - 1;
    if (raw.length > 1) {
      for (let i = 0; i < this.pinLength; i++) {
        this.setPinCell(i, raw[i] || '');
      }
      this.focusPinCell(Math.min(raw.length, this.pinLength) - 1);
      return;
    }
    const val = raw.slice(-1);
    this.setPinCell(index, val);
    if (val && index < last) {
      this.focusPinCell(index + 1);
    }
  }

  onPinKeydown(index: number, event: KeyboardEvent): void {
    const key = event.key;
    const last = this.pinLength - 1;
    if (key === 'Backspace' || key === 'Delete') {
      event.preventDefault();
      if (this.pinDigits[index]) {
        this.setPinCell(index, '');
      } else if (index > 0) {
        this.setPinCell(index - 1, '');
        this.focusPinCell(index - 1);
      }
      return;
    }
    if (key === 'ArrowLeft' && index > 0) {
      event.preventDefault();
      this.focusPinCell(index - 1);
      return;
    }
    if (key === 'ArrowRight' && index < last) {
      event.preventDefault();
      this.focusPinCell(index + 1);
    }
  }

  onPinPaste(event: ClipboardEvent): void {
    event.preventDefault();
    const text = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, this.pinLength);
    for (let i = 0; i < this.pinLength; i++) {
      this.setPinCell(i, text[i] || '');
    }
    this.focusPinCell(text.length > 0 ? Math.min(text.length, this.pinLength) - 1 : 0);
  }

  credential(): string {
    if (this.loginMode === 'pin') {
      return this.pinValue();
    }
    // Browser autofill may fill the input without firing (input); read DOM as source of truth.
    const el = document.getElementById('reauth-password') as HTMLInputElement | null;
    const fromDom = el ? String(el.value || '') : '';
    return String(fromDom || this.password || '').trim();
  }

  private finishReauthSuccess(): void {
    this.session.completeReauth();
    try {
      alertify.success('Session re-authorised.');
    } catch {
      /* ignore */
    }
  }

  confirm(): void {
    const cred = this.credential();
    this.password = this.loginMode === 'password' ? cred : this.password;
    if (!cred || (this.loginMode === 'pin' && cred.length !== this.pinLength)) {
      this.error = this.loginMode === 'pin' ? 'Enter your 4-digit PIN.' : 'Enter your password.';
      return;
    }
    this.busy = true;
    this.error = '';
    // Prefer password verify first on local (token session can flake via proxy); then touch session.
    this.service.verifyAuthCredential(cred).subscribe({
      next: (verified) => {
        if (verified?.status === 'success') {
          this.service.reauthSession(cred).subscribe({
            next: () => {
              this.busy = false;
              this.finishReauthSuccess();
            },
            error: () => {
              // Password already verified — allow continue even if token touch fails.
              this.busy = false;
              this.finishReauthSuccess();
            },
          });
          return;
        }
        // Fallback: full reauthSession (also validates token).
        this.service.reauthSession(cred).subscribe({
          next: (res) => {
            this.busy = false;
            if (res?.status === 'success') {
              this.finishReauthSuccess();
              return;
            }
            if (res?.status === 'session_expired') {
              this.session.forceLogout(res?.message || 'Session expired. Please login again.');
              return;
            }
            this.error = res?.message || verified?.message || 'Invalid password or PIN.';
          },
          error: () => {
            this.busy = false;
            this.error = verified?.message || 'Could not verify credentials. Check your connection.';
          },
        });
      },
      error: () => {
        this.service.reauthSession(cred).subscribe({
          next: (res) => {
            this.busy = false;
            if (res?.status === 'success') {
              this.finishReauthSuccess();
              return;
            }
            if (res?.status === 'session_expired') {
              this.session.forceLogout(res?.message || 'Session expired. Please login again.');
              return;
            }
            this.error = res?.message || 'Invalid password or PIN.';
          },
          error: () => {
            this.busy = false;
            this.error = 'Could not verify credentials. Check your connection.';
          },
        });
      },
    });
  }

  logout(): void {
    this.session.forceLogout('Logged out from re-authorisation screen.');
  }
}
