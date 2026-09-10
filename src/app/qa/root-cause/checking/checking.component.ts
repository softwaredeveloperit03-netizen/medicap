import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css'],
})
export class CheckingComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  isView = false;
  rcaList: any[] = [];
  selectedRca: any = {};

  ngOnInit(): void {
    this.getRcaLog();
  }

  getRcaLog() {
    this.service.get('qa/rca.php?type=getRcaLog').subscribe((res: any) => {
      this.rcaList = res;
    });
  }

  view(index: number) {
    this.selectedRca = this.rcaList[index];
    this.isView = true;
  }
}
