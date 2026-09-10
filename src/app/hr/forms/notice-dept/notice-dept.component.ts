import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-notice-dept',
  templateUrl: './notice-dept.component.html',
  styleUrls: ['./notice-dept.component.css']
})
export class NoticeDeptComponent implements OnInit {
  notices;
  isRemark = false;
  remark;
  notice_id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getNotices();
  }

  getNotices() {
    this.service.get('hrDepartment.php?type=getNotices')
    .subscribe(response => {
      this.notices = response;
    });
  }
  
  viewremark(id) {
    this.notice_id = id;
    this.isRemark = true;
  }

  sendNoticeRemark() {
    this.isRemark = false;
    this.service.get('hrDepartment.php?type=sendNoticeRemark&notice_id='+ this.notice_id+'&remark='+ this.remark)
    .subscribe(response => {
      this.getNotices();
    });
  }

}
