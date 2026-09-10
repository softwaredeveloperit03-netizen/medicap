import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-no-dues',
  templateUrl: './no-dues.component.html',
  styleUrls: ['./no-dues.component.css']
})
export class NoDuesComponent implements OnInit {
  resignations;
  isRemark = false;
  id;
  remark;
  status;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getResignationCleanrance();
  }

  getResignationCleanrance() {
    this.service.get('hrDepartment.php?type=getResignationCleanrance')
    .subscribe(response => {
      this.resignations = response;
    });
  }

  showRemark(id) {
    this.id = id;
    this.isRemark = true;
  }

  sendResignationStatus() {
    this.service.get('hrDepartment.php?type=sendResignationStatus&id='+ this.id + '&remark=' + encodeURIComponent(this.remark) + '&status=' + this.status)
    .subscribe(response => {
      this.isRemark = false;
      this.getResignationCleanrance();
    });
  }

}
