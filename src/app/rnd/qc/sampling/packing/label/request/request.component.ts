import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {
 
  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getApprovedLabels();
  }

  getApprovedLabels(){
    this.service.get('qc/sampling/packing.php?type=getApprovedLabels').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  saveLabelRequest(data){
    if(!data.valid){
      alertify.error('All fields are requried!');
      return;
    }
    this.service.post('qc/sampling/packing.php?type=saveLabelRequest&id=' + this.selectedResult['id'], JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Saved Successfully!');
        this.isView = false;
        this.getApprovedLabels();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
