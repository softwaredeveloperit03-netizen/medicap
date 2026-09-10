import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results: any[] = [];
  loading = false;

  selectedReport = [];
  total = 0;
  damages = [];
  checkPointData ;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessDamagesLog();
   }


  getInprocessDamagesLog() {
    this.loading = true;
    this.service.get('store/raw.php?type=getInprocessDamagesLog').subscribe(response => {
      const rows = Array.isArray(response) ? response : [];
      this.results = rows.filter((row) => {
        const status = String((row && row.damage) || '').trim().toLowerCase();
        return status !== 'yes';
      });
      this.loading = false;
    }, () => {
      this.results = [];
      this.loading = false;
    });
  }

  view(result) {
    this.damages = [];
    this.selectedReport = this.normalizeDamageReport(result);
    this.isView = true;
    this.damage_details = this.selectedReport['damage_details'] || [];
  }

  getBatchNo(): string {
    return this.resolveBatchNo(this.selectedReport);
  }

  private normalizeDamageReport(data: any): any {
    const report = { ...(data || {}) };

    if (typeof report.receiving_details === 'string') {
      try {
        report.receiving_details = JSON.parse(report.receiving_details);
      } catch {
        report.receiving_details = {};
      }
    }
    if (!report.receiving_details || typeof report.receiving_details !== 'object') {
      report.receiving_details = {};
    }

    if (typeof report.damage_details === 'string') {
      try {
        report.damage_details = JSON.parse(report.damage_details);
      } catch {
        report.damage_details = [];
      }
    }
    if (!Array.isArray(report.damage_details)) {
      report.damage_details = report.damage_details ? [report.damage_details] : [];
    }

    if (typeof report.damageChecklist === 'string') {
      try {
        report.damageChecklist = JSON.parse(report.damageChecklist);
      } catch {
        report.damageChecklist = [];
      }
    }

    report.batch_no = this.resolveBatchNo(report);
    report.damaeImg1 = this.resolveDamageImg(report, 1);
    report.damaeImg2 = this.resolveDamageImg(report, 2);
    return report;
  }

  private resolveBatchNo(report: any): string {
    if (!report) {
      return '';
    }

    const direct = String(report.batch_no || report.damage_batch_no || '').trim();
    if (direct) {
      return direct;
    }

    const details = report.receiving_details || {};
    const fromDetails = String(details.damage_batch_no || details.batch_no || '').trim();
    if (fromDetails) {
      return fromDetails;
    }

    const batches = Array.isArray(report.batches) ? report.batches : [];
    if (batches.length === 1) {
      return String(batches[0]?.batch_no || '').trim();
    }

    if (batches.length > 1) {
      const batchNos = batches
        .map((batch: any) => String(batch?.batch_no || '').trim())
        .filter((batchNo: string) => !!batchNo);
      return [...new Set(batchNos)].join(', ');
    }

    return '';
  }

  getDamageImg(slot: 1 | 2): string {
    return this.resolveDamageImg(this.selectedReport, slot);
  }

  hasDamageImage(slot: 1 | 2): boolean {
    const fileName = this.getDamageImg(slot);
    return !!fileName && fileName.toUpperCase() !== 'NA';
  }

  private resolveDamageImg(report: any, slot: 1 | 2): string {
    if (!report) {
      return 'NA';
    }

    const key = slot === 1 ? 'damaeImg1' : 'damaeImg2';
    const lowerKey = key.toLowerCase();
    const details = report.receiving_details || {};
    const candidates = [
      report[key],
      report[lowerKey],
      details[key],
      details[lowerKey],
    ];

    for (const value of candidates) {
      const fileName = String(value || '').trim();
      if (fileName && fileName.toUpperCase() !== 'NA') {
        return fileName;
      }
    }

    return 'NA';
  }
 
  viewFile(url) {
    const fileName = String(url || '').trim();
    if (!fileName || fileName.toUpperCase() === 'NA') {
      return;
    }
    const viewUrl = this.service.url + 'upload/damage/' + encodeURIComponent(fileName) + '?v=1';
    window.open(viewUrl, '_blank');
  }
 
  damage_details =[];


}
