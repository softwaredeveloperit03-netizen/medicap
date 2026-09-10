import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-audit-trail',
  templateUrl: './hplc-audit-trail.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class HplcAuditTrailComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  filterModule = '';
  filterUser = '';

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getAuditTrail();
  }

  filtered() {
    return this.rows.filter(
      (r) =>
        (!this.filterModule || r.module.toLowerCase().includes(this.filterModule.toLowerCase())) &&
        (!this.filterUser || r.user.toLowerCase().includes(this.filterUser.toLowerCase()))
    );
  }
}
