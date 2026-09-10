import { Injectable, NgZone } from '@angular/core';
import { Router } from '@angular/router';
import { BehaviorSubject, fromEvent, merge, Subscription } from 'rxjs';
import { debounceTime } from 'rxjs/operators';
import { DataAccessService } from '../../data-access.service';

declare let alertify: any;

/**
 * Idle policy:
 *  - 10 minutes idle → password/PIN re-auth modal (session stays alive)
 *  - 15 minutes idle → full logout, clear deep link, show login at `#/`
 *  - 3 hours since login → full logout (hard session max)
 */
@Injectable({ providedIn: 'root' })
export class SessionSecurityService {
  static readonly LOGIN_AT_KEY = 'login_at';
  static readonly LAST_ACTIVITY_KEY = 'last_activity_at';
  static readonly REAUTH_REQUIRED_KEY = 'session_reauth_required';

  /** Soft idle: show re-auth modal. */
  private readonly idleMs = 10 * 60 * 1000;
  /** Hard idle: full logout + clear URL so next login goes to department/main dashboard. */
  private readonly idleLogoutMs = 15 * 60 * 1000;
  private readonly sessionMaxMs = 3 * 60 * 60 * 1000;

  private readonly reauthRequired$ = new BehaviorSubject<boolean>(false);
  readonly reauthRequired = this.reauthRequired$.asObservable();

  private tickTimer: ReturnType<typeof setInterval> | null = null;
  private activitySub: Subscription | null = null;
  private started = false;

  constructor(
    private service: DataAccessService,
    private router: Router,
    private zone: NgZone
  ) {}

  init(): void {
    if (this.started || typeof window === 'undefined') {
      return;
    }
    this.started = true;
    this.syncReauthFlag();
    this.bindActivity();
    this.zone.runOutsideAngular(() => {
      this.tickTimer = setInterval(() => this.zone.run(() => this.tick()), 15000);
    });
    this.tick();
  }

  markLoginSuccess(): void {
    const now = String(Date.now());
    localStorage.setItem(SessionSecurityService.LOGIN_AT_KEY, now);
    localStorage.setItem(SessionSecurityService.LAST_ACTIVITY_KEY, now);
    localStorage.removeItem(SessionSecurityService.REAUTH_REQUIRED_KEY);
    this.reauthRequired$.next(false);
    // Grace window so post-login navigation is not killed by a stale idle timer.
    this.loginGraceUntil = Date.now() + 15000;
  }

  private loginGraceUntil = 0;

  touchActivity(): void {
    if (localStorage.getItem('login') !== 'yes') {
      return;
    }
    if (localStorage.getItem(SessionSecurityService.REAUTH_REQUIRED_KEY) === 'yes') {
      return;
    }
    localStorage.setItem(SessionSecurityService.LAST_ACTIVITY_KEY, String(Date.now()));
  }

  isReauthOpen(): boolean {
    return this.reauthRequired$.value;
  }

  completeReauth(): void {
    localStorage.removeItem(SessionSecurityService.REAUTH_REQUIRED_KEY);
    this.touchActivity();
    this.reauthRequired$.next(false);
  }

  forceLogout(reason?: string): void {
    if (reason) {
      try {
        alertify.warning(reason);
      } catch {
        /* ignore */
      }
    }
    this.reauthRequired$.next(false);
    localStorage.removeItem(SessionSecurityService.REAUTH_REQUIRED_KEY);
    localStorage.removeItem('login');
    this.service.logout();
  }

  private syncReauthFlag(): void {
    const needs = localStorage.getItem(SessionSecurityService.REAUTH_REQUIRED_KEY) === 'yes'
      && localStorage.getItem('login') === 'yes';
    this.reauthRequired$.next(needs);
  }

  private bindActivity(): void {
    if (typeof document === 'undefined') {
      return;
    }
    this.activitySub = merge(
      fromEvent(document, 'mousemove'),
      fromEvent(document, 'mousedown'),
      fromEvent(document, 'keydown'),
      fromEvent(document, 'touchstart'),
      fromEvent(document, 'scroll'),
      fromEvent(window, 'focus')
    )
      .pipe(debounceTime(500))
      .subscribe(() => this.touchActivity());
  }

  private tick(): void {
    if (localStorage.getItem('login') !== 'yes' || !localStorage.getItem('token')) {
      return;
    }
    if (this.loginGraceUntil && Date.now() < this.loginGraceUntil) {
      return;
    }

    const loginAt = Number(localStorage.getItem(SessionSecurityService.LOGIN_AT_KEY) || '0');
    const lastActivity = Number(
      localStorage.getItem(SessionSecurityService.LAST_ACTIVITY_KEY) || loginAt || '0'
    );
    const now = Date.now();

    if (loginAt > 0 && now - loginAt >= this.sessionMaxMs) {
      this.forceLogout('Session expired after 3 hours. Please login again.');
      return;
    }

    // 15 min no activity → full logout (clears deep link; next login → dept/main dashboard)
    if (lastActivity > 0 && now - lastActivity >= this.idleLogoutMs) {
      this.forceLogout(
        'Logged out after 15 minutes of inactivity. Please login again.'
      );
      return;
    }

    // 10 min idle → re-auth only (do not logout)
    if (lastActivity > 0 && now - lastActivity >= this.idleMs) {
      if (localStorage.getItem(SessionSecurityService.REAUTH_REQUIRED_KEY) !== 'yes') {
        localStorage.setItem(SessionSecurityService.REAUTH_REQUIRED_KEY, 'yes');
      }
      // Emit only once when opening — re-emitting clears the password field mid-typing.
      if (!this.reauthRequired$.value) {
        this.reauthRequired$.next(true);
      }
    }
  }
}
