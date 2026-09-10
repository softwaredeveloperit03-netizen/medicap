import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  products;
  results;
  stages = ''
  selectedResult = [];
  selectedReview = [];
  isNew = false;
  isView = false;
  isBill = false;
  isunitView = false;
  data = [];

  yield_percentage = '';
  yield_qty = '';

  selectedStage;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckedBatches();
  }

  getCheckedBatches() {
    this.service.get('production/inprocess.php?type=getCheckedBatches').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }

  viewUnit(){
    this.isunitView=true;
  }

  viewBill(){
    this.isBill=true;
  }

  viewstage(index) {
    this.stages = this.selectedResult['stages'];
    this.selectedStage = this.stages[index];
    this.isView = true;
  }

  save(status){
    this.service.get('production/inprocess.php?type=approveBatch&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response =>{
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.isNew = false;
        this.getCheckedBatches();
      }else{
        alert('Failed an error Occured,Please try again!');
      }
    });
  }


}
