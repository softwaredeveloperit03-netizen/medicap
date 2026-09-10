import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-approval-dashboard',
  templateUrl: './approval-dashboard.component.html',
  styleUrls: ['./approval-dashboard.component.css']
})
export class ApprovalDashboardComponent implements OnInit, OnDestroy {
  /** When a child submodule is open, hide the tile grid (same pattern as other modules). */
  showTiles = true;
  private sub: Subscription;

  constructor(private router: Router) {}

  ngOnInit(): void {
    this.updateShowTiles(this.router.url);
    this.sub = this.router.events
      .pipe(filter((e): e is NavigationEnd => e instanceof NavigationEnd))
      .subscribe((e) => this.updateShowTiles(e.urlAfterRedirects || e.url));
  }

  ngOnDestroy(): void {
    if (this.sub) {
      this.sub.unsubscribe();
    }
  }

  private updateShowTiles(url: string): void {
    const path = (url || '').split('?')[0].replace(/\/+$/, '');
    // Show tiles only on /qa/approval (not on nested child routes)
    this.showTiles = path === '/qa/approval' || path.endsWith('/qa/approval');
  }
}
