import { Component } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';

type WoHubTab = 'confirm-indent' | 'indent-log' | 'can-plan';

@Component({
  selector: 'app-wo-planning-hub',
  templateUrl: './wo-planning-hub.component.html',
  styleUrls: ['./wo-planning-hub.component.css'],
})
export class WoPlanningHubComponent {
  activeTab: WoHubTab = 'confirm-indent';

  constructor(private router: Router) {
    this.syncTabFromUrl(this.router.url);
    this.router.events
      .pipe(filter((e) => e instanceof NavigationEnd))
      .subscribe((e: NavigationEnd) => this.syncTabFromUrl(e.urlAfterRedirects));
  }

  private syncTabFromUrl(url: string): void {
    if (url.includes('can-plan')) {
      this.activeTab = 'can-plan';
    } else if (url.includes('indent-log') || url.includes('rmpm-indent')) {
      this.activeTab = 'indent-log';
    } else if (url.includes('indent-confirmation')) {
      this.activeTab = 'confirm-indent';
    } else {
      this.activeTab = 'confirm-indent';
    }
  }

  navigateTab(tab: WoHubTab): void {
    this.activeTab = tab;
    this.router.navigate(['/planning/WoPlanningHub', tab]);
  }
}
