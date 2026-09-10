import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-pur-vendor-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class PurVendorDashboardComponent {
  constructor(private router: Router) {}

  close(): void {
    this.router.navigate(['/rnd']);
  }
}
