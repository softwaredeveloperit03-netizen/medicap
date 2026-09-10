import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-start',
  templateUrl: './start.component.html',
  styleUrls: ['./start.component.css']
})
export class StartComponent implements OnInit {
  
  isView = false;
  results;
  loading;
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingMeetings();
  }

  getPendingMeetings(){
    this.service.get('management/meeting.php?type=getPendingMeetings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  startMeeting(id){
    this.service.get('management/meeting.php?type=startMeeting&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Meeting Start Successfully');
        this.getPendingMeetings();
        this.isView = false;
      }
    });
  }

}
