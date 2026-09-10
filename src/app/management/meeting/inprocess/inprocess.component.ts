import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['./inprocess.component.css']
})
export class InprocessComponent implements OnInit {
  loading;
  isView = false;
  results;
  remark = '';
  managers = [];
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getInprocessMeetings();
  }

  getInprocessMeetings(){
    this.service.get('management/meeting.php?type=getInprocessMeetings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  addAttendance(index, status) {
    let data = this.selectedResult['managers'];
    data[index].attendance = status;
    this.selectedResult['managers'] = data;
  }

  completeMeeting(){
    const temp = new FormData();
    temp['remark'] = this.remark;
    temp['id'] = this.selectedResult['id'];
    temp['managers'] = this.selectedResult['managers'];
    this.service.post('management/meeting.php?type=completeMeeting', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Meeting Completed Successfully');
        this.getInprocessMeetings();
        this.isView = false;
      }
    });
  }

}
