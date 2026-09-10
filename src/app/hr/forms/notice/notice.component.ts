import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-notice',
  templateUrl: './notice.component.html',
  styleUrls: ['./notice.component.css']
})
export class NoticeComponent implements OnInit {
  isNewForm = false;
  departments;
  notices;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getNotices();
  }

  getNotices() {
    this.service.get('hrDepartment.php?type=getNoticeById')
    .subscribe(response => {
      this.notices = response;
    });
  }

  sendNotice(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('hrDepartment.php?type=sendNotice', JSON.stringify(data.value))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('Send successfully sent to Employee');
        data.reset();
      } else {
        alert(response['status']);
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
