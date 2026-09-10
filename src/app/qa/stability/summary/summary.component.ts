import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-summary',
  templateUrl: './summary.component.html',
  styleUrls: ['./summary.component.css']
})
export class SummaryComponent implements OnInit {

  isView = false;
  results;

  selectedStability = [];
  isViewSummary = false;

  selectedCondition = [];
  intervals = [];
  tests = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getSummaryReport();
  }

  getSummaryReport() {
    this.service.get('stability.php?type=getSummaryReport').subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('stability.php?type=summaryReportPdf');
  }
  download1() {
    this.service.open('stability.php?type=summaryReportPdf2');
  }
  view(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }

  viewSummary(index, batch_no) {
    let conditions = this.selectedStability['conditions'];
    this.selectedCondition = conditions[index];
    let intervals = this.selectedCondition['interval'];
    for (let i = 0; i < intervals.length; i++) {
      let interval = intervals[i];
      try {
        if (interval["batches"] == undefined) {
          interval['test'] = 'pending';
        } else {
          let batches = interval["batches"];
          for (let j = 0; j < batches.length; j++) {
            let batch = batches[j];
            if (batch['batch_no'] == batch_no) {
              if (batch['tests'] == undefined) {
                interval['test'] = 'pending';
              } else {
                let tests = batch['tests'];
                interval['tests'] = batch['tests'];
                for (let k = 0; k < tests.length; k++) {
                  let test = tests[k];
                  interval['test'] = test['result'];
                }
              }
            }
          }
        }
      } catch(err) {}
      intervals[i] = interval;
    }
    this.selectedCondition['interval'] = intervals;
    conditions[index] = this.selectedCondition;
    let tests = this.selectedStability['tests'];
    for (let i = 0; i < tests.length; i++) {
      let test = tests[i];
      test['intervals'] = this.selectedCondition['interval'];
    }
    console.log(tests);
    this.intervals = this.selectedCondition['interval'];
    this.tests = tests;
    this.isViewSummary = true;
  }



}
