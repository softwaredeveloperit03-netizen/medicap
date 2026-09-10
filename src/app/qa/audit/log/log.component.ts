import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isNew = false;
  entries1;

  constructor(private service: DataAccessService , private router: Router) {
   }

  ngOnInit() {
    this.getInspectionLog();
  }

  getInspectionLog() {
    this.service.get('audit-trails.php?type=getInspectionLog').subscribe(response => {
      this.entries1 = response;
    });
  }
  

  close() {
    this.router.navigate(['/qa/audit']);
  }

}
