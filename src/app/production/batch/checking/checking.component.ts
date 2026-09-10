import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

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
    this.getInprocessBatches();
  }

  getInprocessBatches() {
    this.service.get('production/inprocess.php?type=getActiveBatches').subscribe(response => {
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

  update(status){
    this.service.get('production/inprocess.php?type=checkBatch&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response =>{
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.isNew = false;
        this.getInprocessBatches();
      }else{
        alert('Failed an error Occured,Please try again!');
      }
    });
  }

}
