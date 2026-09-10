import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  entries;

  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getControlsampleWithdrawals();
  }

  getControlsampleWithdrawals() {
    this.service.get('control_sample.php?type=getControlsampleWithdrawals').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }
  getprint(){
    this.service.open('pdf1/controlsample.php?type=withdrawallog');
  }

}
