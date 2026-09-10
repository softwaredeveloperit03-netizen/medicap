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
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingAdditionalLabels();
  }

  getPendingAdditionalLabels(){
    this.service.get('qc/sampling/packing.php?type=getPendingAdditionalLabels').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  updateRequest(status){
    this.service.get('qc/sampling/packing.php?type=updateAdditionalLabel&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isView = false;
        this.getPendingAdditionalLabels();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }


}
