import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(public service:DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessDQ();
  }

  getInprocessDQ(){
    this.service.get('qa/qualification.php?type=getInprocessDQ').subscribe(response => {
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
  


  update(status){
    this.service.get('qa/qualification.php?type=updateDQ&id=' + this.selectedResult['id'] + '&status=' + status).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getInprocessDQ();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
