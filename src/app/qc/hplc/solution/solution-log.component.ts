import { Component, OnInit } from '@angular/core';
import { HPLC_BASE } from '../shared/hplc-demo-mode';
import { HplcDemoDataService } from '../shared/hplc-demo-data.service';

@Component({
  selector: 'app-hplc-solution-log',
  templateUrl: './solution-log.component.html',
  styleUrls: ['../shared/hplc-shared.css'],
})
export class SolutionLogComponent implements OnInit {
  back = HPLC_BASE;
  rows: any[] = [];
  filterHplc = '';

  constructor(public demo: HplcDemoDataService) {}

  ngOnInit(): void {
    this.rows = this.demo.getSolutions();
  }

  filtered() {
    return this.rows.filter((r) => !this.filterHplc || r.hplc_id === this.filterHplc);
  }
}
