import { Injectable } from '@angular/core';
import { BehaviorSubject, interval, of, Observable } from 'rxjs';
import { startWith, switchMap, catchError } from 'rxjs/operators';

export type ConnectivityStatus = 'connected' | 'low' | 'disconnected';

@Injectable({ providedIn: 'root' })
export class ConnectivityService {
  private statusSubject = new BehaviorSubject<ConnectivityStatus>('connected');
  private speedSubject = new BehaviorSubject<number>(0);
  private readonly LOW_SPEED_MBPS = 3;
  private readonly CHECK_INTERVAL_MS = 15000;

  status$ = this.statusSubject.asObservable();
  speedMbps$ = this.speedSubject.asObservable();

  get status(): ConnectivityStatus {
    return this.statusSubject.value;
  }

  get speedMbps(): number {
    return this.speedSubject.value;
  }

  constructor() {
    this.init();
  }

  private init(): void {
    if (typeof window === 'undefined') return;

    window.addEventListener('online', () => this.checkStatus());
    window.addEventListener('offline', () => this.setDisconnected());

    this.checkStatus();

    interval(this.CHECK_INTERVAL_MS)
      .pipe(
        startWith(0),
        switchMap(() => this.measureSpeedOnce()),
        catchError(() => of(0))
      )
      .subscribe((mbps) => {
        this.speedSubject.next(mbps);
        if (!navigator.onLine || mbps <= 0) {
          this.statusSubject.next('disconnected');
        } else if (mbps < this.LOW_SPEED_MBPS) {
          this.statusSubject.next('low');
        } else {
          this.statusSubject.next('connected');
        }
      });
  }

  private setDisconnected(): void {
    this.speedSubject.next(0);
    this.statusSubject.next('disconnected');
  }

  private checkStatus(): void {
    if (!navigator.onLine) {
      this.setDisconnected();
      return;
    }
    this.measureSpeedOnce().subscribe((mbps) => {
      this.speedSubject.next(mbps);
      if (mbps < this.LOW_SPEED_MBPS) {
        this.statusSubject.next('low');
      } else {
        this.statusSubject.next('connected');
      }
    });
  }

  private measureSpeedOnce(): Observable<number> {
    return new Observable<number>((subscriber) => {
      if (typeof navigator === 'undefined' || !navigator.onLine) {
        subscriber.next(0);
        subscriber.complete();
        return;
      }
      const conn = (navigator as any).connection;
      if (conn && typeof conn.downlink === 'number') {
        subscriber.next(conn.downlink);
        subscriber.complete();
        return;
      }
      const start = performance.now();
      fetch(`${window.location.origin}/?nocache=${Date.now()}`, { cache: 'no-store' })
        .then(() => {
          const elapsedSec = (performance.now() - start) / 1000;
          const estimatedMbps = elapsedSec > 0 ? 5 / elapsedSec : 10;
          subscriber.next(Math.min(estimatedMbps, 100));
          subscriber.complete();
        })
        .catch(() => {
          subscriber.next(navigator.onLine ? 1 : 0);
          subscriber.complete();
        });
    });
  }

  refresh(): void {
    this.checkStatus();
  }
}
