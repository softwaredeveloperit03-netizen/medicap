import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

constructor(private service: DataAccessService, private router: Router) {}

  isView = false;
  rcaList: any[] = [];
  selectedRca: any = {};

  ngOnInit(): void {
    this.getRcaLog();
  }

  getRcaLog() {
    this.service.get('qa/rca.php?type=getRcaMainLog').subscribe((res: any) => {
      this.rcaList = res;
    });
  }

  view(index: number) {
    this.selectedRca = this.rcaList[index];
    this.isView = true;
  }


}

