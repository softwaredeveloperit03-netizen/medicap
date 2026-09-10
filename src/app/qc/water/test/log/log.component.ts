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
  selectedPlan: any = {};
  spec_tests: any[] = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getTestingLog();
  }

  getTestingLog() {
    this.service.get('qc/water.php?type=getTestingLog').subscribe((response: any) => {
      this.results = response || [];
    });
  }

  view(index: number) {
    this.selectedPlan = this.results[index] || {};
    this.spec_tests = Array.isArray(this.selectedPlan['tests']) ? this.selectedPlan['tests'] : [];
    this.isView = true;
  }

  downloadWaterTestingLog() {
    this.service.open('qc/water.php?type=downloadWaterTestingLog');
  }

  printSampledByQcLabel(data) {
    if (!data || !data.id) {
      return;
    }
    this.service.open('qc/water.php?type=printWaterSampledByQcLabel&id=' + data.id);
  }

}
