import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  reports;
  selectedReview = [];
  isView = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
   this.getGlasswaresLog();
  }

  getGlasswaresLog() {
    this.service.get('qc/glassware.php?type=getGlasswaresLog').subscribe(response => {
      this.reports = response;
    });
  }

  download() {
    this.service.open('qc/glassware.php?type=downloadGlasswaresLog')
  }
  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }
  

}
