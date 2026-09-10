import { Injectable, NgZone, Inject } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { of } from 'rxjs';
import { map, catchError, take } from 'rxjs/operators';
import { DataAccessService } from './data-access.service';

const IDLE_SECONDS = 600;
const IDLE_MS = IDLE_SECONDS * 1000;
const IDLE_REAUTH_REQUIRED_KEY = 'idle_reauth_required';

@Injectable({ providedIn: 'root' })
export class IdleReauthService {
  private showModalSubject = new BehaviorSubject<boolean>(false);
  private lastActivity = Date.now();
  private idleTimer: any = null;
  private isRunning = false;

  showModal$ = this.showModalSubject.asObservable();

  get isModalVisible(): boolean {
    return this.showModalSubject.value;
  }

  constructor(
    @Inject(NgZone) private ngZone: NgZone,
    @Inject(DataAccessService) private dataAccess: DataAccessService
  ) {}

  start(): void {
    if (this.isRunning) return;
    const token = localStorage.getItem('token');
    const username = localStorage.getItem('username');
    if (!token || !username) return;

    this.isRunning = true;

    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];
    const boundHandler = () => this.ngZone.run(() => this.recordActivity());

    events.forEach((ev) => {
      window.addEventListener(ev, boundHandler, { passive: true });
    });

    // If session had timed out and user refreshed without re-entering password, show modal again (do not restart session)
    if (sessionStorage.getItem(IDLE_REAUTH_REQUIRED_KEY)) {
      this.showModalSubject.next(true);
      return;
    }

    this.resetIdleTimer();
  }

  stop(): void {
    this.isRunning = false;
    if (this.idleTimer) {
      clearTimeout(this.idleTimer);
      this.idleTimer = null;
    }
    this.showModalSubject.next(false);
    try {
      sessionStorage.removeItem(IDLE_REAUTH_REQUIRED_KEY);
    } catch (_) {}
  }

  private recordActivity(): void {
    if (this.showModalSubject.value) return;
    this.lastActivity = Date.now();
    this.resetIdleTimer();
  }

  private resetIdleTimer(): void {
    if (this.idleTimer) clearTimeout(this.idleTimer);
    this.idleTimer = setTimeout(() => this.onIdle(), IDLE_MS);
  }

  private onIdle(): void {
    this.idleTimer = null;
    const token = localStorage.getItem('token');
    if (!token) {
      this.stop();
      return;
    }
    try {
      sessionStorage.setItem(IDLE_REAUTH_REQUIRED_KEY, '1');
    } catch (_) {}
    this.showModalSubject.next(true);
  }

  /** Validates password via store/verify_password.php?type=verifyPassword */
  verifyPassword(password: string): Promise<boolean> {
    if (!localStorage.getItem('token')) return Promise.resolve(false);

    return this.dataAccess.verifyPassword((password || '').trim()).pipe(
      map((res: any) => res && res.status === 'success'),
      catchError(() => of(false)),
      take(1)
    ).toPromise().then((ok) => {
      if (ok === true) {
        try {
          sessionStorage.removeItem(IDLE_REAUTH_REQUIRED_KEY);
        } catch (_) {}
        this.ngZone.run(() => {
          this.showModalSubject.next(false);
          this.lastActivity = Date.now();
          this.resetIdleTimer();
        });
      }
      return ok === true;
    });
  }

  closeModal(): void {
    this.showModalSubject.next(false);
    this.lastActivity = Date.now();
    this.resetIdleTimer();
  }
}
