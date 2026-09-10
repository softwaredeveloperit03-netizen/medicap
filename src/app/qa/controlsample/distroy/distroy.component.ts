import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-distroy',
  templateUrl: './distroy.component.html',
  styleUrls: ['./distroy.component.css']
})
export class DistroyComponent implements OnInit {

  selectedEntry;
  entries;
  dist_entries;
  isView = false;
  isApprover;
  isChecker;
  steps;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getDistructionDetails();
   // this.getControlsamples();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

    if (localStorage.getItem('checker') === 'true') {
       this.isChecker = true;
      } else {
       this.isChecker = false;
      }
  }

  viewEntry(index) {
    this.selectedEntry = this.dist_entries[index];
    this.isView = true;
  }

  getDistructionDetails() {
    this.service.get('control_sample.php?type=getDistructionDetails').subscribe(response => {
      this.dist_entries = response;
    });
  }

  getControlsamples() {
    this.service.get('control_sample.php?type=getControlsamples').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
  this.router.navigate(['/control-sample-dashboard']);
  }

}


