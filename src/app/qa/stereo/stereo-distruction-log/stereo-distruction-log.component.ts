import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare var swal: any;

@Component({
  selector: 'app-stereo-distruction-log',
  templateUrl: './stereo-distruction-log.component.html',
  styleUrls: ['./stereo-distruction-log.component.css']
})
export class StereoDistructionLogComponent implements OnInit {
  isNew = false;
  entries;
  selectedEntry;
  isView = false;
  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getDistructionLog();
  }

  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  getDistructionLog() {
    this.service.get('audit-trails.php?type=getDistructionLog').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/qa/stereo']);
  }

}
