import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;

@Component({
  selector: 'app-inspection-log',
  templateUrl: './inspection-log.component.html',
  styleUrls: ['./inspection-log.component.css']
})
export class InspectionLogComponent implements OnInit {

  isNew = false;
  isView = false;
  entries1;
  selectedEntry;
  constructor(private service: DataAccessService , private router: Router) { }

  ngOnInit() {
    this.getInspectionLog();
  }

  getInspectionLog() {
    this.service.get('audit-trails.php?type=getInspectionLogByDept1').subscribe(response => {
      this.entries1 = response;
    });
  }

  View(index) {
  this.selectedEntry = this.entries1[index];
  this.isView = true;
  }

  close() {
    this.router.navigate(['/audit']);
  }


}
