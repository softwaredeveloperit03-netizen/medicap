import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(public service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingFactory();
  }

  getPendingFactory(){
    this.service.get('qa/qualification.php?type=getPendingFactory').subscribe(response => {
      this.results = response;
    });
  }

  url = this.service.url;

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  ViewCertificate() {
    window.open(this.service.url+'../../upload/dq/' + this.selectedResult['dq_file']);
  }
  

  updateAwaiting(status, data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('qa/qualification.php?type=saveFactory&status=' + status + '&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getPendingFactory();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
