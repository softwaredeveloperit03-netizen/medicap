import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-inprocess-checks',
  templateUrl: './inprocess-checks.component.html',
  styleUrls: ['./inprocess-checks.component.css']
})
export class InprocessChecksComponent implements OnInit {

  isView = false;
  records;
  selectedBMR = [];

  current_date = '';

  checks = [];
  constructor(private service: DataAccessService) {
    this.current_date = new Date().toLocaleString();;
  }

  ngOnInit(): void {
    this.getPendingQAInprocessChecks();
  }

  getPendingQAInprocessChecks() {
    this.service.get('qa.php?type=getPendingQAInprocessChecks').subscribe(response => {
      this.records = response;
    });
  }

  viewBMR(index) {
    this.selectedBMR = this.records[index];
    this.isView = true;
  }

  addChecks(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.checks[this.checks.length] = data.value;
  }

  saveInprocessChecks() {
    this.isView = false;
  }

}
