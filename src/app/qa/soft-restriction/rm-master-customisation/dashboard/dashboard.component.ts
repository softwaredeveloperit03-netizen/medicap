import { Component, OnInit } from '@angular/core';
import { RmMasterCustomisationService } from '../rm-master-customisation.service';
import { RMCustomisationRecord } from '../rm-master-customisation.constants';
import { GmpMaterialFormCustomisationService, GmpFormLogMeta } from '../gmp-material-form-customisation.service';

@Component({
  selector: 'app-rm-master-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results: RMCustomisationRecord[] = [];
  gmpLog: GmpFormLogMeta[] = [];

  constructor(
    private service: RmMasterCustomisationService,
    private gmp: GmpMaterialFormCustomisationService
  ) {}

  ngOnInit(): void {
    this.loadLog();
    this.loadGmpLog();
  }

  loadLog(): void {
    this.service.getLog().subscribe((res: any) => {
      this.results = Array.isArray(res) ? res : [];
    });
  }

  loadGmpLog(): void {
    this.gmp.getLog().subscribe((res: any) => {
      this.gmpLog = Array.isArray(res) ? res : [];
    });
  }

  scrollToRevisionHistory(): void {
    const el = document.getElementById('revision-history');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}
