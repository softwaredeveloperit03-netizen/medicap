import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-approve',
  templateUrl: './approve.component.html',
  styleUrls: ['./approve.component.css']
})
export class ApproveComponent implements OnInit {

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

  approve() {
  const payload = {
    id: this.selectedRca.id,
    status: 'close'
  };

  this.service.post('qa/rca.php?type=approveRca', payload)
    .subscribe((res: any) => {
      if (res.success) {
        alert('RCA Approved Successfully');
        this.isView = false;
        this.getRcaLog(); 
      } else {
        alert('Approval failed');
      }
    });
}

}

